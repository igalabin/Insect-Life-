<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_ADMIN]);

global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
	$action = $_POST['form_action'] ?? '';
	if ($action === 'assign') {
		$orderId = (int)($_POST['order_id'] ?? 0);
		$staffId = (int)($_POST['assigned_staff_id'] ?? 0);
		$stmt = $pdo->prepare('UPDATE orders SET assigned_staff_id=:sid, updated_at=CURRENT_TIMESTAMP WHERE order_id=:id');
		$stmt->execute([':sid' => $staffId, ':id' => $orderId]);
		header('Location: ' . BASE_URL . 'admin/orders.php?m=assigned');
		exit;
	}
	if ($action === 'status') {
		$orderId = (int)($_POST['order_id'] ?? 0);
		$newStatus = trim((string)($_POST['order_status'] ?? STATUS_PENDING));
		$notes = trim((string)($_POST['notes'] ?? ''));
		$allowed = [STATUS_CONFIRMED, STATUS_PREPARING, STATUS_ON_DELIVERY, STATUS_COMPLETED, STATUS_CANCELLED];
		if ($orderId > 0 && in_array($newStatus, $allowed, true)) {
			$pdo->prepare('UPDATE orders SET order_status=:st, updated_at=CURRENT_TIMESTAMP WHERE order_id=:id')
				->execute([':st' => $newStatus, ':id' => $orderId]);
			$pdo->prepare('INSERT INTO order_tracking (order_id, status, notes, updated_by) VALUES (:id, :st, :n, :u)')
				->execute([':id' => $orderId, ':st' => $newStatus, ':n' => $notes, ':u' => (int)($_SESSION['user']['id'] ?? 0)]);
			header('Location: ' . BASE_URL . 'admin/orders.php?m=updated');
			exit;
		}
	}
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . BASE_URL . 'admin/orders.php?e=Invalid+CSRF+token');
    exit;
}

$appTitle = 'Orders - Admin';
require __DIR__ . '/../includes/header-admin.php';

$rows = $pdo->query("SELECT o.*, d.full_name AS driver_name, s.full_name AS staff_name FROM orders o LEFT JOIN drivers d ON d.driver_id=o.driver_id LEFT JOIN users s ON s.user_id=o.assigned_staff_id ORDER BY o.order_id DESC")->fetchAll();
$staff = $pdo->query("SELECT user_id, full_name FROM users WHERE role='staff' AND status='active' ORDER BY full_name")->fetchAll();
$assignId = isset($_GET['assign_id']) ? (int)$_GET['assign_id'] : 0;
$statusId = isset($_GET['status_id']) ? (int)$_GET['status_id'] : 0;
$assignOrder = null; $statusOrder = null;
if ($assignId > 0) {
    $st = $pdo->prepare('SELECT order_id, order_number, assigned_staff_id FROM orders WHERE order_id = :id');
    $st->execute([':id' => $assignId]);
    $assignOrder = $st->fetch();
}
if ($statusId > 0) {
    $st = $pdo->prepare('SELECT order_id, order_number, order_status FROM orders WHERE order_id = :id');
    $st->execute([':id' => $statusId]);
    $statusOrder = $st->fetch();
}
$csrf = generate_csrf_token();
?>

<div class="container py-4">
	<h1 class="h3 mb-3">Orders</h1>
    <?php if (isset($_GET['m'])): ?>
        <div class="alert alert-success py-2">Action <?= sanitize($_GET['m']) ?> successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['e'])): ?>
        <div class="alert alert-danger py-2">Error: <?= sanitize($_GET['e']) ?></div>
    <?php endif; ?>

	<!-- Inline Assign (no modal) -->
	<?php if ($assignOrder): ?>
	<div class="card mb-3 border-secondary">
		<div class="card-body py-3">
			<h6 class="card-title mb-3 text-muted">Assign staff to <?= sanitize($assignOrder['order_number']) ?></h6>
			<form method="post" class="small">
				<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
				<input type="hidden" name="form_action" value="assign">
				<input type="hidden" name="order_id" value="<?= (int)$assignOrder['order_id'] ?>">
				<div class="row g-2 align-items-end">
					<div class="col-md-6">
						<label class="form-label">Staff</label>
						<select class="form-select form-select-sm" name="assigned_staff_id" required>
							<option value="">— Select Staff —</option>
							<?php foreach ($staff as $s): ?>
							<option value="<?= (int)$s['user_id'] ?>" <?= ((int)($assignOrder['assigned_staff_id'] ?? 0) === (int)$s['user_id'])?'selected':'' ?>><?= sanitize($s['full_name']) ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-6 d-flex gap-2">
						<button class="btn btn-sm btn-primary">Save</button>
						<a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>admin/orders.php">Cancel</a>
					</div>
				</div>
			</form>
		</div>
	</div>
	<?php endif; ?>

	<!-- Inline Status Update (no modal) -->
	<?php if ($statusOrder): ?>
	<div class="card mb-3 border-secondary">
		<div class="card-body py-3">
			<h6 class="card-title mb-3 text-muted">Update status for <?= sanitize($statusOrder['order_number']) ?></h6>
			<form method="post" class="small">
				<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
				<input type="hidden" name="form_action" value="status">
				<input type="hidden" name="order_id" value="<?= (int)$statusOrder['order_id'] ?>">
				<div class="row g-2 align-items-end">
					<div class="col-md-6">
						<label class="form-label">Order Status</label>
						<select class="form-select form-select-sm" name="order_status">
							<?php foreach ([STATUS_CONFIRMED, STATUS_PREPARING, STATUS_ON_DELIVERY, STATUS_COMPLETED, STATUS_CANCELLED] as $st): ?>
							<option value="<?= $st ?>" <?= $statusOrder['order_status']===$st?'selected':'' ?>><?= ucwords(str_replace('_',' ', $st)) ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-6">
						<label class="form-label">Notes</label>
						<input class="form-control form-control-sm" name="notes" placeholder="Optional notes">
					</div>
				</div>
				<div class="mt-3 d-flex gap-2">
					<button class="btn btn-sm btn-primary">Save</button>
					<a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>admin/orders.php">Cancel</a>
				</div>
			</form>
		</div>
	</div>
	<?php endif; ?>

	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead>
				<tr>
					<th>#</th>
					<th>Order</th>
					<th>Driver</th>
					<th>Total</th>
					<th>Payment</th>
					<th>Status</th>
					<th>Assigned</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $r): ?>
				<tr>
					<td><?= (int)$r['order_id'] ?></td>
					<td><?= sanitize($r['order_number']) ?><div class="small text-muted"><?= sanitize($r['created_at']) ?></div></td>
					<td><?= sanitize($r['driver_name'] ?? '') ?></td>
					<td>₱<?= number_format((float)$r['total_amount'], 2) ?></td>
					<td><span class="badge bg-secondary"><?= sanitize($r['payment_status']) ?></span></td>
					<td><span class="badge bg-info text-dark"><?= sanitize($r['order_status']) ?></span></td>
					<td><?= sanitize($r['staff_name'] ?? '—') ?></td>
					<td>
						<a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>admin/orders.php?assign_id=<?= (int)$r['order_id'] ?>">Assign</a>
						<a class="btn btn-sm btn-outline-success" href="<?= BASE_URL ?>admin/orders.php?status_id=<?= (int)$r['order_id'] ?>">Update</a>
					</td>
				</tr>

				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>


