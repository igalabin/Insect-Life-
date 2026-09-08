// Map logic for driver order page using Leaflet + OpenStreetMap Nominatim
(function(){
  if (!document.getElementById('map')) return;
  if (typeof L === 'undefined') {
    // Leaflet not available (offline/no local file). Graceful no-op.
    var mapBox = document.getElementById('map');
    if (mapBox) {
      mapBox.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted">Map unavailable offline</div>';
    }
    return;
  }

  // Load emoji markers script
  if (typeof createEmojiMarker === 'undefined') {
    const script = document.createElement('script');
    script.src = 'assets/js/emoji-markers.js';
    script.onload = function() {
      initializeMap();
    };
    script.onerror = function() {
      initializeMap();
    };
    document.head.appendChild(script);
    return;
  }

  initializeMap();

  function initializeMap() {

  // Ensure the map container has a height
  (function(){
    var box = document.getElementById('map');
    if (box) {
      var h = box.getBoundingClientRect().height;
      if (!h || h < 50) { box.style.height = '260px'; }
    }
  })();

  const map = L.map('map');
  // Set an immediate default view so the map renders even before geolocation
  try { map.setView([14.5995, 120.9842], 12); } catch(_) {}
  const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap'
  }).addTo(map);

  const latInput = document.querySelector('input[name="delivery_latitude"]');
  const lngInput = document.querySelector('input[name="delivery_longitude"]');
  const addrInput = document.querySelector('textarea[name="delivery_address"]');
  const btnLocate = document.getElementById('btnUseMyLocation');
  const btnTrack = document.getElementById('btnTrackMe');
  const productSelect = document.querySelector('select[name="product_id"]');
  const qtyInput = document.querySelector('input[name="quantity_liters"]');
  const cityInput = document.querySelector('input[name="delivery_city"]');
  const provinceInput = document.querySelector('input[name="delivery_province"]');
  const branchSelectHolder = document.createElement('div');
  branchSelectHolder.className = 'mt-2';
  let branchSelect = null;
  const totalEl = document.getElementById('total');
  const form = document.getElementById('order-form');

  let marker;
  let watchId = null;
  let meMarker = null;
  let meCircle = null;

  function setPosition(lat, lng, fly = false) {
    if (!marker) {
      // Use emoji marker for delivery location
      if (typeof createEmojiMarker !== 'undefined') {
        marker = createEmojiMarker(lat, lng, '📍', { draggable: true }).addTo(map);
      } else {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
      }
      marker.on('dragend', function(){
        const p = marker.getLatLng();
        updateFields(p.lat, p.lng);
        reverseGeocode(p.lat, p.lng);
      });
    } else {
      marker.setLatLng([lat, lng]);
    }
    if (fly) map.flyTo([lat, lng], 17); else map.setView([lat, lng], 17);
    updateFields(lat, lng);
    reverseGeocode(lat, lng);
  }

  function updateFields(lat, lng) {
    if (latInput) latInput.value = lat.toFixed(6);
    if (lngInput) lngInput.value = lng.toFixed(6);
  }

  async function reverseGeocode(lat, lng) {
    try {
      const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`;
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return;
      const data = await res.json();
      if (addrInput && data && data.display_name) {
        addrInput.value = data.display_name;
      }
    } catch(e) {
      // ignore
    }
  }

  function geolocate() {
    if (!navigator.geolocation) {
      map.setView([14.5995, 120.9842], 12); // Manila fallback
      return;
    }
    navigator.geolocation.getCurrentPosition(function(pos){
      setPosition(pos.coords.latitude, pos.coords.longitude, true);
    }, function(){
      map.setView([14.5995, 120.9842], 12);
    }, { enableHighAccuracy: true, timeout: 10000 });
  }

  if (btnLocate) btnLocate.addEventListener('click', function(e){ e.preventDefault(); geolocate(); });

  function updateMe(lat, lng, acc){
    if (!meMarker) {
      // Use emoji marker for user location
      if (typeof createEmojiMarker !== 'undefined') {
        meMarker = createEmojiMarker(lat, lng, '👤', { size: 28 }).addTo(map);
      } else {
        meMarker = L.marker([lat, lng]).addTo(map);
      }
    } else {
      meMarker.setLatLng([lat, lng]);
    }
    if (!meCircle) {
      meCircle = L.circle([lat, lng], { radius: acc||15, color: '#6f2cff', fillColor: '#6f2cff', fillOpacity: 0.15 }).addTo(map);
    } else {
      meCircle.setLatLng([lat, lng]);
      if (acc) meCircle.setRadius(acc);
    }
  }

  let hasCenteredOnMe = false;

  function startTracking(){
    if (!navigator.geolocation || watchId) return;
    watchId = navigator.geolocation.watchPosition(function(p){
      updateMe(p.coords.latitude, p.coords.longitude, p.coords.accuracy);
      if (!hasCenteredOnMe) {
        try { map.flyTo([p.coords.latitude, p.coords.longitude], 17); } catch(_) {}
        hasCenteredOnMe = true;
      }
    }, function(){}, { enableHighAccuracy: true, maximumAge: 2000 });
  }

  function stopTracking(){
    if (watchId && navigator.geolocation) {
      navigator.geolocation.clearWatch(watchId);
      watchId = null;
      hasCenteredOnMe = false;
    }
  }

  if (btnTrack) btnTrack.addEventListener('click', function(e){
    e.preventDefault();
    const tracking = this.getAttribute('data-tracking') === '1';
    if (tracking) {
      stopTracking();
      this.setAttribute('data-tracking', '0');
      this.textContent = 'Track me';
    } else {
      startTracking();
      this.setAttribute('data-tracking', '1');
      this.textContent = 'Stop tracking';
    }
  });

  // Initialize
  geolocate();

  // Load products
  async function loadProducts(){
    try {
      const res = await fetch((window.BASE_URL||'/') + 'api/orders.php?action=products');
      const data = await res.json();
      if (!data.ok) return;
      if (!productSelect) return;
      productSelect.innerHTML = '<option value="">Select</option>' + data.data.map(p => `<option value="${p.product_id}" data-price="${p.price_per_liter}">${p.product_name} (₱${Number(p.price_per_liter).toFixed(2)}/L)</option>`).join('');
    } catch(e) {}
  }
  loadProducts();

  function computeTotal(){
    if (!productSelect || !qtyInput || !totalEl) return;
    const opt = productSelect.options[productSelect.selectedIndex];
    const price = opt ? Number(opt.getAttribute('data-price')||0) : 0;
    const qty = Number(qtyInput.value||0);
    const fee = 50; // default; could fetch from settings API later
    const sub = price * qty;
    const tot = sub + fee;
    totalEl.textContent = window.RoadFuel ? window.RoadFuel.formatCurrency(tot) : `₱${tot.toFixed(2)}`;
  }
  if (productSelect) productSelect.addEventListener('change', computeTotal);
  if (qtyInput) qtyInput.addEventListener('input', computeTotal);

  // Submit order
  if (form) form.addEventListener('submit', async function(e){
    e.preventDefault();
    // Ensure preferred_branch_id if branchSelect is present
    if (branchSelect && branchSelect.value) {
      const hidden = document.createElement('input');
      hidden.type = 'hidden'; hidden.name = 'preferred_branch_id'; hidden.value = branchSelect.value;
      form.appendChild(hidden);
    }
    const fd = new FormData(form);
    fd.append('action', 'create');
    try {
      const res = await fetch((window.BASE_URL||'/') + 'api/orders.php', { method:'POST', body: fd });
      const data = await res.json();
      if (data.ok) {
        alert('Order created! #' + data.order_id);
        window.location.href = (window.BASE_URL||'/') + 'driver/my-orders.php';
      } else {
        alert(data.error || 'Failed to create order');
      }
    } catch(err){ alert('Network error'); }
  });

  // Load candidate branches when location/product/qty/city change
  async function loadBranchOptions(){
    if (!productSelect || !qtyInput) return;
    const pid = Number(productSelect.value||0);
    const qty = Number(qtyInput.value||0);
    const lat = Number(latInput && latInput.value || 0);
    const lng = Number(lngInput && lngInput.value || 0);
    const city = (cityInput && cityInput.value) || '';
    const prov = (provinceInput && provinceInput.value) || '';
    if (!pid || !qty || (!lat && !city)) { if (branchSelectHolder.parentNode) branchSelectHolder.remove(); return; }
    try {
      const fd = new FormData();
      fd.append('action', 'branch_options');
      fd.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
      fd.append('product_id', String(pid));
      fd.append('quantity_liters', String(qty));
      fd.append('delivery_latitude', String(lat));
      fd.append('delivery_longitude', String(lng));
      fd.append('delivery_city', city);
      fd.append('delivery_province', prov);
      const res = await fetch((window.BASE_URL||'/') + 'api/orders.php', { method:'POST', body: fd });
      const data = await res.json();
      if (!data.ok) { if (branchSelectHolder.parentNode) branchSelectHolder.remove(); return; }
      const list = data.data||[];
      if (!list.length) { if (branchSelectHolder.parentNode) branchSelectHolder.remove(); return; }
      // Build select
      branchSelectHolder.innerHTML = '';
      const label = document.createElement('label'); label.className = 'form-label'; label.textContent = 'Choose branch';
      branchSelect = document.createElement('select'); branchSelect.className = 'form-select'; branchSelect.name = 'preferred_branch_id';
      branchSelect.innerHTML = '<option value="">Best available</option>' + list.map(b => `<option value="${b.branch_id}">${b.branch_name}${b.distance_km?` (${Number(b.distance_km).toFixed(1)} km)`:''}</option>`).join('');
      branchSelectHolder.appendChild(label); branchSelectHolder.appendChild(branchSelect);
      // Insert right below the address block
      const addrBlock = addrInput && addrInput.closest('.row');
      if (addrBlock && addrBlock.parentNode) {
        addrBlock.parentNode.insertBefore(branchSelectHolder, addrBlock.nextSibling);
      }
    } catch(_) { if (branchSelectHolder.parentNode) branchSelectHolder.remove(); }
  }

  // re-evaluate when critical fields change
  if (productSelect) productSelect.addEventListener('change', loadBranchOptions);
  if (qtyInput) qtyInput.addEventListener('input', loadBranchOptions);
  if (cityInput) cityInput.addEventListener('input', loadBranchOptions);
  if (provinceInput) provinceInput.addEventListener('input', loadBranchOptions);
  if (latInput) latInput.addEventListener('change', loadBranchOptions);
  if (lngInput) lngInput.addEventListener('change', loadBranchOptions);
  } // end initializeMap function
})();


