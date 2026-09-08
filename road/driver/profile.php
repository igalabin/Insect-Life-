<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/nav.php';
start_secure_session();

global $pdo;
$me = null;
$orderStats = ['total' => 0, 'completed' => 0, 'pending' => 0];

if (isset($_SESSION['user']) && $_SESSION['user']['role'] === ROLE_DRIVER) {
    $driverId = (int)$_SESSION['user']['id'];
    
    // Get driver info
    $s = $pdo->prepare('SELECT full_name, email, phone, license_number, address FROM drivers WHERE driver_id = :id');
    $s->execute([':id' => $driverId]);
    $me = $s->fetch();
    
    // Get order statistics
    $statsQuery = $pdo->prepare("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN order_status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN order_status IN ('pending', 'confirmed', 'preparing', 'on_delivery') THEN 1 ELSE 0 END) as pending
        FROM orders WHERE driver_id = :id");
    $statsQuery->execute([':id' => $driverId]);
    $stats = $statsQuery->fetch();
    if ($stats) {
        $orderStats = [
            'total' => (int)$stats['total'],
            'completed' => (int)$stats['completed'],
            'pending' => (int)$stats['pending']
        ];
    }
}
?>

<style>
.profile-hero {
  background: linear-gradient(135deg, #6f2cff 0%, #a779ff 100%);
  border-radius: 1.5rem;
  padding: 2rem;
  margin-bottom: 2rem;
  color: white;
  box-shadow: 0 10px 40px rgba(111,44,255,.25);
  position: relative;
  overflow: hidden;
  isolation: isolate;
}
.profile-hero::before {
  content: '👤';
  position: absolute;
  top: -30px;
  right: -30px;
  font-size: 180px;
  opacity: .08;
  z-index: 0;
}
.hero-content {
  position: relative;
  z-index: 1;
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
.profile-badge {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  padding: .5rem 1.25rem;
  background: rgba(255,255,255,.2);
  border-radius: 2rem;
  font-size: .875rem;
  font-weight: 500;
}
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
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
.profile-section {
  background: white;
  border-radius: 1.5rem;
  padding: 2rem;
  box-shadow: 0 4px 16px rgba(0,0,0,.08);
  border: 2px solid #f5f0ff;
  margin-bottom: 2rem;
}
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f5f0ff;
}
.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1b0a33;
  display: flex;
  align-items: center;
  gap: .75rem;
  margin: 0;
}
.section-title-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #6f2cff, #a779ff);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
}
.info-card {
  padding: 1.5rem;
  background: linear-gradient(135deg, #f5f0ff, #faf9ff);
  border-radius: 1rem;
  border: 2px solid #e6dbff;
  transition: all .3s ease;
}
.info-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 24px rgba(111,44,255,.15);
}
.info-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 1rem;
  box-shadow: 0 4px 12px rgba(0,0,0,.08);
}
.info-label {
  font-size: .75rem;
  color: #718096;
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: .5rem;
  font-weight: 600;
}
.info-value {
  font-weight: 600;
  color: #1b0a33;
  font-size: 1.125rem;
  word-break: break-word;
}
.btn-edit {
  background: linear-gradient(135deg, #6f2cff, #a779ff);
  color: white;
  border: none;
  padding: .75rem 1.5rem;
  border-radius: 1rem;
  font-weight: 600;
  box-shadow: 0 4px 16px rgba(111,44,255,.3);
  transition: all .3s ease;
  display: inline-flex;
  align-items: center;
  gap: .5rem;
}
.btn-edit:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(111,44,255,.4);
  color: white;
}
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #718096;
}
.empty-icon {
  font-size: 5rem;
  margin-bottom: 1.5rem;
  opacity: .5;
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
main.container {
  position: relative;
  z-index: 1;
}
</style>

<main class="container py-4">
  <?php if (!$me): ?>
    <div class="profile-hero">
      <div class="hero-content">
        <div class="hero-title">🔒 Access Restricted</div>
        <div class="hero-subtitle">Please login as a driver to view your profile</div>
      </div>
    </div>
  <?php else: ?>
    <div class="profile-hero">
      <div class="hero-content">
        <div class="hero-title">👤 <?= sanitize($me['full_name']) ?></div>
        <div class="hero-subtitle">Manage your account and view your activity</div>
        <span class="profile-badge">
          <i class="fas fa-car"></i>
          <span>Verified Driver</span>
        </span>
      </div>
    </div>

    <!-- Order Statistics -->
    <div class="stats-container">
      <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div class="stat-label">Total Orders</div>
        <div class="stat-value"><?= number_format($orderStats['total']) ?></div>
        <div class="stat-subtext">All time orders</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div class="stat-label">Completed</div>
        <div class="stat-value"><?= number_format($orderStats['completed']) ?></div>
        <div class="stat-subtext">Successfully delivered</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div class="stat-label">Active Orders</div>
        <div class="stat-value"><?= number_format($orderStats['pending']) ?></div>
        <div class="stat-subtext">In progress</div>
      </div>
    </div>

    <!-- Personal Information -->
    <div class="profile-section">
      <div class="section-header">
        <h2 class="section-title">
          <div class="section-title-icon">
            <i class="fas fa-user"></i>
          </div>
          <span>Personal Information</span>
        </h2>
        <button class="btn-edit" onclick="alert('Edit profile feature coming soon!')">
          <i class="fas fa-edit"></i>
          <span>Edit Profile</span>
        </button>
      </div>
      
      <div class="info-grid">
        <div class="info-card">
          <div class="info-icon">📧</div>
          <div class="info-label">Email Address</div>
          <div class="info-value"><?= sanitize($me['email']) ?></div>
        </div>
        
        <div class="info-card">
          <div class="info-icon">📱</div>
          <div class="info-label">Phone Number</div>
          <div class="info-value"><?= sanitize($me['phone']) ?></div>
        </div>
        
        <div class="info-card">
          <div class="info-icon">🪪</div>
          <div class="info-label">License Number</div>
          <div class="info-value"><?= sanitize($me['license_number']) ?></div>
        </div>
        
        <div class="info-card">
          <div class="info-icon">📍</div>
          <div class="info-label">Address</div>
          <div class="info-value"><?= sanitize($me['address'] ?: 'Not provided') ?></div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <h3 style="margin-bottom:1.5rem;color:#1b0a33;font-weight:600">Quick Access</h3>
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
      
      <a href="<?= BASE_URL ?>driver/dashboard.php" class="action-card">
        <div class="action-card-icon">📊</div>
        <h3>Dashboard</h3>
        <p>View your complete dashboard with statistics and insights</p>
      </a>
    </div>
  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>