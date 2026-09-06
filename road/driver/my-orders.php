<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/nav.php';

start_secure_session();
?>

<style>
.orders-hero {
  background: linear-gradient(135deg, #6f2cff 0%, #a779ff 100%);
  border-radius: 1.5rem;
  padding: 2rem;
  margin-bottom: 2rem;
  color: white;
  box-shadow: 0 10px 40px rgba(111,44,255,.25);
  position: relative;
  overflow: hidden;
}
.orders-hero::before {
  content: '📦';
  position: absolute;
  top: -30px;
  right: -30px;
  font-size: 150px;
  opacity: .08;
}
.orders-hero h1 {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: .5rem;
  position: relative;
  z-index: 1;
}
.orders-hero p {
  opacity: .9;
  margin: 0;
  position: relative;
  z-index: 1;
}
.orders-card {
  background: white;
  border-radius: 1.5rem;
  padding: 1.5rem;
  box-shadow: 0 4px 16px rgba(0,0,0,.08);
  margin-bottom: 1.5rem;
}
.orders-table-wrapper {
  border-radius: 1rem;
  overflow: hidden;
}
.orders-table {
  margin: 0;
}
.orders-table thead {
  background: linear-gradient(135deg, #f5f0ff, #e6dbff);
}
.orders-table thead th {
  border: none;
  padding: 1rem;
  font-weight: 600;
  color: #6f2cff;
  text-transform: uppercase;
  font-size: .75rem;
  letter-spacing: .5px;
}
.orders-table tbody tr {
  border-bottom: 1px solid #f5f0ff;
  transition: all .2s ease;
}
.orders-table tbody tr:hover {
  background: #faf9ff;
}
.orders-table tbody td {
  padding: 1rem;
  vertical-align: middle;
}
.badge-custom {
  padding: .5rem 1rem;
  border-radius: 2rem;
  font-weight: 600;
  font-size: .75rem;
}
.detail-card {
  background: white;
  border-radius: 1.5rem;
  padding: 1.5rem;
  box-shadow: 0 8px 24px rgba(111,44,255,.15);
  border: 2px solid #e6dbff;
}
.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f5f0ff;
}
.detail-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #6f2cff;
  margin: 0;
}
.map-wrapper {
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0,0,0,.1);
}
.info-section {
  display: grid;
  gap: 1rem;
}
.info-box {
  padding: 1rem;
  background: #f5f0ff;
  border-radius: .75rem;
  border-left: 4px solid #6f2cff;
}
.info-box-label {
  font-size: .75rem;
  color: #718096;
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: .5rem;
}
.info-box-value {
  font-weight: 600;
  color: #1b0a33;
  font-size: 1rem;
}
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #718096;
}
.empty-state-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: .5;
}
.btn-view {
  padding: .5rem 1.25rem;
  border-radius: .75rem;
  font-weight: 500;
  transition: all .2s ease;
}
.btn-view:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(111,44,255,.2);
}
</style>

<main class="container py-4">
  <div class="orders-hero">
    <h1>📦 My Orders</h1>
    <p>Track your fuel delivery orders and history</p>
  </div>

  <div class="orders-card">
    <div id="orders"></div>
  </div>
  
  <!-- Order Detail View with Map -->
  <div id="orderDetail" class="detail-card" style="display: none;">
    <div class="detail-header">
      <h6 class="detail-title">📋 Order Details</h6>
      <button class="btn btn-sm btn-outline-secondary" onclick="closeOrderDetail()">✕ Close</button>
    </div>
    <div class="row g-4">
      <div class="col-md-6">
        <div id="driverOrderMap" class="map-wrapper" style="height:300px;background:#f3f0ff"></div>
      </div>
      <div class="col-md-6">
      <div class="info-section">
        <div class="info-box">
          <div class="info-box-label">Order Number</div>
          <div class="info-box-value" id="orderNumber"></div>
        </div>
        <div class="info-box">
          <div class="info-box-label">Status</div>
          <div class="info-box-value" id="orderStatus"></div>
        </div>
        <div class="info-box">
          <div class="info-box-label">Total Amount</div>
          <div class="info-box-value" style="color:#6f2cff;font-size:1.5rem">₱<span id="orderTotal"></span></div>
        </div>
        <div class="info-box">
          <div class="info-box-label">Payment Status</div>
          <div class="info-box-value" id="orderPayment"></div>
        </div>
        <div class="info-box">
          <div class="info-box-label">Delivery Address</div>
          <div class="info-box-value" id="orderAddress"></div>
        </div>
        <div class="info-box">
          <div class="info-box-label">Notes</div>
          <div class="info-box-value" id="orderNotes"></div>
        </div>
      </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Leaflet and Emoji Markers -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" onerror="this.remove()"/>
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" onerror="this.remove()"></script>
<script src="<?= BASE_URL ?>assets/js/leaflet.js"></script>
<script src="<?= BASE_URL ?>assets/js/emoji-markers.js"></script>

