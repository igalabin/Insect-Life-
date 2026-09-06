<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_ADMIN]);

global $pdo;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validate_csrf_token($_POST['csrf_token'] ?? '')) {
	$action = $_POST['form_action'] ?? '';
	if ($action === 'create') {
		$stmt = $pdo->prepare('INSERT INTO branches (branch_name, branch_code, address, city, province, latitude, longitude, contact_number, status) VALUES (:n, :c, :a, :city, :prov, :lat, :lng, :phone, :s)');
		$stmt->execute([
			':n' => trim((string)($_POST['branch_name'] ?? '')),
			':c' => trim((string)($_POST['branch_code'] ?? '')),
			':a' => trim((string)($_POST['address'] ?? '')),
			':city' => trim((string)($_POST['city'] ?? '')),
			':prov' => trim((string)($_POST['province'] ?? '')),
			':lat' => (float)($_POST['latitude'] ?? 0),
			':lng' => (float)($_POST['longitude'] ?? 0),
			':phone' => trim((string)($_POST['contact_number'] ?? '')),
			':s' => trim((string)($_POST['status'] ?? 'active')),
		]);
		header('Location: ' . BASE_URL . 'admin/branches.php?m=created');
		exit;
	}
	if ($action === 'update') {
		$stmt = $pdo->prepare('UPDATE branches SET branch_name=:n, branch_code=:c, address=:a, city=:city, province=:prov, latitude=:lat, longitude=:lng, contact_number=:phone, status=:s WHERE branch_id=:id');
		$stmt->execute([
			':n' => trim((string)($_POST['branch_name'] ?? '')),
			':c' => trim((string)($_POST['branch_code'] ?? '')),
			':a' => trim((string)($_POST['address'] ?? '')),
			':city' => trim((string)($_POST['city'] ?? '')),
			':prov' => trim((string)($_POST['province'] ?? '')),
			':lat' => (float)($_POST['latitude'] ?? 0),
			':lng' => (float)($_POST['longitude'] ?? 0),
			':phone' => trim((string)($_POST['contact_number'] ?? '')),
			':s' => trim((string)($_POST['status'] ?? 'active')),
			':id' => (int)($_POST['branch_id'] ?? 0),
		]);
		header('Location: ' . BASE_URL . 'admin/branches.php?m=updated');
		exit;
	}
	if ($action === 'delete') {
		$stmt = $pdo->prepare('DELETE FROM branches WHERE branch_id = :id');
		$stmt->execute([':id' => (int)($_POST['branch_id'] ?? 0)]);
		header('Location: ' . BASE_URL . 'admin/branches.php?m=deleted');
		exit;
	}
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ' . BASE_URL . 'admin/branches.php?e=Invalid+CSRF+token');
    exit;
}

$appTitle = 'Branches - Admin';
require __DIR__ . '/../includes/header-admin.php';

$rows = $pdo->query("SELECT * FROM branches ORDER BY branch_id DESC")->fetchAll();
$csrf = generate_csrf_token();
// Inline edit support
$editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$editRow = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM branches WHERE branch_id = :id');
    $st->execute([':id' => $editId]);
    $editRow = $st->fetch();
}
?>

