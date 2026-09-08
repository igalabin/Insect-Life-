<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

start_secure_session();
header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'products':
            echo json_encode(['ok' => true, 'data' => getActiveProducts()]);
            break;

        case 'branch_options':
            // Return candidate branches near location or in specified city with sufficient stock
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['ok' => false, 'error' => 'POST required']);
                break;
            }
            if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
                break;
            }
            $productId = (int)($_POST['product_id'] ?? 0);
            $qty = (float)($_POST['quantity_liters'] ?? 0);
            $lat = (float)($_POST['delivery_latitude'] ?? 0);
            $lng = (float)($_POST['delivery_longitude'] ?? 0);
            $city = trim((string)($_POST['delivery_city'] ?? ''));
            $province = trim((string)($_POST['delivery_province'] ?? ''));
            $candidates = listCandidateBranches($productId, $qty, $lat, $lng, $city, $province, 5);
            echo json_encode(['ok' => true, 'data' => $candidates]);
            break;

        case 'create':
            $payload = [
                'driver_id' => $_SESSION['user']['id'] ?? null,
                'vehicle_type' => trim((string)($_POST['vehicle_type'] ?? '')),
                'vehicle_plate_number' => trim((string)($_POST['vehicle_plate_number'] ?? '')),
                'product_id' => (int)($_POST['product_id'] ?? 0),
                'quantity_liters' => (float)($_POST['quantity_liters'] ?? 0),
                'payment_method' => trim((string)($_POST['payment_method'] ?? 'cod')),
                'delivery_latitude' => (float)($_POST['delivery_latitude'] ?? 0),
                'delivery_longitude' => (float)($_POST['delivery_longitude'] ?? 0),
                'delivery_address' => trim((string)($_POST['delivery_address'] ?? '')),
                // Optional location fields to prefer municipality match
                'delivery_city' => trim((string)($_POST['delivery_city'] ?? '')),
                'delivery_province' => trim((string)($_POST['delivery_province'] ?? '')),
                'preferred_branch_id' => (int)($_POST['preferred_branch_id'] ?? 0),
            ];
            if (!$payload['driver_id']) {
                http_response_code(401);
                echo json_encode(['ok' => false, 'error' => 'Login required']);
                break;
            }
            $orderId = createQuickOrder($payload);
            echo json_encode(['ok' => true, 'order_id' => $orderId]);
            break;

        case 'my_orders':
            $driverId = $_SESSION['user']['id'] ?? 0;
            if (!$driverId) {
                http_response_code(401);
                echo json_encode(['ok' => false, 'error' => 'Login required']);
                break;
            }
            echo json_encode(['ok' => true, 'data' => getDriverOrders($driverId)]);
            break;

        case 'order_detail':
            $driverId = $_SESSION['user']['id'] ?? 0;
            $orderId = (int)($_GET['id'] ?? 0);
            if (!$driverId) {
                http_response_code(401);
                echo json_encode(['ok' => false, 'error' => 'Login required']);
                break;
            }
            if (!$orderId) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Order ID required']);
                break;
            }
            $order = getOrderDetail($orderId, $driverId);
            if (!$order) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'Order not found']);
                break;
            }
            echo json_encode(['ok' => true, 'data' => $order]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Unsupported action']);
    }
} catch (RuntimeException $e) {
    // Business rule/validation errors (e.g., no active branch, invalid product)
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    // Unexpected errors; reveal details only in local environment
    http_response_code(500);
    $message = (defined('APP_ENV') && APP_ENV === 'local') ? ($e->getMessage()) : 'Server error';
    echo json_encode(['ok' => false, 'error' => $message]);
}

function getActiveProducts(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT product_id, product_name, fuel_type, price_per_liter FROM products WHERE status='active' ORDER BY product_name");
    return $stmt->fetchAll();
}

