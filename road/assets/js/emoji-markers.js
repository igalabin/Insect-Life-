// Emoji-based markers for Leaflet maps
(function() {
  // Create emoji marker icons
  const createEmojiIcon = (emoji, size = 32) => {
    return L.divIcon({
      html: `<div style="font-size: ${size}px; text-align: center; line-height: 1; background: white; border-radius: 50%; width: ${size}px; height: ${size}px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">${emoji}</div>`,
      iconSize: [size, size],
      iconAnchor: [size/2, size/2],
      popupAnchor: [0, -size/2],
      className: 'emoji-marker'
    });
  };

  // Predefined emoji markers
  window.EmojiMarkers = {
    location: createEmojiIcon('📍', 32),
    delivery: createEmojiIcon('🚚', 32),
    user: createEmojiIcon('👤', 28),
    branch: createEmojiIcon('🏢', 32),
    fuel: createEmojiIcon('⛽', 32),
    home: createEmojiIcon('🏠', 32),
    office: createEmojiIcon('🏢', 32),
    truck: createEmojiIcon('🚛', 32),
    pin: createEmojiIcon('📌', 28),
    star: createEmojiIcon('⭐', 28),
    fire: createEmojiIcon('🔥', 28),
    check: createEmojiIcon('✅', 28),
    warning: createEmojiIcon('⚠️', 28),
    info: createEmojiIcon('ℹ️', 28)
  };

  // Helper function to create emoji markers
  window.createEmojiMarker = function(lat, lng, emoji = '📍', options = {}) {
    const icon = createEmojiIcon(emoji, options.size || 32);
    const markerOptions = {
      icon: icon,
      draggable: options.draggable || false
    };
    
    return L.marker([lat, lng], markerOptions);
  };

  // Override default marker to use emoji if no custom icon provided
  const originalMarker = L.marker;
  L.marker = function(latlng, options = {}) {
    // If no custom icon is provided, use location emoji
    if (!options.icon) {
      options.icon = createEmojiIcon('📍', 32);
    }
    return originalMarker.call(this, latlng, options);
  };

  // Add CSS for emoji markers
  const style = document.createElement('style');
  style.textContent = `
    .emoji-marker {
      background: transparent !important;
      border: none !important;
    }
    .emoji-marker div {
      transition: transform 0.2s ease;
    }
    .emoji-marker:hover div {
      transform: scale(1.1);
    }
  `;
  document.head.appendChild(style);

})();
