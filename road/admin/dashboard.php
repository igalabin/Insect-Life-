<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_ADMIN]);

$appTitle = 'Admin Dashboard - RoadFuel';
require __DIR__ . '/../includes/header-admin.php';
?>

<?php
global $pdo;
$branchName = null;
if (!empty($_SESSION['user']['id'])) {
	$st = $pdo->prepare('SELECT b.branch_name FROM users u LEFT JOIN branches b ON b.branch_id = u.branch_id WHERE u.user_id = :id LIMIT 1');
	$st->execute([':id' => (int)$_SESSION['user']['id']]);
	$branchName = $st->fetchColumn() ?: null;
}

// Enhanced metrics with trends
$metrics = [
    'products' => (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'branches' => (int)$pdo->query("SELECT COUNT(*) FROM branches")->fetchColumn(),
    'staff' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetchColumn(),
    'drivers' => (int)$pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn(),
    'orders_today' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE()")->fetchColumn(),
    'orders_pending' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE order_status='pending'")->fetchColumn(),
];

// Get recent activity count
$recentOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
?>

<style>
.dashboard-header {
	background: linear-gradient(135deg, #6f2cff 0%, #a779ff 100%);
	border-radius: 1.5rem;
	padding: 2rem;
	margin-bottom: 2rem;
	color: white;
	box-shadow: 0 10px 40px rgba(111,44,255,.25);
	position: relative;
	overflow: hidden;
}
.dashboard-header::before {
	content: '';
	position: absolute;
	top: -50%;
	right: -10%;
	width: 300px;
	height: 300px;
	background: rgba(255,255,255,.1);
	border-radius: 50%;
}
.dashboard-header::after {
	content: '';
	position: absolute;
	bottom: -30%;
	left: -5%;
	width: 200px;
	height: 200px;
	background: rgba(255,255,255,.08);
	border-radius: 50%;
}
.metric-card {
	background: white;
	border-radius: 1rem;
	padding: 1.5rem;
	box-shadow: 0 4px 16px rgba(0,0,0,.08);
	transition: all .3s ease;
	border: 2px solid transparent;
	position: relative;
	overflow: hidden;
}
.metric-card::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 4px;
	background: linear-gradient(90deg, #6f2cff, #a779ff);
	transform: scaleX(0);
	transition: transform .3s ease;
}
.metric-card:hover {
	transform: translateY(-5px);
	box-shadow: 0 8px 24px rgba(111,44,255,.2);
	border-color: #e6dbff;
}
.metric-card:hover::before {
	transform: scaleX(1);
}
.metric-icon {
	width: 56px;
	height: 56px;
	border-radius: 16px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 24px;
	margin-bottom: 1rem;
	background: linear-gradient(135deg, #f5f0ff, #e6dbff);
	color: #6f2cff;
}
.metric-value {
	font-size: 2rem;
	font-weight: 700;
	color: #1b0a33;
	margin-bottom: .5rem;
}
.metric-label {
	color: #718096;
	font-size: .875rem;
	font-weight: 500;
	text-transform: uppercase;
	letter-spacing: .5px;
}
.action-card {
	background: white;
	border-radius: 1rem;
	padding: 1.5rem;
	text-decoration: none;
	display: block;
	box-shadow: 0 4px 16px rgba(0,0,0,.08);
	transition: all .3s ease;
	border: 2px solid transparent;
	height: 100%;
}
.action-card:hover {
	transform: translateY(-5px);
	box-shadow: 0 8px 24px rgba(111,44,255,.2);
	border-color: #6f2cff;
	text-decoration: none;
}
.action-card-icon {
	width: 48px;
	height: 48px;
	border-radius: 12px;
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 20px;
	margin-bottom: 1rem;
	background: linear-gradient(135deg, #6f2cff, #a779ff);
	color: white;
}
.action-card h5 {
	color: #1b0a33;
	font-weight: 600;
	margin-bottom: .5rem;
}
.action-card p {
	color: #718096;
	margin: 0;
	font-size: .875rem;
}
.stats-badge {
	display: inline-flex;
	align-items: center;
	padding: .5rem 1rem;
	background: rgba(255,255,255,.2);
	backdrop-filter: blur(10px);
	border-radius: 2rem;
	font-size: .875rem;
	font-weight: 500;
}
.pulse-dot {
	width: 8px;
	height: 8px;
	background: #4ade80;
	border-radius: 50%;
	margin-right: .5rem;
	animation: pulse 2s infinite;
}
@keyframes pulse {
	0%, 100% { opacity: 1; transform: scale(1); }
	50% { opacity: .5; transform: scale(1.2); }
}
</style>

<div class="container py-4">
	<div class="dashboard-header">
		<div class="row align-items-center" style="position:relative;z-index:1">
			<div class="col-md-8">
				<h1 class="mb-2" style="font-weight:700">Welcome Back, Admin! 👋</h1>
				<?php if ($branchName): ?>
					<p class="mb-3" style="opacity:.9">Managing <strong><?= sanitize($branchName) ?></strong> branch operations</p>
				<?php else: ?>
					<p class="mb-3" style="opacity:.9">System-wide administration dashboard</p>
				<?php endif; ?>
				<div class="stats-badge">
					<span class="pulse-dot"></span>
					<?= number_format($recentOrders) ?> orders this week
				</div>
			</div>
			<div class="col-md-4 text-md-end mt-3 mt-md-0">
				<div style="font-size:3rem;opacity:.3">📊</div>
			</div>
		</div>
	</div>

	<!-- Metric cards -->
	<div class="row g-3 mb-4">
		<div class="col-6 col-md-4 col-xl-2">
			<div class="metric-card">
				<div class="metric-icon">🏪</div>
				<div class="metric-value"><?= number_format($metrics['products']) ?></div>
				<div class="metric-label">Products</div>
			</div>
		</div>
		<div class="col-6 col-md-4 col-xl-2">
			<div class="metric-card">
				<div class="metric-icon">🏢</div>
				<div class="metric-value"><?= number_format($metrics['branches']) ?></div>
				<div class="metric-label">Branches</div>
			</div>
		</div>
		<div class="col-6 col-md-4 col-xl-2">
			<div class="metric-card">
				<div class="metric-icon">👥</div>
				<div class="metric-value"><?= number_format($metrics['staff']) ?></div>
				<div class="metric-label">Staff</div>
			</div>
		</div>
		<div class="col-6 col-md-4 col-xl-2">
			<div class="metric-card">
				<div class="metric-icon">🚗</div>
				<div class="metric-value"><?= number_format($metrics['drivers']) ?></div>
				<div class="metric-label">Drivers</div>
			</div>
		</div>
		<div class="col-6 col-md-4 col-xl-2">
			<div class="metric-card">
				<div class="metric-icon">📅</div>
				<div class="metric-value"><?= number_format($metrics['orders_today']) ?></div>
				<div class="metric-label">Today</div>
			</div>
		</div>
		<div class="col-6 col-md-4 col-xl-2">
			<div class="metric-card">
				<div class="metric-icon">⏳</div>
				<div class="metric-value"><?= number_format($metrics['orders_pending']) ?></div>
				<div class="metric-label">Pending</div>
			</div>
		</div>
	</div>

	<h4 class="mb-3" style="color:#1b0a33;font-weight:600">Quick Actions</h4>
	<div class="row g-3">
		<div class="col-md-4">
			<a class="action-card" href="<?= BASE_URL ?>admin/products.php">
				<div class="action-card-icon">⛽</div>
				<h5>Products</h5>
				<p>Manage fuel products and pricing</p>
			</a>
		</div>
		<div class="col-md-4">
			<a class="action-card" href="<?= BASE_URL ?>admin/branches.php">
				<div class="action-card-icon">🏢</div>
				<h5>Branches</h5>
				<p>Manage branches & stock levels</p>
			</a>
		</div>
		<div class="col-md-4">
			<a class="action-card" href="<?= BASE_URL ?>admin/users.php">
				<div class="action-card-icon">👤</div>
				<h5>Users</h5>
				<p>Manage admins & staff accounts</p>
			</a>
		</div>
	</div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>