function getSystemSetting(string $key, $default) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = :k LIMIT 1');
    $stmt->execute([':k' => $key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? (is_numeric($val) ? (float)$val : $val) : $default;
}

function createQuickOrder(array $p): int {
    global $pdo;
    $deliveryFee = (float)getSystemSetting('delivery_fee', DEFAULT_DELIVERY_FEE);
    $product = fetchProduct((int)$p['product_id']);
    if (!$product) {
        throw new RuntimeException('Invalid product');
    }
    $pricePerLiter = (float)$product['price_per_liter'];
    $subtotal = $pricePerLiter * (float)$p['quantity_liters'];
    $total = $subtotal + $deliveryFee;

    // If driver selected a preferred branch and it has sufficient stock, use it; else prefer by city, else nearest
    $branch = null;
    if (!empty($p['preferred_branch_id'])) {
        $branch = validatePreferredBranch((int)$p['preferred_branch_id'], (int)$p['product_id'], (float)$p['quantity_liters']);
    }
    if (!$branch) {
        $branch = selectBranchForOrder(
        (int)$p['product_id'],
        (float)$p['quantity_liters'],
        (float)$p['delivery_latitude'],
        (float)$p['delivery_longitude'],
        (string)($p['delivery_city'] ?? ''),
        (string)($p['delivery_province'] ?? '')
        );
    }
    if (!$branch) { throw new RuntimeException('No branch with sufficient stock nearby'); }

    // Choose an active staff in that branch (first by name)
    $assignedStaffId = selectStaffForBranch((int)$branch['branch_id']);

    // Place order in a transaction and deduct stock
    $pdo->beginTransaction();
    try {
        $orderNumber = 'ORD-' . date('YmdHis') . '-' . random_int(100, 999);
        $stmt = $pdo->prepare('INSERT INTO orders (
            order_number, driver_id, branch_id, assigned_staff_id, delivery_latitude, delivery_longitude, delivery_address,
            vehicle_type, vehicle_plate_number, product_id, quantity_liters, price_per_liter, subtotal,
            delivery_fee, total_amount, payment_method
        ) VALUES (:num, :driver, :branch, :staff, :lat, :lng, :addr, :veh, :plate, :prod, :qty, :ppl, :sub, :fee, :tot, :pay)');
        $stmt->execute([
            ':num' => $orderNumber,
            ':driver' => (int)$p['driver_id'],
            ':branch' => (int)$branch['branch_id'],
            ':staff' => $assignedStaffId,
            ':lat' => $p['delivery_latitude'],
            ':lng' => $p['delivery_longitude'],
            ':addr' => $p['delivery_address'],
            ':veh' => $p['vehicle_type'],
            ':plate' => $p['vehicle_plate_number'],
            ':prod' => (int)$p['product_id'],
            ':qty' => $p['quantity_liters'],
            ':ppl' => $pricePerLiter,
            ':sub' => $subtotal,
            ':fee' => $deliveryFee,
            ':tot' => $total,
            ':pay' => $p['payment_method'],
        ]);
        $orderId = (int)$pdo->lastInsertId();

        // Deduct stock and write history
        deductStockForOrder((int)$branch['branch_id'], (int)$p['product_id'], (float)$p['quantity_liters'], $orderId);

        $pdo->prepare('INSERT INTO order_tracking (order_id, status, notes, updated_by) VALUES (:id, :st, :n, NULL)')
            ->execute([':id' => $orderId, ':st' => STATUS_PENDING, ':n' => 'Order placed via quick form']);

        $pdo->commit();
        return $orderId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function fetchProduct(int $productId): ?array {
    global $pdo;
    $s = $pdo->prepare('SELECT product_id, price_per_liter FROM products WHERE product_id = :id AND status = "active"');
    $s->execute([':id' => $productId]);
    $row = $s->fetch();
    return $row ?: null;
}

function fetchAnyActiveBranchId(): ?int {
    global $pdo;
    $row = $pdo->query("SELECT branch_id FROM branches WHERE status='active' ORDER BY branch_id LIMIT 1")->fetch();
    return $row ? (int)$row['branch_id'] : null;
}

function selectBranchForOrder(int $productId, float $qtyLiters, float $lat, float $lng, string $city = '', string $province = ''): ?array {
    global $pdo;
    // 1) Try by municipality (city + optional province)
    if ($city !== '') {
        $params = [':pid' => $productId, ':qty' => $qtyLiters, ':city' => $city];
        $whereProv = '';
        if ($province !== '') { $whereProv = ' AND b.province = :prov'; $params[':prov'] = $province; }
        $sqlCity = "
            SELECT b.branch_id, b.branch_name, bs.quantity_liters, 0 AS distance_km
            FROM branches b
            JOIN branch_stock bs ON bs.branch_id = b.branch_id AND bs.product_id = :pid
            WHERE b.status = 'active' AND bs.quantity_liters >= :qty AND b.city = :city" . $whereProv . "
            ORDER BY bs.quantity_liters DESC, b.branch_id ASC
            LIMIT 1
        ";
        $stCity = $pdo->prepare($sqlCity);
        $stCity->execute($params);
        $rowCity = $stCity->fetch();
        if ($rowCity) { return $rowCity; }
    }

    // 2) Fallback: nearest active branch with sufficient stock (Haversine)
    $sql = "
        SELECT b.branch_id, b.branch_name,
               (6371 * ACOS(
                   COS(RADIANS(:lat1)) * COS(RADIANS(b.latitude)) * COS(RADIANS(b.longitude) - RADIANS(:lng1)) +
                   SIN(RADIANS(:lat2)) * SIN(RADIANS(b.latitude))
               )) AS distance_km,
               bs.quantity_liters
        FROM branches b
        JOIN branch_stock bs ON bs.branch_id = b.branch_id AND bs.product_id = :pid
        WHERE b.status = 'active' AND bs.quantity_liters >= :qty
        ORDER BY distance_km ASC
        LIMIT 1
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':lat1' => $lat, ':lng1' => $lng, ':lat2' => $lat, ':pid' => $productId, ':qty' => $qtyLiters]);
    $row = $st->fetch();
    return $row ?: null;
}

function selectStaffForBranch(int $branchId): ?int {
    global $pdo;
    $st = $pdo->prepare("SELECT user_id FROM users WHERE role='staff' AND status='active' AND branch_id = :b ORDER BY full_name LIMIT 1");
    $st->execute([':b' => $branchId]);
    $id = $st->fetchColumn();
    return $id ? (int)$id : null;
}

function deductStockForOrder(int $branchId, int $productId, float $qty, int $orderId): void {
    global $pdo;
    // Lock row and update
    $lock = $pdo->prepare('SELECT quantity_liters FROM branch_stock WHERE branch_id = :b AND product_id = :p FOR UPDATE');
    $lock->execute([':b' => $branchId, ':p' => $productId]);
    $row = $lock->fetch();
    if (!$row) { throw new RuntimeException('Stock not found'); }
    $prev = (float)$row['quantity_liters'];
    if ($prev < $qty) { throw new RuntimeException('Insufficient stock'); }
    $newQty = $prev - $qty;
    $pdo->prepare('UPDATE branch_stock SET quantity_liters = :q, updated_at = CURRENT_TIMESTAMP WHERE branch_id = :b AND product_id = :p')
        ->execute([':q' => $newQty, ':b' => $branchId, ':p' => $productId]);
    // history
    $pdo->prepare('INSERT INTO stock_history (branch_id, product_id, transaction_type, quantity_change, previous_quantity, new_quantity, reference_id, notes, updated_by) VALUES (:b, :p, :t, :chg, :prev, :new, :ref, :n, :u)')
        ->execute([
            ':b' => $branchId,
            ':p' => $productId,
            ':t' => 'order',
            ':chg' => -$qty,
            ':prev' => $prev,
            ':new' => $newQty,
            ':ref' => $orderId,
            ':n' => 'Auto-deduct for order',
            ':u' => $_SESSION['user']['id'] ?? null,
        ]);
}

function validatePreferredBranch(int $branchId, int $productId, float $qtyLiters): ?array {
    global $pdo;
    $st = $pdo->prepare("SELECT b.branch_id, b.branch_name, bs.quantity_liters FROM branches b JOIN branch_stock bs ON bs.branch_id=b.branch_id AND bs.product_id=:pid WHERE b.branch_id=:b AND b.status='active' AND bs.quantity_liters >= :qty LIMIT 1");
    $st->execute([':pid' => $productId, ':b' => $branchId, ':qty' => $qtyLiters]);
    $row = $st->fetch();
    return $row ?: null;
}

function listCandidateBranches(int $productId, float $qtyLiters, float $lat, float $lng, string $city = '', string $province = '', int $limit = 5): array {
    global $pdo;
    $results = [];
    if ($city !== '') {
        $params = [':pid' => $productId, ':qty' => $qtyLiters, ':city' => $city];
        $whereProv = '';
        if ($province !== '') { $whereProv = ' AND b.province = :prov'; $params[':prov'] = $province; }
        $sqlCity = "
            SELECT b.branch_id, b.branch_name, bs.quantity_liters, 0 AS distance_km
            FROM branches b
            JOIN branch_stock bs ON bs.branch_id = b.branch_id AND bs.product_id = :pid
            WHERE b.status = 'active' AND bs.quantity_liters >= :qty AND b.city = :city" . $whereProv . "
            ORDER BY bs.quantity_liters DESC, b.branch_id ASC
            LIMIT :lim
        ";
        $stCity = $pdo->prepare($sqlCity);
        $stCity->bindValue(':pid', $productId, PDO::PARAM_INT);
        $stCity->bindValue(':qty', $qtyLiters);
        $stCity->bindValue(':city', $city);
        if ($province !== '') { $stCity->bindValue(':prov', $province); }
        $stCity->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stCity->execute();
        $results = $stCity->fetchAll();
    }
    if (count($results) >= 1) { return $results; }
    // Fallback nearest list
    $sql = "
        SELECT b.branch_id, b.branch_name,
               (6371 * ACOS(
                   COS(RADIANS(:lat1)) * COS(RADIANS(b.latitude)) * COS(RADIANS(b.longitude) - RADIANS(:lng1)) +
                   SIN(RADIANS(:lat2)) * SIN(RADIANS(b.latitude))
               )) AS distance_km,
               bs.quantity_liters
        FROM branches b
        JOIN branch_stock bs ON bs.branch_id = b.branch_id AND bs.product_id = :pid
        WHERE b.status = 'active' AND bs.quantity_liters >= :qty
        ORDER BY distance_km ASC
        LIMIT :lim
    ";
    $st = $pdo->prepare($sql);
    $st->bindValue(':lat1', $lat);
    $st->bindValue(':lng1', $lng);
    $st->bindValue(':lat2', $lat);
    $st->bindValue(':pid', $productId, PDO::PARAM_INT);
    $st->bindValue(':qty', $qtyLiters);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

function getDriverOrders(int $driverId): array {
    global $pdo;
    $s = $pdo->prepare('SELECT order_id, order_number, total_amount, payment_status, order_status, created_at FROM orders WHERE driver_id = :d ORDER BY order_id DESC');
    $s->execute([':d' => $driverId]);
    return $s->fetchAll();
}

function getOrderDetail(int $orderId, int $driverId): ?array {
    global $pdo;
    $stmt = $pdo->prepare('
        SELECT o.*, p.product_name, p.fuel_type, b.branch_name, b.address as branch_address,
               u.full_name as staff_name, ot.notes
        FROM orders o 
        LEFT JOIN products p ON p.product_id = o.product_id
        LEFT JOIN branches b ON b.branch_id = o.branch_id  
        LEFT JOIN users u ON u.user_id = o.assigned_staff_id
        LEFT JOIN order_tracking ot ON ot.order_id = o.order_id AND ot.status = o.order_status
        WHERE o.order_id = :id AND o.driver_id = :driver
        LIMIT 1
    ');
    $stmt->execute([':id' => $orderId, ':driver' => $driverId]);
    $order = $stmt->fetch();
    
    if (!$order) return null;
    
    return [
        'order_id' => $order['order_id'],
        'order_number' => $order['order_number'],
        'order_status' => $order['order_status'],
        'payment_status' => $order['payment_status'],
        'total_amount' => $order['total_amount'],
        'delivery_latitude' => $order['delivery_latitude'],
        'delivery_longitude' => $order['delivery_longitude'],
        'delivery_address' => $order['delivery_address'],
        'vehicle_type' => $order['vehicle_type'],
        'vehicle_plate_number' => $order['vehicle_plate_number'],
        'product_name' => $order['product_name'],
        'fuel_type' => $order['fuel_type'],
        'quantity_liters' => $order['quantity_liters'],
        'price_per_liter' => $order['price_per_liter'],
        'delivery_fee' => $order['delivery_fee'],
        'branch_name' => $order['branch_name'],
        'branch_address' => $order['branch_address'],
        'staff_name' => $order['staff_name'],
        'notes' => $order['notes'],
        'created_at' => $order['created_at']
    ];
}


