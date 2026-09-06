<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

// Public scaffold access for now (no role restriction on landing flow)

require_once __DIR__ . '/../includes/nav.php';
?>

<style>
.order-hero {
  background: linear-gradient(135deg, #6f2cff 0%, #a779ff 100%);
  border-radius: 1.5rem;
  padding: 2rem;
  margin-bottom: 2rem;
  color: white;
  box-shadow: 0 10px 40px rgba(111,44,255,.25);
  position: relative;
  overflow: hidden;
}
.order-hero::before {
  content: '⛽';
  position: absolute;
  top: -30px;
  right: -30px;
  font-size: 150px;
  opacity: .08;
}
.order-hero h2 {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: .5rem;
  position: relative;
  z-index: 1;
}
.order-hero p {
  opacity: .9;
  margin: 0;
  position: relative;
  z-index: 1;
}
.order-form-card {
  background: white;
  border-radius: 1.5rem;
  padding: 2rem;
  box-shadow: 0 4px 16px rgba(0,0,0,.08);
  border: 2px solid #f5f0ff;
}
.summary-card {
  background: linear-gradient(135deg, #f5f0ff, #faf9ff);
  border-radius: 1.5rem;
  padding: 2rem;
  box-shadow: 0 4px 16px rgba(0,0,0,.08);
  border: 2px solid #e6dbff;
  position: sticky;
  top: 20px;
}
.summary-card h2 {
  color: #6f2cff;
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: .5rem;
}
.summary-item {
  padding: 1rem;
  background: white;
  border-radius: .75rem;
  margin-bottom: .75rem;
  box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.summary-label {
  font-size: .75rem;
  color: #718096;
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: .25rem;
}
.summary-value {
  font-weight: 600;
  color: #1b0a33;
  font-size: 1rem;
}
.summary-total {
  padding: 1.5rem;
  background: linear-gradient(135deg, #6f2cff, #a779ff);
  border-radius: .75rem;
  color: white;
  text-align: center;
  margin-top: 1rem;
}
.summary-total-label {
  font-size: .875rem;
  opacity: .9;
  margin-bottom: .5rem;
}
.summary-total-value {
  font-size: 2rem;
  font-weight: 700;
}
.form-section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #6f2cff;
  margin-bottom: 1rem;
  padding-bottom: .5rem;
  border-bottom: 2px solid #f5f0ff;
}
.form-label {
  font-weight: 500;
  color: #4a5568;
  margin-bottom: .5rem;
}
.form-control, .form-select {
  border: 2px solid #e2e8f0;
  border-radius: .75rem;
  padding: .75rem 1rem;
  transition: all .2s ease;
}
.form-control:focus, .form-select:focus {
  border-color: #a779ff;
  box-shadow: 0 0 0 3px rgba(111,44,255,.1);
}
.map-controls {
  display: flex;
  gap: .5rem;
  flex-wrap: wrap;
}
.map-btn {
  padding: .5rem 1rem;
  border-radius: .75rem;
  font-size: .875rem;
  font-weight: 500;
  transition: all .2s ease;
}
.map-btn:hover {
  transform: translateY(-2px);
}
.map-container {
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0,0,0,.1);
  margin-top: .75rem;
}
.radio-card {
  padding: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: .75rem;
  cursor: pointer;
  transition: all .2s ease;
  display: flex;
  align-items: center;
  gap: .75rem;
}
.radio-card:hover {
  border-color: #a779ff;
  background: #faf9ff;
}
.radio-card input:checked ~ .radio-card-content {
  color: #6f2cff;
}
.radio-card input:checked + label {
  border-color: #6f2cff;
  background: #f5f0ff;
}
.submit-btn {
  background: linear-gradient(135deg, #6f2cff, #a779ff);
  color: white;
  border: none;
  padding: 1rem 2.5rem;
  border-radius: 1rem;
  font-weight: 600;
  font-size: 1rem;
  box-shadow: 0 4px 16px rgba(111,44,255,.3);
  transition: all .3s ease;
}
.submit-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(111,44,255,.4);
}
</style>

<main class="container py-4">
  <div class="order-hero">
    <h2>⛽ Order Fuel</h2>
    <p>Quick and easy fuel delivery to your location</p>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="order-form-card">
        <form id="order-form" method="post" action="<?= BASE_URL ?>api/orders.php">
          <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
          <input type="hidden" name="action" value="create">
          
          <!-- Vehicle Information -->
          <div class="form-section-title">🚗 Vehicle Information</div>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Vehicle Type</label>
              <select class="form-select" name="vehicle_type" required>
                <option value="">Select vehicle type</option>
                <option>motorcycle</option>
                <option>sedan</option>
                <option>suv</option>
                <option>truck</option>
                <option>van</option>
                <option>bus</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Plate Number</label>
              <input class="form-control" name="vehicle_plate_number" placeholder="ABC-1234" required>
            </div>
          </div>

          <!-- Fuel Selection -->
          <div class="form-section-title">⛽ Fuel Selection</div>
          <div class="row g-3 mb-4">
            <div class="col-md-7">
              <label class="form-label">Fuel Type</label>
              <select class="form-select" name="product_id" required>
                <option value="">Select fuel type</option>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label">Quantity (Liters)</label>
              <input type="number" step="0.1" min="1" class="form-control" name="quantity_liters" placeholder="20" required>
            </div>
          </div>

          <!-- Payment Method -->
          <div class="form-section-title">💳 Payment Method</div>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="radio-card">
                <input class="form-check-input" type="radio" name="payment_method" value="cod" checked>
                <div class="radio-card-content">
                  <div class="fw-semibold">💵 Cash on Delivery</div>
                  <div class="small text-muted">Pay when fuel arrives</div>
                </div>
              </label>
            </div>
            <div class="col-md-6">
              <label class="radio-card">
                <input class="form-check-input" type="radio" name="payment_method" value="gcash">
                <div class="radio-card-content">
                  <div class="fw-semibold">📱 GCash</div>
                  <div class="small text-muted">Digital payment</div>
                </div>
              </label>
            </div>
          </div>

          <!-- Delivery Location -->
          <div class="form-section-title">📍 Delivery Location</div>
          <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <label class="form-label mb-0">Set Your Location</label>
              <div class="map-controls">
                <button type="button" id="btnUseMyLocation" class="btn btn-sm btn-outline-secondary map-btn">📍 Use my location</button>
                <button type="button" id="btnTrackMe" class="btn btn-sm btn-outline-secondary map-btn" data-tracking="0">🎯 Track me</button>
              </div>
            </div>
            <div class="map-container" id="map" style="background:#f3f0ff;height:280px;"></div>
          </div>
          
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Latitude</label>
              <input class="form-control" name="delivery_latitude" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label">Longitude</label>
              <input class="form-control" name="delivery_longitude" readonly>
            </div>
            <div class="col-12">
              <label class="form-label">Delivery Address</label>
              <textarea class="form-control" name="delivery_address" rows="2" placeholder="Detected address will appear here"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">City / Municipality</label>
              <input class="form-control" name="delivery_city" placeholder="e.g. Quezon City">
            </div>
            <div class="col-md-6">
              <label class="form-label">Province (optional)</label>
              <input class="form-control" name="delivery_province" placeholder="e.g. Metro Manila">
            </div>
          </div>
          
          <div class="small text-muted mb-4">💡 Drag the pin on the map to adjust your exact delivery location</div>

          <div class="d-flex justify-content-between align-items-center">
            <div class="fw-semibold" style="font-size:1.125rem">
              Total: <span id="total" style="color:#6f2cff">₱0.00</span>
            </div>
            <button class="submit-btn" type="submit">🚀 Place Order</button>
          </div>
        </form>
      </div>
    </div>
    
    <div class="col-lg-5">
      <div class="summary-card">
        <h2>📋 Order Summary</h2>
        <div class="summary-item">
          <div class="summary-label">Vehicle</div>
          <div class="summary-value" id="summaryVehicle">Not selected</div>
        </div>
        <div class="summary-item">
          <div class="summary-label">Fuel Type</div>
          <div class="summary-value" id="summaryFuel">Not selected</div>
        </div>
        <div class="summary-item">
          <div class="summary-label">Quantity</div>
          <div class="summary-value" id="summaryQuantity">0 L</div>
        </div>
        <div class="summary-item">
          <div class="summary-label">Payment Method</div>
          <div class="summary-value" id="summaryPayment">Cash on Delivery</div>
        </div>
        <div class="summary-total">
          <div class="summary-total-label">Total Amount</div>
          <div class="summary-total-value" id="summaryTotal">₱0.00</div>
        </div>
        <div class="mt-3 p-3 bg-white rounded" style="font-size:.875rem">
          <div class="text-muted mb-2">ℹ️ <strong>Note:</strong></div>
          <ul class="mb-0 ps-3" style="line-height:1.8">
            <li>Delivery typically takes 30-60 minutes</li>
            <li>Our drivers will contact you before arrival</li>
            <li>Payment is collected upon delivery</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</main>

    <!-- Try CDN Leaflet first; fallback to local stub if offline -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" onerror="this.remove()"></script>
    <script src="<?= BASE_URL ?>assets/js/leaflet.js"></script>
    <script src="<?= BASE_URL ?>assets/js/emoji-markers.js"></script>
    <script src="<?= BASE_URL ?>assets/js/map.js"></script>

<script>
// Update summary in real-time
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('order-form');
  
  // Vehicle Type
  form.querySelector('[name="vehicle_type"]').addEventListener('change', function(e) {
    document.getElementById('summaryVehicle').textContent = e.target.value || 'Not selected';
  });
  
  // Fuel Type
  form.querySelector('[name="product_id"]').addEventListener('change', function(e) {
    const selectedOption = e.target.options[e.target.selectedIndex];
    document.getElementById('summaryFuel').textContent = selectedOption.text || 'Not selected';
  });
  
  // Quantity
  form.querySelector('[name="quantity_liters"]').addEventListener('input', function(e) {
    const qty = parseFloat(e.target.value) || 0;
    document.getElementById('summaryQuantity').textContent = qty + ' L';
  });
  
  // Payment Method
  form.querySelectorAll('[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function(e) {
      const method = e.target.value === 'cod' ? 'Cash on Delivery' : 'GCash';
      document.getElementById('summaryPayment').textContent = method;
    });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>