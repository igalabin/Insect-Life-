<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_ADMIN]);

global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
	$action = $_POST['form_action'] ?? '';
if ($action === 'create') {
    $username = trim((string)($_POST['username'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    // Pre-check duplicates for clearer message
    $dupe = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :u OR email = :e');
    $dupe->execute([':u' => $username, ':e' => $email]);
    if ((int)$dupe->fetchColumn() > 0) {
        header('Location: ' . BASE_URL . 'admin/users.php?e=Username+or+email+already+exists');
        exit;
    }
    $hash = password_hash((string)($_POST['password'] ?? ''), PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password, full_name, email, phone, role, branch_id, status) VALUES (:u, :p, :n, :e, :ph, :r, :b, :s)');
    try {
        $stmt->execute([
            ':u' => $username,
            ':p' => $hash,
            ':n' => trim((string)($_POST['full_name'] ?? '')),
            ':e' => $email,
            ':ph' => trim((string)($_POST['phone'] ?? '')),
            ':r' => trim((string)($_POST['role'] ?? ROLE_STAFF)),
            ':b' => ($_POST['branch_id'] ?? '') === '' ? null : (int)$_POST['branch_id'],
            ':s' => trim((string)($_POST['status'] ?? 'active')),
        ]);
    } catch (PDOException $e) {
        header('Location: ' . BASE_URL . 'admin/users.php?e=Username+or+email+already+exists');
        exit;
    }
    header('Location: ' . BASE_URL . 'admin/users.php?m=created');
    exit;
}
	if ($action === 'update') {
		$params = [
			':n' => trim((string)($_POST['full_name'] ?? '')),
			':e' => trim((string)($_POST['email'] ?? '')),
			':ph' => trim((string)($_POST['phone'] ?? '')),
			':r' => trim((string)($_POST['role'] ?? ROLE_STAFF)),
			':b' => ($_POST['branch_id'] ?? '') === '' ? null : (int)$_POST['branch_id'],
			':s' => trim((string)($_POST['status'] ?? 'active')),
			':id' => (int)($_POST['user_id'] ?? 0),
		];
		$sql = 'UPDATE users SET full_name=:n, email=:e, phone=:ph, role=:r, branch_id=:b, status=:s';
		if (($_POST['password'] ?? '') !== '') {
			$sql .= ', password=:pw';
			$params[':pw'] = password_hash((string)$_POST['password'], PASSWORD_DEFAULT);
		}
		$sql .= ' WHERE user_id=:id';
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
		header('Location: ' . BASE_URL . 'admin/users.php?m=updated');
		exit;
	}
	if ($action === 'delete') {
		$stmt = $pdo->prepare('DELETE FROM users WHERE user_id=:id AND role!=:admin');
		$stmt->execute([':id' => (int)($_POST['user_id'] ?? 0), ':admin' => ROLE_ADMIN]);
		header('Location: ' . BASE_URL . 'admin/users.php?m=deleted');
		exit;
	}
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . BASE_URL . 'admin/users.php?e=Invalid+CSRF+token');
    exit;
}

$appTitle = 'Users - Admin';
require __DIR__ . '/../includes/header-admin.php';

$users = $pdo->query("SELECT u.*, b.branch_name FROM users u LEFT JOIN branches b ON b.branch_id = u.branch_id ORDER BY u.user_id DESC")->fetchAll();
$branches = $pdo->query("SELECT branch_id, branch_name FROM branches WHERE status='active' ORDER BY branch_name")->fetchAll();
$csrf = generate_csrf_token();
// Inline edit support
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editRow = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM users WHERE user_id = :id');
    $st->execute([':id' => $editId]);
    $editRow = $st->fetch();
}
?>