<div class="container py-4">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<h1 class="h3 mb-0">Branches</h1>
	</div>

    <?php if (isset($_GET['m'])): ?>
        <div class="alert alert-success py-2">Action <?= sanitize($_GET['m']) ?> successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['e'])): ?>
        <div class="alert alert-danger py-2">Error: <?= sanitize($_GET['e']) ?></div>
    <?php endif; ?>

	<!-- Inline Create (no modal) -->
	<details class="mb-3">
		<summary class="text-muted" style="cursor:pointer">Add branch</summary>
		<div class="card mt-2">
			<div class="card-body py-3">
				<form method="post" class="small">
					<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
					<input type="hidden" name="form_action" value="create">
					<div class="row g-2">
						<div class="col-md-6">
							<label class="form-label">Name</label>
							<input class="form-control form-control-sm" name="branch_name" required>
						</div>
						<div class="col-md-6">
							<label class="form-label">Code</label>
							<input class="form-control form-control-sm" name="branch_code" required>
						</div>
						<div class="col-12">
							<label class="form-label">Address</label>
							<input class="form-control form-control-sm" name="address" required>
						</div>
						<div class="col-md-4">
							<label class="form-label">City</label>
							<input class="form-control form-control-sm" name="city" required>
						</div>
						<div class="col-md-4">
							<label class="form-label">Province</label>
							<input class="form-control form-control-sm" name="province" required>
						</div>
						<div class="col-12">
							<div class="d-flex align-items-center justify-content-between">
								<label class="form-label mb-1">Location (pick on map)</label>
								<button type="button" id="btnCreateUseMyLoc" class="btn btn-sm btn-outline-secondary">Use my location</button>
							</div>
							<div id="createBranchMap" class="border rounded" style="height:240px;background:#f3f0ff"></div>
						</div>
						<div class="col-md-2">
							<label class="form-label">Latitude</label>
							<input type="number" step="0.00000001" class="form-control form-control-sm" name="latitude" required readonly>
						</div>
						<div class="col-md-2">
							<label class="form-label">Longitude</label>
							<input type="number" step="0.00000001" class="form-control form-control-sm" name="longitude" required readonly>
						</div>
						<div class="col-md-6">
							<label class="form-label">Contact</label>
							<input class="form-control form-control-sm" name="contact_number" required>
						</div>
						<div class="col-md-6">
							<label class="form-label">Status</label>
							<select class="form-select form-select-sm" name="status">
								<option value="active">Active</option>
								<option value="inactive">Inactive</option>
							</select>
						</div>
					</div>
					<div class="mt-3 d-flex gap-2">
						<button class="btn btn-sm btn-primary">Create</button>
					</div>
				</form>
			</div>
		</div>
	</details>

	<!-- Leaflet for create map (CDN first, fallback local) -->
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" onerror="this.remove()"/>
	<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/leaflet.css">
	<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" onerror="this.remove()"></script>
	<script src="<?= BASE_URL ?>assets/js/leaflet.js"></script>
	<script src="<?= BASE_URL ?>assets/js/emoji-markers.js"></script>
	<script>
	(function(){
		var mapEl = document.getElementById('createBranchMap');
		if (!mapEl || typeof L === 'undefined') return;
		var latInput = document.querySelector('input[name="latitude"]');
		var lngInput = document.querySelector('input[name="longitude"]');
		var start = { lat: 14.5995, lng: 120.9842 }; // Manila default
		var map = L.map('createBranchMap').setView([start.lat, start.lng], 12);
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
		
		// Use emoji marker for branch creation
		var marker;
		if (typeof createEmojiMarker !== 'undefined') {
			marker = createEmojiMarker(start.lat, start.lng, '🏢', { draggable: true }).addTo(map);
		} else {
			marker = L.marker([start.lat, start.lng], { draggable: true }).addTo(map);
		}
		function updateInputs(latlng){ if(latInput){latInput.value = latlng.lat.toFixed(8);} if(lngInput){lngInput.value = latlng.lng.toFixed(8);} }
		updateInputs(marker.getLatLng());
		marker.on('dragend', function(){ updateInputs(marker.getLatLng()); });
		map.on('click', function(e){ marker.setLatLng(e.latlng); updateInputs(e.latlng); });
		var btn = document.getElementById('btnCreateUseMyLoc');
		if (btn && navigator.geolocation) {
			btn.addEventListener('click', function(ev){ ev.preventDefault(); navigator.geolocation.getCurrentPosition(function(pos){ var ll = {lat: pos.coords.latitude, lng: pos.coords.longitude}; map.setView(ll, 14); marker.setLatLng(ll); updateInputs(ll); }); });
		}
	})();
	</script>

    <!-- Inline Edit (no modal) -->
    <?php if ($editRow): ?>
    <div class="card mb-3 border-secondary">
        <div class="card-body py-3">
            <h6 class="card-title mb-3 text-muted">Editing branch #<?= (int)$editRow['branch_id'] ?></h6>
            <form method="post" class="small">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="form_action" value="update">
                <input type="hidden" name="branch_id" value="<?= (int)$editRow['branch_id'] ?>">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input class="form-control form-control-sm" name="branch_name" required value="<?= sanitize($editRow['branch_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Code</label>
                        <input class="form-control form-control-sm" name="branch_code" required value="<?= sanitize($editRow['branch_code']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <input class="form-control form-control-sm" name="address" required value="<?= sanitize($editRow['address']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input class="form-control form-control-sm" name="city" required value="<?= sanitize($editRow['city']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Province</label>
                        <input class="form-control form-control-sm" name="province" required value="<?= sanitize($editRow['province']) ?>">
                    </div>
					<div class="col-12">
						<div class="d-flex align-items-center justify-content-between">
							<label class="form-label mb-1">Location (pick on map)</label>
							<button type="button" id="btnEditUseMyLoc" class="btn btn-sm btn-outline-secondary">Use my location</button>
						</div>
						<div id="editBranchMap" class="border rounded" style="height:240px;background:#f3f0ff"></div>
					</div>
					<div class="col-md-2">
						<label class="form-label">Latitude</label>
						<input type="number" step="0.00000001" class="form-control form-control-sm" name="latitude" required readonly value="<?= number_format((float)$editRow['latitude'], 8, '.', '') ?>">
					</div>
					<div class="col-md-2">
						<label class="form-label">Longitude</label>
						<input type="number" step="0.00000001" class="form-control form-control-sm" name="longitude" required readonly value="<?= number_format((float)$editRow['longitude'], 8, '.', '') ?>">
					</div>
                    <div class="col-md-6">
                        <label class="form-label">Contact</label>
                        <input class="form-control form-control-sm" name="contact_number" required value="<?= sanitize($editRow['contact_number']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select class="form-select form-select-sm" name="status">
                            <option value="active" <?= $editRow['status']==='active'?'selected':'' ?>>Active</option>
                            <option value="inactive" <?= $editRow['status']==='inactive'?'selected':'' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-sm btn-primary">Save changes</button>
                    <a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>admin/branches.php">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

	<script>
	(function(){
		var mapEl = document.getElementById('editBranchMap');
		if (!mapEl || typeof L === 'undefined') return;
		var latInput = document.querySelector('form input[name="latitude"]');
		var lngInput = document.querySelector('form input[name="longitude"]');
		var start = { lat: parseFloat(latInput.value)||14.5995, lng: parseFloat(lngInput.value)||120.9842 };
		var map = L.map('editBranchMap').setView([start.lat, start.lng], 12);
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);
		
		// Use emoji marker for branch editing
		var marker;
		if (typeof createEmojiMarker !== 'undefined') {
			marker = createEmojiMarker(start.lat, start.lng, '🏢', { draggable: true }).addTo(map);
		} else {
			marker = L.marker([start.lat, start.lng], { draggable: true }).addTo(map);
		}
		function updateInputs(latlng){ if(latInput){latInput.value = latlng.lat.toFixed(8);} if(lngInput){lngInput.value = latlng.lng.toFixed(8);} }
		marker.on('dragend', function(){ updateInputs(marker.getLatLng()); });
		map.on('click', function(e){ marker.setLatLng(e.latlng); updateInputs(e.latlng); });
		var btn = document.getElementById('btnEditUseMyLoc');
		if (btn && navigator.geolocation) {
			btn.addEventListener('click', function(ev){ ev.preventDefault(); navigator.geolocation.getCurrentPosition(function(pos){ var ll = {lat: pos.coords.latitude, lng: pos.coords.longitude}; map.setView(ll, 14); marker.setLatLng(ll); updateInputs(ll); }); });
		}
	})();
	</script>

	<div class="table-responsive">
		<table class="table table-striped align-middle">
			<thead>
				<tr>
					<th>ID</th>
					<th>Name</th>
					<th>Code</th>
					<th>City</th>
					<th>Province</th>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($rows as $r): ?>
				<tr>
					<td><?= (int)$r['branch_id'] ?></td>
					<td><?= sanitize($r['branch_name']) ?></td>
					<td><?= sanitize($r['branch_code']) ?></td>
					<td><?= sanitize($r['city']) ?></td>
					<td><?= sanitize($r['province']) ?></td>
					<td><span class="badge bg-<?= $r['status']==='active'?'success':'secondary' ?>"><?= sanitize($r['status']) ?></span></td>
					<td>
                    <a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>admin/branches.php?edit_id=<?= (int)$r['branch_id'] ?>">Edit</a>
						<form class="d-inline" method="post" onsubmit="return confirm('Delete this branch?')">
							<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
							<input type="hidden" name="form_action" value="delete">
							<input type="hidden" name="branch_id" value="<?= (int)$r['branch_id'] ?>">
							<button class="btn btn-sm btn-outline-danger">Delete</button>
						</form>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<!-- Create Modal -->
	<div class="modal fade" id="modalCreate" tabindex="-1" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<form method="post">
					<input type="hidden" name="csrf_token" value="<?= $csrf ?>">
					<input type="hidden" name="form_action" value="create">
					<div class="modal-header"><h5 class="modal-title">Add Branch</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
					<div class="modal-body">
						<div class="row g-2">
							<div class="col-md-6">
								<label class="form-label">Name</label>
								<input class="form-control" name="branch_name" required>
							</div>
							<div class="col-md-6">
								<label class="form-label">Code</label>
								<input class="form-control" name="branch_code" required>
							</div>
							<div class="col-12">
								<label class="form-label">Address</label>
								<input class="form-control" name="address" required>
							</div>
							<div class="col-md-4">
								<label class="form-label">City</label>
								<input class="form-control" name="city" required>
							</div>
							<div class="col-md-4">
								<label class="form-label">Province</label>
								<input class="form-control" name="province" required>
							</div>
							<div class="col-md-2">
								<label class="form-label">Latitude</label>
								<input type="number" step="0.00000001" class="form-control" name="latitude" required>
							</div>
							<div class="col-md-2">
								<label class="form-label">Longitude</label>
								<input type="number" step="0.00000001" class="form-control" name="longitude" required>
							</div>
							<div class="col-md-6">
								<label class="form-label">Contact</label>
								<input class="form-control" name="contact_number" required>
							</div>
							<div class="col-md-6">
								<label class="form-label">Status</label>
								<select class="form-select" name="status">
									<option value="active">Active</option>
									<option value="inactive">Inactive</option>
								</select>
							</div>
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


