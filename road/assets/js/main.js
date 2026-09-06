(() => {
  // Global helpers
  window.RoadFuel = {
    formatCurrency(value) {
      return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0));
    }
  };
  // Online/offline banner toggle (optional usage)
  function setOnlineStatus() {
    const banner = document.getElementById('offlineBanner');
    if (!banner) return;
    banner.style.display = navigator.onLine ? 'none' : 'block';
  }
  window.addEventListener('online', setOnlineStatus);
  window.addEventListener('offline', setOnlineStatus);
  setOnlineStatus();

  // Minimal navbar collapse fallback only when Bootstrap JS is not present
  if (!window.bootstrap) {
    document.addEventListener('click', function(e){
      var toggler = e.target.closest('[data-bs-toggle="collapse"]');
      if (!toggler) return;
      var target = toggler.getAttribute('data-bs-target') || toggler.getAttribute('href');
      if (!target) return;
      try {
        var el = document.querySelector(target);
        if (!el) return;
        e.preventDefault();
        el.classList.toggle('show');
      } catch(_){}
    });
  }
})();

// Basic interactive behaviors for the landing page
(() => {
	const onReady = (fn) => {
		if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
	};

	onReady(() => {
		// Smooth scroll for anchor links
		document.querySelectorAll('a[href^="#"]').forEach(link => {
			link.addEventListener('click', (e) => {
				const targetId = link.getAttribute('href');
				const target = document.querySelector(targetId);
				if (target) {
					e.preventDefault();
					target.scrollIntoView({ behavior: 'smooth', block: 'start' });
				}
			});
		});

		// Navbar toggler icon color visibility fix for purple bg
		const toggler = document.querySelector('.navbar-toggler');
		if (toggler && !toggler.querySelector('.navbar-toggler-icon')) {
			const span = document.createElement('span');
			span.className = 'navbar-toggler-icon';
			toggler.appendChild(span);
		}
	});
})();

