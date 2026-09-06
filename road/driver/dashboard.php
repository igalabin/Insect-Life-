<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/nav.php';
?>

<style>
.driver-hero {
  background: linear-gradient(135deg, #6f2cff 0%, #a779ff 100%);
  border-radius: 1.5rem;
  padding: 2rem;
  margin-bottom: 2rem;
  color: white;
  box-shadow: 0 10px 40px rgba(111,44,255,.25);
  position: relative;
  overflow: hidden;
}
.driver-hero::before {
  content: '🚗';
  position: absolute;
  top: -30px;
  right: -30px;
  font-size: 180px;
  opacity: .08;
}
.hero-title {
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: .5rem;
}
.hero-subtitle {
  opacity: .9;
  margin-bottom: 1.5rem;
}
.quick-action-btn {
  background: white;
  color: #6f2cff;
  border: none;
  padding: 1rem 2rem;
  border-radius: 1rem;
  font-weight: 600;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: .75rem;
  box-shadow: 0 4px 16px rgba(0,0,0,.15);
  transition: all .3s ease;
}
.quick-action-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 24px rgba(0,0,0,.25);
  color: #6f2cff;
  text-decoration: none;
}
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}
.stat-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 4px 16px rgba(0,0,0,.08);
  transition: all .3s ease;
  border: 2px solid transparent;
  position: relative;
  overflow: hidden;
}
.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #6f2cff, #a779ff);
}
.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 24px rgba(111,44,255,.2);
  border-color: #e6dbff;
}
.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
  margin-bottom: 1rem;
  background: linear-gradient(135deg, #f5f0ff, #e6dbff);
}
.stat-label {
  color: #718096;
  font-size: .875rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: .5rem;
}
.stat-value {
  font-size: 2.25rem;
  font-weight: 700;
  color: #1b0a33;
}
.stat-subtext {
  color: #a0aec0;
  font-size: .75rem;
  margin-top: .25rem;
}
.action-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}
.action-card {
  background: white;
  border-radius: 1rem;
  padding: 2rem;
  box-shadow: 0 4px 16px rgba(0,0,0,.08);
  transition: all .3s ease;
  text-decoration: none;
  display: block;
  border: 2px solid transparent;
  position: relative;
}
.action-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 24px rgba(111,44,255,.2);
  border-color: #6f2cff;
  text-decoration: none;
}
.action-card-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  margin-bottom: 1.25rem;
  background: linear-gradient(135deg, #6f2cff, #a779ff);
  color: white;
  box-shadow: 0 4px 16px rgba(111,44,255,.25);
}
.action-card h3 {
  color: #1b0a33;
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: .75rem;
}
.action-card p {
  color: #718096;
  margin: 0;
  line-height: 1.6;
}
.badge-live {
  display: inline-flex;
  align-items: center;
  padding: .4rem .9rem;
  background: rgba(255,255,255,.2);
  border-radius: 2rem;
  font-size: .813rem;
  font-weight: 500;
  margin-left: .75rem;
}
.live-dot {
  width: 8px;
  height: 8px;
  background: #4ade80;
  border-radius: 50%;
  margin-right: .5rem;
  animation: pulse 2s infinite;
}
@keyframes pulse {
  0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(74,222,128,.7); }
  50% { opacity: .8; box-shadow: 0 0 0 6px rgba(74,222,128,0); }
}
</style>

<main class="container py-4">
  <?php
  start_secure_session();
  $driverId = $_SESSION['user']['id'] ?? 0;
  $pending = 0; $completed = 0; $onDelivery = 0;
  if ($driverId) {
    global $pdo;
    $st = $pdo->prepare("SELECT 
        SUM(order_status IN ('pending','confirmed','preparing')) AS pending_cnt,
        SUM(order_status = 'on_delivery') AS delivery_cnt,
        SUM(order_status = 'completed') AS completed_cnt
      FROM orders WHERE driver_id = :d");
    $st->execute([':d' => (int)$driverId]);
    $row = $st->fetch();
    if ($row) { 
    $pending = (int)$row['pending_cnt']; 
    $onDelivery = (int)$row['delivery_cnt'];
    $completed = (int)$row['completed_cnt']; 
  }
  }
  $driverName = $_SESSION['user']['name'] ?? 'Driver';
  ?>
  
  <div class="driver-hero">
    <div style="position:relative;z-index:1">
      <div class="hero-title">Welcome, <?= sanitize($driverName) ?>! 🚀</div>
      <div class="hero-subtitle">Let's fuel up the day with great deliveries</div>
      <a href="<?= BASE_URL ?>driver/order.php" class="quick-action-btn">
        <span style="font-size:1.5rem">⛽</span>
        <span>Order Fuel Now</span>
      </a>
      <?php if ($onDelivery > 0): ?>
      <span class="badge-live">
        <span class="live-dot"></span>
        <?= $onDelivery ?> active delivery
      </span>
      <?php endif; ?>
    </div>
  </div>

  <div class="stats-container">
    <div class="stat-card">
      <div class="stat-icon">⏳</div>
      <div class="stat-label">Pending Orders</div>
      <div class="stat-value"><?= number_format($pending) ?></div>
      <div class="stat-subtext">Awaiting processing</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">🚚</div>
      <div class="stat-label">On Delivery</div>
      <div class="stat-value"><?= number_format($onDelivery) ?></div>
      <div class="stat-subtext">In transit now</div>
    </div>
    
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <div class="stat-label">Completed</div>
      <div class="stat-value"><?= number_format($completed) ?></div>
      <div class="stat-subtext">Total delivered</div>
    </div>
  </div>

  <h3 style="margin-top:3rem;margin-bottom:1.5rem;color:#1b0a33;font-weight:600">Quick Access</h3>
  
  <div class="action-grid">
    <a href="<?= BASE_URL ?>driver/order.php" class="action-card">
      <div class="action-card-icon">⛽</div>
      <h3>New Order</h3>
      <p>Place a new fuel order for your vehicle quickly and easily</p>
    </a>
    
    <a href="<?= BASE_URL ?>driver/my-orders.php" class="action-card">
      <div class="action-card-icon">📦</div>
      <h3>My Orders</h3>
      <p>Track all your orders, view status and delivery history</p>
    </a>
    
    <a href="<?= BASE_URL ?>driver/my-orders.php?filter=on_delivery" class="action-card">
      <div class="action-card-icon">🗺️</div>
      <h3>Active Deliveries</h3>
      <p>Monitor your ongoing deliveries and estimated arrival times</p>
    </a>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>