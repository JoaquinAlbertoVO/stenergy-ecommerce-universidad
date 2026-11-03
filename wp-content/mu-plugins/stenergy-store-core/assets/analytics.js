/* ST Store: eventos de Google Analytics 4 que ocurren en el navegador (los datos vienen de window.stsGa4). */
jQuery(function ($) {
	var ga = window.stsGa4;
	if (!ga || typeof window.gtag !== 'function') {
		return;
	}

	function send(name, params) {
		window.gtag('event', name, params || {});
	}

	function copy(obj, extra) {
		return $.extend({}, obj, extra || {});
	}

	function value(items) {
		var total = 0;
		$.each(items, function (i, it) { total += it.price * it.quantity; });
		return Math.round(total * 100) / 100;
	}

	function cartItems() {
		return $.map(ga.cart || {}, function (it) { return it; });
	}

	// 1) Eventos que el servidor dejó listos para esta página (view_item, view_cart, purchase...).
	$.each(ga.events || [], function (i, ev) { send(ev[0], ev[1]); });

	// 2) select_item: clic en un producto de un listado.
	$(document.body).on('click', 'ul.products li.product a:not(.add_to_cart_button):not(.added_to_cart)', function () {
		var id = productIdFrom($(this).closest('li.product'));
		var it = id && ga.products[id];
		if (it) {
			send('select_item', { item_list_id: ga.list.id, item_list_name: ga.list.name, items: [it] });
		}
	});

	function productIdFrom($li) {
		var m = ($li.attr('class') || '').match(/(?:^|\s)post-(\d+)(?:\s|$)/);
		return m ? m[1] : null;
	}

	// 3) add_to_cart desde los botones AJAX del catálogo (WooCommerce dispara "added_to_cart").
	$(document.body).on('added_to_cart', function (e, fragments, hash, $button) {
		if (!$button || !$button.length) {
			return;
		}
		var id = String($button.data('product_id') || '');
		var base = ga.products[id];
		if (!base) {
			return;
		}
		var it = copy(base, { quantity: parseInt($button.data('quantity'), 10) || 1, item_list_id: ga.list.id, item_list_name: ga.list.name });
		send('add_to_cart', { currency: ga.currency, value: value([it]), items: [it] });
	});

	// 4) remove_from_cart: la "x" de cada fila en la página del carrito.
	$(document.body).on('click', '.woocommerce-cart-form a.remove', function () {
		var m = (this.getAttribute('href') || '').match(/[?&]remove_item=([^&#]+)/);
		var it = m && ga.cart[decodeURIComponent(m[1])];
		if (it) {
			send('remove_from_cart', { currency: ga.currency, value: value([it]), items: [it] });
			delete ga.cart[decodeURIComponent(m[1])];
		}
	});

	// 5) add_shipping_info y add_payment_info: al pulsar "Realizar el pedido".
	var checkoutSent = false;
	$('form.checkout').on('checkout_place_order', function () {
		if (checkoutSent) {
			return;
		}
		checkoutSent = true;
		var items = cartItems();
		var base = { currency: ga.currency, value: value(items), items: items };
		var $ship = $('input[name^="shipping_method"]:checked, input[type="hidden"][name^="shipping_method"]').first();
		if ($ship.length) {
			send('add_shipping_info', copy(base, { shipping_tier: labelFor($ship) || $ship.val() }));
		}
		var $pay = $('input[name="payment_method"]:checked');
		if ($pay.length) {
			send('add_payment_info', copy(base, { payment_type: labelFor($pay) || $pay.val() }));
		}
		// No devolver nada: devolver false cancelaría el pedido.
	});

	function labelFor($input) {
		var id = $input.attr('id');
		var $label = id ? $('label[for="' + id + '"]').first() : $();
		if (!$label.length) {
			$label = $input.closest('li').find('label').first();
		}
		// Sin el precio: "Delivery en Lima y Callao (24 a 48 h): S/ 15.00" -> "Delivery en Lima y Callao (24 a 48 h)".
		var text = $label.clone().find('.amount, .woocommerce-Price-amount').remove().end().text();
		return $.trim(text.replace(/\s+/g, ' ').replace(/[:\s]+$/, ''));
	}

	// 6) Eventos propios de ST Store.
	$(document.body).on('click', '.sts-bot__fab', function () {
		setTimeout(function () {
			var root = document.getElementById('sts-chatbot');
			if (root && root.classList.contains('is-open')) {
				send('chatbot_open', { page_location: window.location.href });
			}
		}, 0);
	});

	$(document.body).on('click', 'a[href*="wa.me/"]', function () {
		var where = $(this).closest('header, .sts-topbar').length ? 'encabezado'
			: $(this).closest('footer').length ? 'pie'
			: $(this).closest('#sts-chatbot').length ? 'chatbot'
			: 'contenido';
		send('whatsapp_click', { link_location: where, link_url: this.href.split('?')[0] });
	});
});
