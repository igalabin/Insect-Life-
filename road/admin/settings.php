<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_ADMIN]);

global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
	$action = $_POST['form_action'] ?? '';
if ($action === 'create') {
    $key = trim((string)($_POST['setting_key'] ?? ''));
    // Pre-check duplicate key
    $dupe = $pdo->prepare('SELECT COUNT(*) FROM system_settings WHERE setting_key = :k');
    $dupe->execute([':k' => $key]);
    if ((int)$dupe->fetchColumn() > 0) {
        header('Location: ' . BASE_URL . 'admin/settings.php?e=Setting+key+already+exists');
        exit;
    }
    $stmt = $pdo->prepare('INSERT INTO system_settings (setting_key, setting_value, description) VALUES (:k, :v, :d)');
    try {
        $stmt->execute([
            ':k' => $key,
            ':v' => (string)($_POST['setting_value'] ?? ''),
            ':d' => trim((string)($_POST['description'] ?? '')),
        ]);
    } catch (PDOException $e) {
        header('Location: ' . BASE_URL . 'admin/settings.php?e=Setting+key+already+exists');
        exit;
    }
    header('Location: ' . BASE_URL . 'admin/settings.php?m=created');
    exit;
}
if ($action === 'update') {
    $id = (int)($_POST['setting_id'] ?? 0);
    $key = trim((string)($_POST['setting_key'] ?? ''));
    // Ensure no other row has this key
    $dupe = $pdo->prepare('SELECT COUNT(*) FROM system_settings WHERE setting_key = :k AND setting_id <> :id');
    $dupe->execute([':k' => $key, ':id' => $id]);
    if ((int)$dupe->fetchColumn() > 0) {
        header('Location: ' . BASE_URL . 'admin/settings.php?e=Setting+key+already+in+use');
        exit;
    }
    $stmt = $pdo->prepare('UPDATE system_settings SET setting_key=:k, setting_value=:v, description=:d WHERE setting_id=:id');
    try {
        $stmt->execute([
            ':k' => $key,
            ':v' => (string)($_POST['setting_value'] ?? ''),
            ':d' => trim((string)($_POST['description'] ?? '')),
            ':id' => $id,
        ]);
    } catch (PDOException $e) {
        header('Location: ' . BASE_URL . 'admin/settings.php?e=Update+failed');
        exit;
    }
    header('Location: ' . BASE_URL . 'admin/settings.php?m=updated');
    exit;
}
	if ($action === 'delete') {
		$stmt = $pdo->prepare('DELETE FROM system_settings WHERE setting_id=:id');
		$stmt->execute([':id' => (int)($_POST['setting_id'] ?? 0)]);
		header('Location: ' . BASE_URL . 'admin/settings.php?m=deleted');
		exit;
	}
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . BASE_URL . 'admin/settings.php?e=Invalid+CSRF+token');
    exit;
}

$appTitle = 'Settings - Admin';
require __DIR__ . '/../includes/header-admin.php';

$rows = $pdo->query("SELECT * FROM system_settings ORDER BY setting_key")->fetchAll();
$csrf = generate_csrf_token();
// Inline edit support
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editRow = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM system_settings WHERE setting_id = :id');
    $st->execute([':id' => $editId]);
    $editRow = $st->fetch();
}
?>

<div class="container py-4">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h3 mb-0">System Settings</h1>
	</div>

    <?php if (isset($_GET['m'])): ?>
        <div class="alert alert-success py-2">Action <?= sanitize($_GET['m']) ?> successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['e'])): ?>
        <div class="alert alert-danger py-2">Error: <?= sanitize($_GET['e']) ?></div>
    <?php endif; ?>

	<!-- Inline Create (no modal) -->
	<details class="mb-3">
		<summary class="text-muted" style="cursor:pointer">Add setting</summary>
		<div class="card mt-2">
			<div class="card-body py-3">
				<form method="post" class="small">
					<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
					<input type="hidden" name="form_action" value="create">
					<div class="row g-2">
						<div class="col-md-4">
							<label class="form-label">Key</label>
							<input class="form-control form-control-sm" name="setting_key" required>
						</div>
						<div class="col-md-4">
							<label class="form-label">Value</label>
							<input class="form-control form-control-sm" name="setting_value" required>
						</div>
						<div class="col-md-4">
							<label class="form-label">Description</label>
							<input class="form-control form-control-sm" name="description">
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
			<h6 class="card-title mb-3 text-muted">Editing setting #<?= (int)$editRow['setting_id'] ?></h6>
			<form method="post" class="small">
				<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
				<input type="hidden" name="form_action" value="update">
				<input type="hidden" name="setting_id" value="<?= (int)$editRow['setting_id'] ?>">
				<div class="row g-2">
					<div class="col-md-4">
						<label class="form-label">Key</label>
						<input class="form-control form-control-sm" name="setting_key" required value="<?= sanitize($editRow['setting_key']) ?>">
					</div>
					<div class="col-md-4">
						<label class="form-label">Value</label>
						<input class="form-control form-control-sm" name="setting_value" required value="<?= sanitize($editRow['setting_value']) ?>">
					</div>
					<div class="col-md-4">
						<label class="form-label">Description</label>
						<input class="form-control form-control-sm" name="description" value="<?= sanitize((string)($editRow['description'] ?? '')) ?>">
					</div>
				</div>
				<div class="mt-3 d-flex gap-2">
					<button class="btn btn-sm btn-primary">Save changes</button>
					<a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>admin/settings.php">Cancel</a>
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
					<th>Key</th>
					<th>Value</th>
					<th>Description</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $r): ?>
				<tr>
					<td><?= (int)$r['setting_id'] ?></td>
					<td><code><?= sanitize($r['setting_key']) ?></code></td>
					<td><?= sanitize($r['setting_value']) ?></td>
					<td><?= sanitize((string)($r['description'] ?? '')) ?></td>
					<td>
					<a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>admin/settings.php?edit_id=<?= (int)$r['setting_id'] ?>">Edit</a>
						<form class="d-inline" method="post" onsubmit="return confirm('Delete this setting?')">
							<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
							<input type="hidden" name="form_action" value="delete">
							<input type="hidden" name="setting_id" value="<?= (int)$r['setting_id'] ?>">
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