<div class="container py-4">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h3 mb-0">Users</h1>
	</div>

    <?php if (isset($_GET['m'])): ?>
        <div class="alert alert-success py-2">Action <?= sanitize($_GET['m']) ?> successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['e'])): ?>
        <div class="alert alert-danger py-2">Error: <?= sanitize($_GET['e']) ?></div>
    <?php endif; ?>

	<!-- Inline Create (no modal) -->
	<details class="mb-3">
		<summary class="text-muted" style="cursor:pointer">Add user</summary>
		<div class="card mt-2">
			<div class="card-body py-3">
				<form method="post" class="small">
					<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
					<input type="hidden" name="form_action" value="create">
					<div class="row g-2">
						<div class="col-md-4">
							<label class="form-label">Username</label>
							<input class="form-control form-control-sm" name="username" required>
						</div>
						<div class="col-md-4">
							<label class="form-label">Full Name</label>
							<input class="form-control form-control-sm" name="full_name" required>
						</div>
						<div class="col-md-4">
							<label class="form-label">Email</label>
							<input type="email" class="form-control form-control-sm" name="email" required>
						</div>
						<div class="col-md-4">
							<label class="form-label">Phone</label>
							<input class="form-control form-control-sm" name="phone" required>
						</div>
						<div class="col-md-4">
							<label class="form-label">Role</label>
							<select class="form-select form-select-sm" name="role">
								<option value="staff">Staff</option>
								<option value="admin">Admin</option>
							</select>
						</div>
						<div class="col-md-4">
							<label class="form-label">Branch (optional)</label>
							<select class="form-select form-select-sm" name="branch_id">
								<option value="">— None —</option>
								<?php foreach ($branches as $b): ?>
								<option value="<?= (int)$b['branch_id'] ?>"><?= sanitize($b['branch_name']) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="col-md-4">
							<label class="form-label">Status</label>
							<select class="form-select form-select-sm" name="status">
								<option value="active">Active</option>
								<option value="inactive">Inactive</option>
							</select>
						</div>
						<div class="col-md-4">
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
			<h6 class="card-title mb-3 text-muted">Editing user #<?= (int)$editRow['user_id'] ?></h6>
			<form method="post" class="small">
				<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
				<input type="hidden" name="form_action" value="update">
				<input type="hidden" name="user_id" value="<?= (int)$editRow['user_id'] ?>">
				<div class="row g-2">
					<div class="col-md-3">
						<label class="form-label">Full Name</label>
						<input class="form-control form-control-sm" name="full_name" required value="<?= sanitize($editRow['full_name']) ?>">
					</div>
					<div class="col-md-3">
						<label class="form-label">Email</label>
						<input type="email" class="form-control form-control-sm" name="email" required value="<?= sanitize($editRow['email']) ?>">
					</div>
					<div class="col-md-2">
						<label class="form-label">Phone</label>
						<input class="form-control form-control-sm" name="phone" required value="<?= sanitize($editRow['phone']) ?>">
					</div>
					<div class="col-md-2">
						<label class="form-label">Role</label>
						<select class="form-select form-select-sm" name="role">
							<option value="staff" <?= $editRow['role']==='staff'?'selected':'' ?>>Staff</option>
							<option value="admin" <?= $editRow['role']==='admin'?'selected':'' ?>>Admin</option>
						</select>
					</div>
					<div class="col-md-2">
						<label class="form-label">Branch</label>
						<select class="form-select form-select-sm" name="branch_id">
							<option value="">— None —</option>
							<?php foreach ($branches as $b): ?>
							<option value="<?= (int)$b['branch_id'] ?>" <?= ((int)($editRow['branch_id'] ?? 0) === (int)$b['branch_id'])?'selected':'' ?>><?= sanitize($b['branch_name']) ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-12">
						<label class="form-label">Status</label>
						<select class="form-select form-select-sm" name="status">
							<option value="active" <?= $editRow['status']==='active'?'selected':'' ?>>Active</option>
							<option value="inactive" <?= $editRow['status']==='inactive'?'selected':'' ?>>Inactive</option>
						</select>
					</div>
					<div class="col-12">
						<label class="form-label">Password (leave blank to keep)</label>
						<input type="password" class="form-control form-control-sm" name="password">
					</div>
				</div>
				<div class="mt-3 d-flex gap-2">
					<button class="btn btn-sm btn-primary">Save changes</button>
					<a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>admin/users.php">Cancel</a>
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
					<th>Role</th>
					<th>Branch</th>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($users as $u): ?>
				<tr>
					<td><?= (int)$u['user_id'] ?></td>
					<td><?= sanitize($u['username']) ?></td>
					<td><?= sanitize($u['full_name']) ?></td>
					<td><?= sanitize($u['role']) ?></td>
					<td><?= sanitize((string)($u['branch_name'] ?? '')) ?></td>
					<td><span class="badge bg-<?= $u['status']==='active'?'success':'secondary' ?>"><?= sanitize($u['status']) ?></span></td>
					<td>
						<a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>admin/users.php?edit_id=<?= (int)$u['user_id'] ?>">Edit</a>
						<form class="d-inline" method="post" onsubmit="return confirm('Delete this user?')">
							<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
							<input type="hidden" name="form_action" value="delete">
							<input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
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
					<div class="modal-header"><h5 class="modal-title">Add User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
							<label class="form-label">Role</label>
							<select class="form-select" name="role">
								<option value="staff">Staff</option>
								<option value="admin">Admin</option>
							</select>
						</div>
						<div class="mb-2">
							<label class="form-label">Branch (optional)</label>
							<select class="form-select" name="branch_id">
								<option value="">— None —</option>
								<?php foreach ($branches as $b): ?>
								<option value="<?= (int)$b['branch_id'] ?>"><?= sanitize($b['branch_name']) ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="mb-2">
							<label class="form-label">Status</label>
							<select class="form-select" name="status">
								<option value="active">Active</option>
								<option value="inactive">Inactive</option>
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


