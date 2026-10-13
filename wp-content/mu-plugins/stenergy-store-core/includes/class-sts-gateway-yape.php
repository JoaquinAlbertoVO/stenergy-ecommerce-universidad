<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Yape / Plin (pago manual): el cliente paga desde su celular y registra el
 * número de operación. El pedido queda "En espera" hasta que el administrador
 * valida el pago y lo pasa a "Procesando". El stock se reserva al crear el pedido.
 */
class STS_Gateway_Yape extends WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'sts_yape';
		$this->method_title       = 'Yape / Plin';
		$this->method_description = 'Pago manual: el cliente registra el número de operación y la tienda lo valida.';
		$this->has_fields         = true;
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou' ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'   => 'Activar',
				'type'    => 'checkbox',
				'label'   => 'Activar Yape / Plin',
				'default' => 'yes',
			),
			'title'       => array(
				'title'   => 'Título',
				'type'    => 'text',
				'default' => 'Yape / Plin',
			),
			'description' => array(
				'title'   => 'Descripción',
				'type'    => 'textarea',
				'default' => 'Paga desde tu celular y registra el número de operación.',
			),
			'number'      => array(
				'title'   => 'Número de celular',
				'type'    => 'text',
				'default' => '999 999 999',
			),
			'holder'      => array(
				'title'   => 'Titular',
				'type'    => 'text',
				'default' => 'ST Store S.A.C. (ejemplo)',
			),
		);
	}

	public function payment_fields() {
		if ( $this->description ) {
			echo '<p>' . esc_html( $this->description ) . '</p>';
		}
		printf(
			'<p>Envía el total del pedido al <strong>%s</strong> a nombre de <strong>%s</strong>, luego escribe aquí el número de operación.</p>',
			esc_html( $this->get_option( 'number' ) ),
			esc_html( $this->get_option( 'holder' ) )
		);
		echo '<p class="form-row form-row-wide"><label for="sts_yape_operacion">Número de operación <span class="required">*</span></label>';
		echo '<input id="sts_yape_operacion" name="sts_yape_operacion" type="text" inputmode="numeric" autocomplete="off" maxlength="12" placeholder="Ej.: 12345678"></p>';
	}

	private function posted_operation() {
		// phpcs:ignore WordPress.Security.NonceVerification
		return isset( $_POST['sts_yape_operacion'] ) ? preg_replace( '/\D+/', '', wp_unslash( $_POST['sts_yape_operacion'] ) ) : '';
	}

	public function validate_fields() {
		if ( ! preg_match( '/^\d{6,12}$/', $this->posted_operation() ) ) {
			wc_add_notice( 'Ingresa el número de operación de Yape/Plin (de 6 a 12 dígitos).', 'error' );
			return false;
		}
		return true;
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		$op    = $this->posted_operation();

		$order->update_meta_data( '_sts_operacion', $op );
		$order->update_status( 'on-hold', 'Pago por Yape/Plin pendiente de validación. N.º de operación: ' . $op . '.' );
		$order->save();
		WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	public function thankyou( $order_id ) {
		echo '<p class="sts-notice">Recibimos tu número de operación. Validaremos el pago y te avisaremos por correo cuando el pedido pase a preparación.</p>';
	}
}