<script>
function renderPaymentBadge(status) {
  var s = String(status || '').toLowerCase();
  var cls = 'bg-secondary';
  if (s === 'paid' || s === 'succeeded' || s === 'completed') cls = 'bg-success';
  else if (s === 'pending' || s === 'processing') cls = 'bg-warning text-dark';
  else if (s === 'failed' || s === 'declined' || s === 'refunded' || s === 'cancelled') cls = 'bg-danger';
  return '<span class="badge badge-custom ' + cls + '">' + (status || 'Unknown') + '</span>';
}

function renderStatusBadge(status) {
  var s = String(status || '').toLowerCase();
  var cls = 'bg-secondary';
  if (s === 'completed') cls = 'bg-success';
  else if (s === 'on_delivery') cls = 'bg-primary';
  else if (s === 'preparing') cls = 'bg-info';
  else if (s === 'confirmed') cls = 'bg-info';
  else if (s === 'pending') cls = 'bg-warning text-dark';
  else if (s === 'cancelled') cls = 'bg-danger';
  return '<span class="badge badge-custom ' + cls + '">' + (status || 'Unknown').replace('_', ' ') + '</span>';
}

(async function(){
  const res = await fetch('<?= BASE_URL ?>api/orders.php?action=my_orders');
  const data = await res.json();
  const el = document.getElementById('orders');
  if (!data.ok) { 
    el.innerHTML = '<div class="alert alert-danger">⚠️ ' + (data.error||'Failed to load orders') + '</div>'; 
    return; 
  }
  if (!data.data.length) { 
    el.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📭</div><div class="h5">No orders yet</div><div class="text-muted">Your fuel orders will appear here</div></div>'; 
    return; 
  }
  let html = '<div class="orders-table-wrapper"><table class="table orders-table"><thead><tr><th>Order #</th><th>Status</th><th>Payment</th><th>Total</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
  data.data.forEach(o => {
    html += `<tr>
      <td><strong>${o.order_number}</strong></td>
      <td>${renderStatusBadge(o.order_status)}</td>
      <td>${renderPaymentBadge(o.payment_status)}</td>
      <td><strong style="color:#6f2cff">₱${Number(o.total_amount).toFixed(2)}</strong></td>
      <td><span class="small text-muted">${o.created_at}</span></td>
      <td><button class="btn btn-sm btn-outline-secondary btn-view" onclick="viewOrder(${o.order_id})">👁️ View</button></td>
    </tr>`;
  });
  html += '</tbody></table></div>';
  el.innerHTML = html;
})();

// Global variables for map
let driverOrderMap = null;
let currentOrderMarker = null;

// Function to view order details
async function viewOrder(orderId) {
  try {
    const res = await fetch(`<?= BASE_URL ?>api/orders.php?action=order_detail&id=${orderId}`);
    const data = await res.json();
    
    if (!data.ok) {
      alert('Failed to load order details: ' + (data.error || 'Unknown error'));
      return;
    }
    
    const order = data.data;
    
    // Update order details
    document.getElementById('orderNumber').textContent = order.order_number;
    document.getElementById('orderStatus').innerHTML = renderStatusBadge(order.order_status);
    document.getElementById('orderTotal').textContent = Number(order.total_amount).toFixed(2);
    document.getElementById('orderPayment').innerHTML = renderPaymentBadge(order.payment_status);
    document.getElementById('orderAddress').textContent = order.delivery_address || 'No address provided';
    document.getElementById('orderNotes').textContent = order.notes || 'No notes';
    
    // Show order detail panel
    document.getElementById('orderDetail').style.display = 'block';
    
    // Scroll to detail
    document.getElementById('orderDetail').scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    // Initialize map if not already done
    if (!driverOrderMap) {
      initializeDriverOrderMap();
    }
    
    // Update map with order location
    if (order.delivery_latitude && order.delivery_longitude) {
      const lat = parseFloat(order.delivery_latitude);
      const lng = parseFloat(order.delivery_longitude);
      
      // Clear existing marker
      if (currentOrderMarker) {
        driverOrderMap.removeLayer(currentOrderMarker);
      }
      
      // Add emoji marker for delivery location
      if (typeof createEmojiMarker !== 'undefined') {
        currentOrderMarker = createEmojiMarker(lat, lng, '🚚').addTo(driverOrderMap);
      } else {
        currentOrderMarker = L.marker([lat, lng]).addTo(driverOrderMap);
      }
      
      // Center map on location
      driverOrderMap.setView([lat, lng], 15);
    }
    
  } catch (error) {
    console.error('Error loading order details:', error);
    alert('Failed to load order details');
  }
}

// Function to initialize the driver order map
function initializeDriverOrderMap() {
  if (typeof L === 'undefined') return;
  
  driverOrderMap = L.map('driverOrderMap').setView([14.5995, 120.9842], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
  }).addTo(driverOrderMap);
}

// Function to close order detail
function closeOrderDetail() {
  document.getElementById('orderDetail').style.display = 'none';
  if (currentOrderMarker && driverOrderMap) {
    driverOrderMap.removeLayer(currentOrderMarker);
    currentOrderMarker = null;
  }
}
</script>