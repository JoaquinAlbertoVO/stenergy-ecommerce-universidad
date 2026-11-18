/* ST Store: aviso de cookies. Guarda la elección y actualiza Consent Mode v2, GA4 y el origen de pedidos de WooCommerce. */
(function () {
	var cfg = window.stsConsentCfg || { cookie: 'sts_cookie_consent', days: 180 };
	var state = window.stsConsent;
	var box = document.getElementById('sts-consent');
	if (!state || !box) {
		return;
	}
	var prefs = document.getElementById('sts-consent-prefs');
	var check = document.getElementById('sts-consent-analytics');
	var btn = function (name) { return box.querySelector('[data-consent="' + name + '"]'); };

	function show(withPrefs) {
		box.hidden = false;
		togglePrefs(!!withPrefs);
		(withPrefs ? check : btn('accept')).focus();
	}

	function hide() {
		box.hidden = true;
	}

	function togglePrefs(open) {
		prefs.hidden = !open;
		check.checked = state.analytics;
		btn('settings').hidden = open;
		btn('settings').setAttribute('aria-expanded', open ? 'true' : 'false');
		btn('save').hidden = !open;
	}

	function domains() {
		var parts = location.hostname.split('.');
		var list = [''];
		for (var i = 0; i < parts.length - 1; i++) {
			list.push('; domain=.' + parts.slice(i).join('.'));
		}
		list.push('; domain=' + location.hostname);
		return list;
	}

	function removeAnalyticsCookies() {
		document.cookie.split(';').forEach(function (c) {
			var name = c.split('=')[0].trim();
			if (/^_ga(_|$)/.test(name)) {
				domains().forEach(function (d) {
					document.cookie = name + '=; path=/; max-age=0' + d;
				});
			}
		});
	}

	function save(analytics) {
		var secure = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = cfg.cookie + '=' + (analytics ? 'v1.a1' : 'v1.a0') + '; path=/; max-age=' + (cfg.days * 86400) + '; SameSite=Lax' + secure;
		state.decided = true;

		if (typeof window.gtag === 'function') {
			window.gtag('consent', 'update', { analytics_storage: analytics ? 'granted' : 'denied' });
		}
		if (analytics) {
			state._grant();
		} else {
			state.analytics = false;
			removeAnalyticsCookies();
		}
		if (window.wc_order_attribution && typeof window.wc_order_attribution.setOrderTracking === 'function') {
			window.wc_order_attribution.setOrderTracking(analytics);
		}
		hide();
	}

	box.addEventListener('click', function (e) {
		var b = e.target.closest('[data-consent]');
		if (!b) {
			return;
		}
		var action = b.getAttribute('data-consent');
		if (action === 'accept') { save(true); }
		if (action === 'reject') { save(false); }
		if (action === 'settings') { togglePrefs(true); check.focus(); }
		if (action === 'save') { save(check.checked); }
	});

	box.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && state.decided) {
			hide();
		}
	});

	// Cualquier enlace a #preferencias-cookies (footer, Política de cookies) vuelve a abrir el aviso.
	document.addEventListener('click', function (e) {
		var a = e.target.closest('a[href$="#preferencias-cookies"]');
		if (a) {
			e.preventDefault();
			show(true);
		}
	});

	if (!state.decided) {
		box.hidden = false;
	}
	if (location.hash === '#preferencias-cookies') {
		show(true);
	}
})();
