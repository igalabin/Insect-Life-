<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_ADMIN]);

global $pdo;

// Handle create/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
	$action = $_POST['form_action'] ?? '';
	if ($action === 'create') {
		$stmt = $pdo->prepare('INSERT INTO products (product_name, fuel_type, description, price_per_liter, status) VALUES (:n, :f, :d, :p, :s)');
		$stmt->execute([
			':n' => trim((string)($_POST['product_name'] ?? '')),
			':f' => trim((string)($_POST['fuel_type'] ?? 'unleaded')),
			':d' => trim((string)($_POST['description'] ?? '')),
			':p' => (float)($_POST['price_per_liter'] ?? 0),
			':s' => trim((string)($_POST['status'] ?? 'active')),
		]);
		header('Location: ' . BASE_URL . 'admin/products.php?m=created');
		exit;
	}
	if ($action === 'update') {
		$stmt = $pdo->prepare('UPDATE products SET product_name=:n, fuel_type=:f, description=:d, price_per_liter=:p, status=:s WHERE product_id=:id');
		$stmt->execute([
			':n' => trim((string)($_POST['product_name'] ?? '')),
			':f' => trim((string)($_POST['fuel_type'] ?? 'unleaded')),
			':d' => trim((string)($_POST['description'] ?? '')),
			':p' => (float)($_POST['price_per_liter'] ?? 0),
			':s' => trim((string)($_POST['status'] ?? 'active')),
			':id' => (int)($_POST['product_id'] ?? 0),
		]);
		header('Location: ' . BASE_URL . 'admin/products.php?m=updated');
		exit;
	}
	if ($action === 'delete') {
		$stmt = $pdo->prepare('DELETE FROM products WHERE product_id = :id');
		$stmt->execute([':id' => (int)($_POST['product_id'] ?? 0)]);
		header('Location: ' . BASE_URL . 'admin/products.php?m=deleted');
		exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . BASE_URL . 'admin/products.php?e=Invalid+CSRF+token');
    exit;
}

$appTitle = 'Products - Admin';
require __DIR__ . '/../includes/header-admin.php';

$rows = $pdo->query("SELECT * FROM products ORDER BY product_id DESC")->fetchAll();
$csrf = generate_csrf_token();

// If editing, fetch the product
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editRow = null;
if ($editId > 0) {
	$st = $pdo->prepare('SELECT * FROM products WHERE product_id = :id');
	$st->execute([':id' => $editId]);
	$editRow = $st->fetch();
}
?>

<div class="container py-4">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h3 mb-0">Products</h1>
	</div>

    <?php if (isset($_GET['m'])): ?>
        <div class="alert alert-success py-2">Action <?= sanitize($_GET['m']) ?> successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['e'])): ?>
        <div class="alert alert-danger py-2">Error: <?= sanitize($_GET['e']) ?></div>
    <?php endif; ?>

	<!-- Inline Create/Edit Form (no modals) -->
	<?php if ($editRow): ?>
	<div class="card mb-3 border-secondary">
		<div class="card-body py-3">
			<h6 class="card-title mb-3 text-muted">Editing product #<?= (int)$editRow['product_id'] ?></h6>
			<form method="post" class="small">
				<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
				<input type="hidden" name="form_action" value="update">
				<input type="hidden" name="product_id" value="<?= (int)$editRow['product_id'] ?>">
				<div class="row g-2">
					<div class="col-md-4">
						<label class="form-label">Name</label>
						<input class="form-control form-control-sm" name="product_name" required value="<?= sanitize($editRow['product_name'] ?? '') ?>">
					</div>
					<div class="col-md-3">
						<label class="form-label">Fuel Type</label>
						<select class="form-select form-select-sm" name="fuel_type">
							<?php foreach (['unleaded','premium','diesel','kerosene','special'] as $ft): ?>
							<option value="<?= $ft ?>" <?= (($editRow['fuel_type'] ?? '')===$ft)?'selected':'' ?>><?= ucfirst($ft) ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-3">
						<label class="form-label">Price per liter</label>
						<input type="number" step="0.01" min="0" class="form-control form-control-sm" name="price_per_liter" required value="<?= number_format((float)$editRow['price_per_liter'], 2, '.', '') ?>">
					</div>
					<div class="col-md-2">
						<label class="form-label">Status</label>
						<select class="form-select form-select-sm" name="status">
							<option value="active" <?= (($editRow['status'] ?? 'active')==='active')?'selected':'' ?>>Active</option>
							<option value="inactive" <?= (($editRow['status'] ?? '')==='inactive')?'selected':'' ?>>Inactive</option>
						</select>
					</div>
					<div class="col-12">
						<label class="form-label">Description</label>
						<textarea class="form-control form-control-sm" name="description" rows="2"><?= sanitize((string)($editRow['description'] ?? '')) ?></textarea>
					</div>
				</div>
				<div class="mt-3 d-flex gap-2">
					<button class="btn btn-sm btn-primary">Save changes</button>
					<a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>admin/products.php">Cancel</a>
				</div>
			</form>
		</div>
	</div>
	<?php else: ?>
	<details class="mb-3">
		<summary class="text-muted" style="cursor:pointer">Add product</summary>
		<div class="card mt-2">
			<div class="card-body py-3">
				<form method="post" class="small">
					<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
					<input type="hidden" name="form_action" value="create">
					<div class="row g-2">
						<div class="col-md-4">
							<label class="form-label">Name</label>
							<input class="form-control form-control-sm" name="product_name" required>
						</div>
						<div class="col-md-3">
							<label class="form-label">Fuel Type</label>
							<select class="form-select form-select-sm" name="fuel_type">
								<?php foreach (['unleaded','premium','diesel','kerosene','special'] as $ft): ?>
								<option value="<?= $ft ?>"><?= ucfirst($ft) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-md-3">
							<label class="form-label">Price per liter</label>
							<input type="number" step="0.01" min="0" class="form-control form-control-sm" name="price_per_liter" required>
						</div>
						<div class="col-md-2">
							<label class="form-label">Status</label>
							<select class="form-select form-select-sm" name="status">
								<option value="active">Active</option>
								<option value="inactive">Inactive</option>
							</select>
						</div>
						<div class="col-12">
							<label class="form-label">Description</label>
							<textarea class="form-control form-control-sm" name="description" rows="2"></textarea>
						</div>
					</div>
					<div class="mt-3 d-flex gap-2">
						<button class="btn btn-sm btn-primary">Create</button>
					</div>
				</form>
			</div>
		</div>
	</details>
	<?php endif; ?>

	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead>
				<tr>
					<th>ID</th>
					<th>Name</th>
					<th>Fuel Type</th>
					<th>Price/Liter</th>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $r): ?>
				<tr>
					<td><?= (int)$r['product_id'] ?></td>
					<td><?= sanitize($r['product_name']) ?></td>
					<td><?= sanitize($r['fuel_type']) ?></td>
					<td>₱<?= number_format((float)$r['price_per_liter'], 2) ?></td>
					<td><span class="badge bg-<?= $r['status']==='active'?'success':'secondary' ?>"><?= sanitize($r['status']) ?></span></td>
					<td>
						<a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>admin/products.php?edit_id=<?= (int)$r['product_id'] ?>">Edit</a>
						<form class="d-inline" method="post" onsubmit="return confirm('Delete this product?')">
							<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
							<input type="hidden" name="form_action" value="delete">
							<input type="hidden" name="product_id" value="<?= (int)$r['product_id'] ?>">
							<button class="btn btn-sm btn-outline-danger">Delete</button>
						</form>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>


