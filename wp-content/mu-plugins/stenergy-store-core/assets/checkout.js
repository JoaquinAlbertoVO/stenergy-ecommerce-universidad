/* ST Store: el campo de documento cambia según el comprobante (boleta = DNI, factura = RUC). */
jQuery(function ($) {
	function sync() {
		var tipo = $('#billing_comprobante').val() || 'boleta';
		var $doc = $('#billing_documento');
		var $docLabel = $('label[for="billing_documento"]');
		var $razon = $('#billing_razon_social_field');
		var required = '<abbr class="required" title="obligatorio">*</abbr>';

		if (tipo === 'factura') {
			$docLabel.html('RUC ' + required);
			$doc.attr({ maxlength: 11, placeholder: '11 dígitos' });
			$razon.show().addClass('validate-required').find('label').html('Razón social ' + required);
		} else {
			$docLabel.html('DNI ' + required);
			$doc.attr({ maxlength: 8, placeholder: '8 dígitos' });
			$razon.hide().removeClass('validate-required');
		}
	}

	$(document.body).on('change', '#billing_comprobante', sync);
	$(document.body).on('updated_checkout', sync);
	sync();
});
