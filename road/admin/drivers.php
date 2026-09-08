<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_ADMIN]);

global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
	$action = $_POST['form_action'] ?? '';
	if ($action === 'create') {
		$hash = password_hash((string)($_POST['password'] ?? ''), PASSWORD_DEFAULT);
		$stmt = $pdo->prepare('INSERT INTO drivers (username, password, full_name, email, phone, license_number, address, status) VALUES (:u, :p, :n, :e, :ph, :ln, :ad, :s)');
		$stmt->execute([
			':u' => trim((string)($_POST['username'] ?? '')),
			':p' => $hash,
			':n' => trim((string)($_POST['full_name'] ?? '')),
			':e' => trim((string)($_POST['email'] ?? '')),
			':ph' => trim((string)($_POST['phone'] ?? '')),
			':ln' => trim((string)($_POST['license_number'] ?? '')),
			':ad' => trim((string)($_POST['address'] ?? '')),
			':s' => trim((string)($_POST['status'] ?? 'active')),
		]);
		header('Location: ' . BASE_URL . 'admin/drivers.php?m=created');
		exit;
	}
	if ($action === 'update') {
		$params = [
			':n' => trim((string)($_POST['full_name'] ?? '')),
			':e' => trim((string)($_POST['email'] ?? '')),
			':ph' => trim((string)($_POST['phone'] ?? '')),
			':ln' => trim((string)($_POST['license_number'] ?? '')),
			':ad' => trim((string)($_POST['address'] ?? '')),
			':s' => trim((string)($_POST['status'] ?? 'active')),
			':id' => (int)($_POST['driver_id'] ?? 0),
		];
		$sql = 'UPDATE drivers SET full_name=:n, email=:e, phone=:ph, license_number=:ln, address=:ad, status=:s';
		if (($_POST['password'] ?? '') !== '') {
			$sql .= ', password=:pw';
			$params[':pw'] = password_hash((string)$_POST['password'], PASSWORD_DEFAULT);
		}
		$sql .= ' WHERE driver_id=:id';
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
		header('Location: ' . BASE_URL . 'admin/drivers.php?m=updated');
		exit;
	}
	if ($action === 'delete') {
		$stmt = $pdo->prepare('DELETE FROM drivers WHERE driver_id=:id');
		$stmt->execute([':id' => (int)($_POST['driver_id'] ?? 0)]);
		header('Location: ' . BASE_URL . 'admin/drivers.php?m=deleted');
		exit;
	}
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . BASE_URL . 'admin/drivers.php?e=Invalid+CSRF+token');
    exit;
}

$appTitle = 'Drivers - Admin';
require __DIR__ . '/../includes/header-admin.php';

$rows = $pdo->query("SELECT * FROM drivers ORDER BY driver_id DESC")->fetchAll();
$csrf = generate_csrf_token();
// Inline edit support
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editRow = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM drivers WHERE driver_id = :id');
    $st->execute([':id' => $editId]);
    $editRow = $st->fetch();
}
?>

