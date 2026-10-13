<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Estados del pedido que siguen el diagrama UML de la compra:
 *   Procesando (pago confirmado)  ->  En preparación (almacén)  ->  Enviado (con n.º de seguimiento)  ->  Entregado
 * Al pasar a "Enviado" el cliente recibe un correo con el seguimiento.
 */
class STS_Order_Status {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'wc_order_statuses', array( __CLASS__, 'statuses' ) );
		add_filter( 'woocommerce_order_is_paid_statuses', array( __CLASS__, 'paid_statuses' ) );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( __CLASS__, 'tracking_fields' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'save_tracking' ), 20, 1 );
		add_action( 'woocommerce_order_status_shipped', array( __CLASS__, 'notify_shipped' ), 10, 2 );
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'tracking_frontend' ) );
	}

	public static function register() {
		register_post_status(
			'wc-preparing',
			array(
				'label'                     => 'En preparación',
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'En preparación <span class="count">(%s)</span>', 'En preparación <span class="count">(%s)</span>' ),
			)
		);
		register_post_status(
			'wc-shipped',
			array(
				'label'                     => 'Enviado',
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Enviado <span class="count">(%s)</span>', 'Enviado <span class="count">(%s)</span>' ),
			)
		);
	}

	public static function statuses( $statuses ) {
		$new = array();
		foreach ( $statuses as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'wc-processing' === $key ) {
				$new['wc-preparing'] = 'En preparación';
				$new['wc-shipped']   = 'Enviado';
			}
		}
		if ( isset( $new['wc-completed'] ) ) {
			$new['wc-completed'] = 'Entregado';
		}
		return $new;
	}

	public static function paid_statuses( $statuses ) {
		return array_merge( $statuses, array( 'preparing', 'shipped' ) );
	}

	public static function tracking_fields( $order ) {
		echo '<p class="form-field form-field-wide"><label for="sts_carrier">Courier</label>';
		printf( '<input type="text" id="sts_carrier" name="sts_carrier" value="%s" placeholder="Ej.: Olva Courier"></p>', esc_attr( $order->get_meta( '_sts_carrier' ) ) );
		echo '<p class="form-field form-field-wide"><label for="sts_tracking">N.º de seguimiento</label>';
		printf( '<input type="text" id="sts_tracking" name="sts_tracking" value="%s" placeholder="Se envía al cliente al marcar el pedido como Enviado"></p>', esc_attr( $order->get_meta( '_sts_tracking' ) ) );
	}

	public static function save_tracking( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_POST['sts_carrier'] ) ) {
			$order->update_meta_data( '_sts_carrier', sanitize_text_field( wp_unslash( $_POST['sts_carrier'] ) ) );
		}
		if ( isset( $_POST['sts_tracking'] ) ) {
			$order->update_meta_data( '_sts_tracking', sanitize_text_field( wp_unslash( $_POST['sts_tracking'] ) ) );
		}
		// phpcs:enable
		$order->save_meta_data();
	}

	public static function notify_shipped( $order_id, $order = null ) {
		$order = $order ? $order : wc_get_order( $order_id );
		if ( ! $order || ! $order->get_billing_email() ) {
			return;
		}
		$carrier  = $order->get_meta( '_sts_carrier' );
		$tracking = $order->get_meta( '_sts_tracking' );

		$body  = '<p>Hola ' . esc_html( $order->get_billing_first_name() ) . ',</p>';
		$body .= '<p>Tu pedido <strong>#' . esc_html( $order->get_order_number() ) . '</strong> ya salió de nuestro almacén.</p>';
		if ( $carrier || $tracking ) {
			$body .= '<p>' . ( $carrier ? 'Courier: <strong>' . esc_html( $carrier ) . '</strong><br>' : '' )
				. ( $tracking ? 'N.º de seguimiento: <strong>' . esc_html( $tracking ) . '</strong>' : '' ) . '</p>';
		}
		$body .= '<p>Puedes ver el detalle en <a href="' . esc_url( $order->get_view_order_url() ) . '">Mi cuenta</a>.</p>';

		$mailer = WC()->mailer();
		$mailer->send(
			$order->get_billing_email(),
			'Tu pedido #' . $order->get_order_number() . ' fue enviado',
			$mailer->wrap_message( 'Tu pedido está en camino', $body )
		);
		$order->add_order_note( 'Correo de envío enviado al cliente.' );
	}

	public static function tracking_frontend( $order ) {
		$carrier  = $order->get_meta( '_sts_carrier' );
		$tracking = $order->get_meta( '_sts_tracking' );
		if ( ! $tracking ) {
			return;
		}
		echo '<p class="sts-tracking"><strong>Seguimiento del envío:</strong> '
			. ( $carrier ? esc_html( $carrier ) . ' · ' : '' ) . esc_html( $tracking ) . '</p>';
	}
}
