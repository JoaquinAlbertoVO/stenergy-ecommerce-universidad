/* ST Store: menú móvil, menú de catálogo y aviso al agregar al carrito. */
jQuery(function ($) {
	var $nav = $('#sts-nav');

	$('.sts-burger').on('click', function () {
		var open = $nav.toggleClass('is-open').hasClass('is-open');
		$(this).attr('aria-expanded', open);
	});

	$('.sts-catmenu__btn').on('click', function (e) {
		e.stopPropagation();
		$(this).parent().toggleClass('is-open');
	});
	$(document).on('click', function () {
		$('.sts-catmenu').removeClass('is-open');
	});

	function toast(html) {
		var $t = $('.sts-toast');
		if (!$t.length) {
			$t = $('<div class="sts-toast" role="status" aria-live="polite"></div>').appendTo('body');
		}
		$t.html(html);
		requestAnimationFrame(function () { $t.addClass('is-visible'); });
		clearTimeout($t.data('timer'));
		$t.data('timer', setTimeout(function () { $t.removeClass('is-visible'); }, 4200));
	}

	$(document.body).on('added_to_cart', function (e, fragments, hash, $button) {
		var name = $button && $button.attr('aria-label') ? $button.attr('aria-label').replace(/^Añadir «?|»? al carrito$/g, '') : 'Producto';
		var cart = (window.wc_add_to_cart_params && window.wc_add_to_cart_params.cart_url) || '/carrito/';
		toast('✔ <span>' + $('<span>').text(name).html() + ' agregado</span> <a href="' + cart + '">Ver carrito</a>');
	});
});
