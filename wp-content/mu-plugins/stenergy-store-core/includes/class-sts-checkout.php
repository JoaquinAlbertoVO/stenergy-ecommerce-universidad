<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checkout adaptado a Perú: boleta (DNI) o factura (RUC + razón social), campos
 * de dirección en español, mensajes de disponibilidad de stock y menos ruido
 * del panel de WooCommerce.
 */
class STS_Checkout {

	public static function init() {
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'fields' ) );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'validate' ) );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'admin_display' ) );
		add_action( 'woocommerce_order_details_after_customer_details', array( __CLASS__, 'frontend_display' ) );
		add_action( 'woocommerce_email_after_order_table', array( __CLASS__, 'email_display' ), 20, 4 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'woocommerce_get_availability', array( __CLASS__, 'availability' ), 10, 2 );
		add_filter( 'woocommerce_get_country_locale', array( __CLASS__, 'peru_locale' ) );

		// Menos avisos y llamadas externas en el panel (demo en localhost).
		add_filter( 'woocommerce_allow_marketplace_suggestions', '__return_false' );
		add_filter( 'woocommerce_helper_suppress_admin_notice', '__return_true' );
		add_filter(
			'woocommerce_admin_features',
			function ( $features ) {
				return array_values( array_diff( $features, array( 'onboarding', 'marketing', 'remote-inbox-notifications', 'remote-free-extensions', 'payment-gateway-suggestions', 'shipping-smart-defaults', 'shipping-setting-tour' ) ) );
			}
		);
	}

	/**
	 * Etiquetas y obligatoriedad de la dirección para Perú (sin código postal).
	 */
	public static function peru_locale( $locales ) {
		$locales['PE'] = array_merge(
			isset( $locales['PE'] ) ? $locales['PE'] : array(),
			array(
				'postcode'  => array(
					'required' => false,
					'hidden'   => true,
				),
				'city'      => array( 'label' => 'Provincia / ciudad' ),
				'state'     => array(
					'label'    => 'Departamento',
					'required' => true,
				),
				'address_2' => array(
					'label'       => 'Distrito y referencia',
					'placeholder' => 'Ej.: Miraflores, cerca al parque Kennedy',
					'required'    => true,
					'label_class' => array(),
				),
			)
		);
		return $locales;
	}

	public static function fields( $fields ) {
		unset( $fields['billing']['billing_company'] );

		$fields['billing']['billing_comprobante'] = array(
			'type'     => 'select',
			'label'    => 'Tipo de comprobante',
			'required' => true,
			'class'    => array( 'form-row-wide' ),
			'priority' => 5,
			'options'  => array(
				'boleta'  => 'Boleta (persona natural, con DNI)',
				'factura' => 'Factura (empresa, con RUC)',
			),
			'default'  => 'boleta',
		);
		$fields['billing']['billing_documento'] = array(
			'type'              => 'text',
			'label'             => 'DNI',
			'required'          => true,
			'class'             => array( 'form-row-wide' ),
			'priority'          => 6,
			'custom_attributes' => array(
				'inputmode'    => 'numeric',
				'maxlength'    => '11',
				'autocomplete' => 'off',
			),
		);
		$fields['billing']['billing_razon_social'] = array(
			'type'     => 'text',
			'label'    => 'Razón social',
			'required' => false,
			'class'    => array( 'form-row-wide', 'sts-razon-social' ),
			'priority' => 7,
		);

		if ( isset( $fields['billing']['billing_address_2'] ) ) {
			$fields['billing']['billing_address_2']['label']       = 'Distrito y referencia';
			$fields['billing']['billing_address_2']['placeholder'] = 'Ej.: Miraflores, cerca al parque Kennedy';
			$fields['billing']['billing_address_2']['required']    = true;
		}
		if ( isset( $fields['billing']['billing_city'] ) ) {
			$fields['billing']['billing_city']['label'] = 'Provincia / ciudad';
		}
		if ( isset( $fields['billing']['billing_state'] ) ) {
			$fields['billing']['billing_state']['label'] = 'Departamento';
		}
		if ( isset( $fields['billing']['billing_postcode'] ) ) {
			$fields['billing']['billing_postcode']['required'] = false;
		}
		$fields['order']['order_comments']['label']       = 'Notas del pedido';
		$fields['order']['order_comments']['placeholder'] = 'Indicaciones de entrega, horario, contacto en obra...';

		return $fields;
	}

	private static function posted() {
		// phpcs:disable WordPress.Security.NonceVerification
		return array(
			'tipo'  => isset( $_POST['billing_comprobante'] ) && 'factura' === $_POST['billing_comprobante'] ? 'factura' : 'boleta',
			'doc'   => isset( $_POST['billing_documento'] ) ? preg_replace( '/\D+/', '', wp_unslash( $_POST['billing_documento'] ) ) : '',
			'razon' => isset( $_POST['billing_razon_social'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['billing_razon_social'] ) ) ) : '',
		);
		// phpcs:enable
	}

	public static function validate() {
		$p = self::posted();
		if ( 'factura' === $p['tipo'] ) {
			if ( ! sts_valid_ruc( $p['doc'] ) ) {
				wc_add_notice( 'El RUC no es válido: debe tener 11 dígitos y empezar con 10, 15, 16, 17 o 20.', 'error' );
			}
			if ( strlen( $p['razon'] ) < 3 ) {
				wc_add_notice( 'Ingresa la razón social para emitir la factura.', 'error' );
			}
		} elseif ( ! preg_match( '/^\d{8}$/', $p['doc'] ) ) {
			wc_add_notice( 'El DNI debe tener 8 dígitos.', 'error' );
		}
	}

	public static function save( $order, $data ) {
		$p = self::posted();
		$order->update_meta_data( '_billing_comprobante', $p['tipo'] );
		$order->update_meta_data( '_billing_documento', $p['doc'] );
		$order->update_meta_data( '_billing_razon_social', 'factura' === $p['tipo'] ? mb_strtoupper( $p['razon'] ) : '' );
	}

	private static function summary( $order ) {
		$tipo = $order->get_meta( '_billing_comprobante' );
		$doc  = $order->get_meta( '_billing_documento' );
		if ( ! $tipo || ! $doc ) {
			return array();
		}
		$rows = array(
			'Comprobante' => 'factura' === $tipo ? 'Factura' : 'Boleta',
			( 'factura' === $tipo ? 'RUC' : 'DNI' ) => $doc,
		);
		if ( 'factura' === $tipo && $order->get_meta( '_billing_razon_social' ) ) {
			$rows['Razón social'] = $order->get_meta( '_billing_razon_social' );
		}
		return $rows;
	}

	public static function admin_display( $order ) {
		$rows = self::summary( $order );
		if ( ! $rows ) {
			return;
		}
		echo '<h3>Comprobante</h3><p>';
		foreach ( $rows as $label => $value ) {
			echo '<strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '<br>';
		}
		echo '</p>';
		if ( $order->get_meta( '_sts_operacion' ) ) {
			echo '<p><strong>N.º de operación Yape/Plin:</strong> ' . esc_html( $order->get_meta( '_sts_operacion' ) ) . '</p>';
		}
	}

	public static function frontend_display( $order ) {
		$rows = self::summary( $order );
		if ( ! $rows ) {
			return;
		}
		echo '<section class="sts-comprobante"><h2 class="woocommerce-column__title">Comprobante solicitado</h2><p>';
		foreach ( $rows as $label => $value ) {
			echo '<strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '<br>';
		}
		echo '</p></section>';
	}

	public static function email_display( $order, $sent_to_admin, $plain_text, $email ) {
		$rows = self::summary( $order );
		if ( ! $rows || $plain_text ) {
			return;
		}
		echo '<p style="margin:0 0 16px"><strong>Comprobante solicitado</strong><br>';
		foreach ( $rows as $label => $value ) {
			echo esc_html( $label ) . ': ' . esc_html( $value ) . '<br>';
		}
		echo '</p>';
	}

	public static function assets() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		wp_enqueue_script( 'sts-checkout', STS_CORE_URL . '/assets/checkout.js', array( 'jquery' ), STS_CORE_VERSION, true );
	}

	/**
	 * Mensajes claros de disponibilidad (el stock es el centro de este proyecto).
	 */
	public static function availability( $availability, $product ) {
		if ( ! $product->managing_stock() ) {
			return $availability;
		}
		$qty = (int) $product->get_stock_quantity();
		$low = (int) ( $product->get_low_stock_amount() ?: get_option( 'woocommerce_notify_low_stock_amount', 5 ) );

		if ( $qty <= 0 && ! $product->backorders_allowed() ) {
			return array(
				'availability' => 'Agotado por ahora',
				'class'        => 'out-of-stock sts-stock-out',
			);
		}
		if ( $qty <= $low ) {
			return array(
				'availability' => sprintf( '¡Últimas %d unidades!', $qty ),
				'class'        => 'in-stock sts-stock-low',
			);
		}
		return array(
			'availability' => sprintf( '%d unidades en stock', $qty ),
			'class'        => 'in-stock sts-stock-ok',
		);
	}
}