<div class="container py-4">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h3 mb-0">Drivers</h1>
	</div>

    <?php if (isset($_GET['m'])): ?>
        <div class="alert alert-success py-2">Action <?= sanitize($_GET['m']) ?> successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['e'])): ?>
        <div class="alert alert-danger py-2">Error: <?= sanitize($_GET['e']) ?></div>
    <?php endif; ?>

	<!-- Inline Create (no modal) -->
	<details class="mb-3">
		<summary class="text-muted" style="cursor:pointer">Add driver</summary>
		<div class="card mt-2">
			<div class="card-body py-3">
				<form method="post" class="small">
					<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
					<input type="hidden" name="form_action" value="create">
					<div class="row g-2">
						<div class="col-md-3">
							<label class="form-label">Username</label>
							<input class="form-control form-control-sm" name="username" required>
						</div>
						<div class="col-md-3">
							<label class="form-label">Full Name</label>
							<input class="form-control form-control-sm" name="full_name" required>
						</div>
						<div class="col-md-3">
							<label class="form-label">Email</label>
							<input type="email" class="form-control form-control-sm" name="email" required>
						</div>
						<div class="col-md-3">
							<label class="form-label">Phone</label>
							<input class="form-control form-control-sm" name="phone" required>
						</div>
						<div class="col-md-3">
							<label class="form-label">License Number</label>
							<input class="form-control form-control-sm" name="license_number" required>
						</div>
						<div class="col-md-5">
							<label class="form-label">Address</label>
							<input class="form-control form-control-sm" name="address" required>
						</div>
						<div class="col-md-2">
							<label class="form-label">Status</label>
							<select class="form-select form-select-sm" name="status">
								<option value="active">Active</option>
								<option value="inactive">Inactive</option>
								<option value="suspended">Suspended</option>
							</select>
						</div>
						<div class="col-md-2">
							<label class="form-label">Password</label>
							<input type="password" class="form-control form-control-sm" name="password" required>
						</div>
					</div>
					<div class="mt-3 d-flex gap-2">
						<button class="btn btn-sm btn-primary">Create</button>
					</div>
				</form>
			</div>
		</div>
	</details>

	<!-- Inline Edit (no modal) -->
	<?php if ($editRow): ?>
	<div class="card mb-3 border-secondary">
		<div class="card-body py-3">
			<h6 class="card-title mb-3 text-muted">Editing driver #<?= (int)$editRow['driver_id'] ?></h6>
			<form method="post" class="small">
				<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
				<input type="hidden" name="form_action" value="update">
				<input type="hidden" name="driver_id" value="<?= (int)$editRow['driver_id'] ?>">
				<div class="row g-2">
					<div class="col-md-4">
						<label class="form-label">Full Name</label>
						<input class="form-control form-control-sm" name="full_name" required value="<?= sanitize($editRow['full_name']) ?>">
					</div>
					<div class="col-md-4">
						<label class="form-label">Email</label>
						<input type="email" class="form-control form-control-sm" name="email" required value="<?= sanitize($editRow['email']) ?>">
					</div>
					<div class="col-md-4">
						<label class="form-label">Phone</label>
						<input class="form-control form-control-sm" name="phone" required value="<?= sanitize($editRow['phone']) ?>">
					</div>
					<div class="col-md-4">
						<label class="form-label">License Number</label>
						<input class="form-control form-control-sm" name="license_number" required value="<?= sanitize($editRow['license_number']) ?>">
					</div>
					<div class="col-md-6">
						<label class="form-label">Address</label>
						<input class="form-control form-control-sm" name="address" required value="<?= sanitize($editRow['address']) ?>">
					</div>
					<div class="col-md-2">
						<label class="form-label">Status</label>
						<select class="form-select form-select-sm" name="status">
							<option value="active" <?= $editRow['status']==='active'?'selected':'' ?>>Active</option>
							<option value="inactive" <?= $editRow['status']==='inactive'?'selected':'' ?>>Inactive</option>
							<option value="suspended" <?= $editRow['status']==='suspended'?'selected':'' ?>>Suspended</option>
						</select>
					</div>
					<div class="col-md-12">
						<label class="form-label">Password (leave blank to keep)</label>
						<input type="password" class="form-control form-control-sm" name="password">
					</div>
				</div>
				<div class="mt-3 d-flex gap-2">
					<button class="btn btn-sm btn-primary">Save changes</button>
					<a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>admin/drivers.php">Cancel</a>
				</div>
			</form>
		</div>
	</div>
	<?php endif; ?>

	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead>
				<tr>
					<th>ID</th>
					<th>Username</th>
					<th>Name</th>
					<th>Email</th>
					<th>Phone</th>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $r): ?>
				<tr>
					<td><?= (int)$r['driver_id'] ?></td>
					<td><?= sanitize($r['username']) ?></td>
					<td><?= sanitize($r['full_name']) ?></td>
					<td><?= sanitize($r['email']) ?></td>
					<td><?= sanitize($r['phone']) ?></td>
					<td><span class="badge bg-<?= $r['status']==='active'?'success':'secondary' ?>"><?= sanitize($r['status']) ?></span></td>
					<td>
						<a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>admin/drivers.php?edit_id=<?= (int)$r['driver_id'] ?>">Edit</a>
						<form class="d-inline" method="post" onsubmit="return confirm('Delete this driver?')">
							<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
							<input type="hidden" name="form_action" value="delete">
							<input type="hidden" name="driver_id" value="<?= (int)$r['driver_id'] ?>">
							<button class="btn btn-sm btn-outline-danger">Delete</button>
						</form>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<form method="post">
					<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
					<input type="hidden" name="form_action" value="create">
					<div class="modal-header"><h5 class="modal-title">Add Driver</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
					<div class="modal-body">
						<div class="mb-2">
							<label class="form-label">Username</label>
							<input class="form-control" name="username" required>
						</div>
						<div class="mb-2">
							<label class="form-label">Full Name</label>
							<input class="form-control" name="full_name" required>
						</div>
						<div class="mb-2">
							<label class="form-label">Email</label>
							<input type="email" class="form-control" name="email" required>
						</div>
						<div class="mb-2">
							<label class="form-label">Phone</label>
							<input class="form-control" name="phone" required>
						</div>
						<div class="mb-2">
							<label class="form-label">License Number</label>
							<input class="form-control" name="license_number" required>
						</div>
						<div class="mb-2">
							<label class="form-label">Address</label>
							<input class="form-control" name="address" required>
						</div>
						<div class="mb-2">
							<label class="form-label">Status</label>
							<select class="form-select" name="status">
								<option value="active">Active</option>
								<option value="inactive">Inactive</option>
								<option value="suspended">Suspended</option>
							</select>
						</div>
						<div class="mb-2">
							<label class="form-label">Password</label>
							<input type="password" class="form-control" name="password" required>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<button class="btn btn-primary">Create</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>


