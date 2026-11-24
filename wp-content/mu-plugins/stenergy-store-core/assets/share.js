/* ST Store: compartir un producto (menú del celular, WhatsApp, Facebook, copiar enlace) y evento "share" de GA4. */
(function () {
	var box = document.querySelector('.sts-share[data-url]');
	if (!box) {
		return;
	}
	var native = box.querySelector('[data-share="native"]');
	var ok = box.querySelector('.sts-share__ok');

	function track(method) {
		if (typeof window.gtag === 'function') {
			window.gtag('event', 'share', { method: method, content_type: 'product', item_id: box.getAttribute('data-item-id') });
		}
	}

	if (navigator.share) {
		native.hidden = false;
	}

	box.addEventListener('click', function (e) {
		var b = e.target.closest('[data-share]');
		if (!b) {
			return;
		}
		var method = b.getAttribute('data-share');
		if (method === 'native') {
			navigator.share({ title: box.getAttribute('data-title'), url: box.getAttribute('data-url') })
				.then(function () { track('menu_compartir'); })
				.catch(function () { /* el usuario cerró el menú */ });
			return;
		}
		if (method === 'copy') {
			var url = box.getAttribute('data-copy-url');
			var done = function () { ok.textContent = 'Enlace copiado'; setTimeout(function () { ok.textContent = ''; }, 2500); track('copiar_enlace'); };
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(url).then(done, function () { window.prompt('Copia el enlace:', url); });
			} else {
				window.prompt('Copia el enlace:', url);
			}
			return;
		}
		track(method);
	});
})();
