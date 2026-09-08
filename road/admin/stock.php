<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_ADMIN]);

global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
	$action = $_POST['form_action'] ?? '';
	if ($action === 'upsert') {
		$branchId = (int)($_POST['branch_id'] ?? 0);
		$productId = (int)($_POST['product_id'] ?? 0);
		$qty = (float)($_POST['quantity_liters'] ?? 0);
		$min = (float)($_POST['minimum_stock'] ?? 0);
		// upsert into branch_stock
		$stmt = $pdo->prepare('INSERT INTO branch_stock (branch_id, product_id, quantity_liters, minimum_stock, last_updated_by)
		VALUES (:b, :p, :q, :m, :u)
		ON DUPLICATE KEY UPDATE quantity_liters=:q2, minimum_stock=:m2, last_updated_by=:u2');
		$stmt->execute([
			':b' => $branchId, ':p' => $productId,
			':q' => $qty, ':m' => $min, ':u' => (int)($_SESSION['user']['id'] ?? 0),
			':q2' => $qty, ':m2' => $min, ':u2' => (int)($_SESSION['user']['id'] ?? 0),
		]);
		header('Location: ' . BASE_URL . 'admin/stock.php?m=updated');
		exit;
	}
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . BASE_URL . 'admin/stock.php?e=Invalid+CSRF+token');
    exit;
}

$appTitle = 'Stock - Admin';
require __DIR__ . '/../includes/header-admin.php';

$branches = $pdo->query("SELECT branch_id, branch_name FROM branches WHERE status='active' ORDER BY branch_name")->fetchAll();
$products = $pdo->query("SELECT product_id, product_name FROM products WHERE status='active' ORDER BY product_name")->fetchAll();
$rows = $pdo->query("SELECT bs.*, b.branch_name, p.product_name FROM branch_stock bs JOIN branches b ON b.branch_id=bs.branch_id JOIN products p ON p.product_id=bs.product_id ORDER BY b.branch_name, p.product_name")->fetchAll();
$csrf = generate_csrf_token();
?>

<div class="container py-4">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h3 mb-0">Branch Stock</h1>
	</div>

    <?php if (isset($_GET['m'])): ?>
        <div class="alert alert-success py-2">Stock updated.</div>
    <?php endif; ?>
    <?php if (isset($_GET['e'])): ?>
        <div class="alert alert-danger py-2">Error: <?= sanitize($_GET['e']) ?></div>
    <?php endif; ?>

	<!-- Inline Upsert (no modal) -->
	<details class="mb-3">
		<summary class="text-muted" style="cursor:pointer">Add or update stock</summary>
		<div class="card mt-2">
			<div class="card-body py-3">
				<form method="post" class="small">
					<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
					<input type="hidden" name="form_action" value="upsert">
					<div class="row g-2">
						<div class="col-md-4">
							<label class="form-label">Branch</label>
							<select class="form-select form-select-sm" name="branch_id" required>
								<?php foreach ($branches as $b): ?>
								<option value="<?= (int)$b['branch_id'] ?>"><?= sanitize($b['branch_name']) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-md-4">
							<label class="form-label">Product</label>
							<select class="form-select form-select-sm" name="product_id" required>
								<?php foreach ($products as $p): ?>
								<option value="<?= (int)$p['product_id'] ?>"><?= sanitize($p['product_name']) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-md-2">
							<label class="form-label">Quantity (L)</label>
							<input type="number" step="0.01" min="0" class="form-control form-control-sm" name="quantity_liters" required>
						</div>
						<div class="col-md-2">
							<label class="form-label">Minimum (L)</label>
							<input type="number" step="0.01" min="0" class="form-control form-control-sm" name="minimum_stock" required>
						</div>
					</div>
					<div class="mt-3 d-flex gap-2">
						<button class="btn btn-sm btn-primary">Save</button>
					</div>
				</form>
			</div>
		</div>
	</details>

	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead>
				<tr>
					<th>Branch</th>
					<th>Product</th>
					<th>Quantity (L)</th>
					<th>Minimum (L)</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $r): ?>
				<tr>
					<td><?= sanitize($r['branch_name']) ?></td>
					<td><?= sanitize($r['product_name']) ?></td>
					<td><?= number_format((float)$r['quantity_liters'], 2) ?></td>
					<td><?= number_format((float)$r['minimum_stock'], 2) ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="modal fade" id="modalUpsert" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<form method="post">
					<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
					<input type="hidden" name="form_action" value="upsert">
					<div class="modal-header"><h5 class="modal-title">Add/Update Stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
					<div class="modal-body">
						<div class="mb-2">
							<label class="form-label">Branch</label>
							<select class="form-select" name="branch_id" required>
								<?php foreach ($branches as $b): ?>
								<option value="<?= (int)$b['branch_id'] ?>"><?= sanitize($b['branch_name']) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="mb-2">
							<label class="form-label">Product</label>
							<select class="form-select" name="product_id" required>
								<?php foreach ($products as $p): ?>
								<option value="<?= (int)$p['product_id'] ?>"><?= sanitize($p['product_name']) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="mb-2">
							<label class="form-label">Quantity (L)</label>
							<input type="number" step="0.01" min="0" class="form-control" name="quantity_liters" required>
						</div>
						<div class="mb-2">
							<label class="form-label">Minimum (L)</label>
							<input type="number" step="0.01" min="0" class="form-control" name="minimum_stock" required>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<button class="btn btn-primary">Save</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>


