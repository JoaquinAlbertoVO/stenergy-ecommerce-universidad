<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pasarela de pago SIMULADA para la demostración.
 *
 * No procesa ni guarda tarjetas reales: solo acepta dos números de prueba y
 * rechaza cualquier otro. Sirve para recorrer en vivo el flujo
 * "¿Pago aprobado? sí/no" del diagrama UML.
 *   4242 4242 4242 4242  -> pago aprobado
 *   4000 0000 0000 0002  -> pago rechazado
 */
class STS_Gateway_Card_Sim extends WC_Payment_Gateway {

	const OK   = '4242424242424242';
	const FAIL = '4000000000000002';

	public function __construct() {
		$this->id                 = 'sts_card_sim';
		$this->method_title       = 'Tarjeta (pasarela simulada)';
		$this->method_description = 'Pasarela de pruebas para la demostración. No cobra tarjetas reales.';
		$this->has_fields         = true;
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->enabled     = $this->get_option( 'enabled' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'   => 'Activar',
				'type'    => 'checkbox',
				'label'   => 'Activar pasarela simulada',
				'default' => 'yes',
			),
			'title'       => array(
				'title'   => 'Título',
				'type'    => 'text',
				'default' => 'Tarjeta de crédito o débito (pasarela simulada)',
			),
			'description' => array(
				'title'   => 'Descripción',
				'type'    => 'textarea',
				'default' => 'Simulación para la demostración: no se procesan tarjetas reales.',
			),
		);
	}

	public function payment_fields() {
		if ( $this->description ) {
			echo '<p>' . esc_html( $this->description ) . '</p>';
		}
		echo '<div class="sts-card-sim">';
		echo '<p class="form-row form-row-wide"><label for="sts_card_number">Número de tarjeta de prueba <span class="required">*</span></label>';
		echo '<input id="sts_card_number" name="sts_card_number" type="text" inputmode="numeric" autocomplete="off" maxlength="19" placeholder="4242 4242 4242 4242"></p>';
		echo '<p class="sts-hint"><strong>Aprobada:</strong> 4242 4242 4242 4242 &nbsp;·&nbsp; <strong>Rechazada:</strong> 4000 0000 0000 0002</p>';
		echo '</div>';
	}

	private function posted_number() {
		// phpcs:ignore WordPress.Security.NonceVerification
		return isset( $_POST['sts_card_number'] ) ? preg_replace( '/\D+/', '', wp_unslash( $_POST['sts_card_number'] ) ) : '';
	}

	public function validate_fields() {
		if ( ! in_array( $this->posted_number(), array( self::OK, self::FAIL ), true ) ) {
			wc_add_notice( 'Pasarela simulada: usa la tarjeta de prueba 4242 4242 4242 4242 (aprobada) o 4000 0000 0000 0002 (rechazada). Nunca ingreses una tarjeta real.', 'error' );
			return false;
		}
		return true;
	}

	public function process_payment( $order_id ) {
		$order  = wc_get_order( $order_id );
		$number = $this->posted_number();

		if ( self::FAIL === $number ) {
			$order->update_status( 'failed', 'Pago rechazado por la pasarela simulada (tarjeta de prueba terminada en 0002).' );
			wc_add_notice( 'Tu pago fue rechazado por el banco emisor (simulación). Intenta con otra tarjeta u otro método de pago.', 'error' );
			return array( 'result' => 'failure' );
		}

		$order->payment_complete( 'SIM-' . strtoupper( wp_generate_password( 10, false, false ) ) );
		$order->add_order_note( 'Pago aprobado en la pasarela simulada (tarjeta de prueba terminada en ' . substr( $number, -4 ) . ').' );
		WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}
}
