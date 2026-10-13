<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Configuración completa de la tienda para Perú. Es idempotente: se puede
 * ejecutar varias veces (ST Store > Estado > "Re-aplicar configuración").
 */
class STS_Setup {

	/** Cuenta de cliente de demostración (solo localhost). */
	const DEMO_USER     = 'cliente.demo';
	const DEMO_EMAIL    = 'cliente.demo@ststore.test';
	const DEMO_PASSWORD = 'Cliente-Demo-2026';

	public static function run() {
		self::language_and_theme();
		self::store_options();
		$pages = self::pages();
		self::taxes();
		$ship = self::shipping();
		self::payments( $ship );
		self::coupons();
		self::demo_customer();
		$report = STS_Seeder::import_all();
		self::menus( $pages );
		self::permalinks();

		update_option( 'sts_setup_report', $report, false );
		update_option( 'sts_setup_done', STS_SETUP_VERSION );
		delete_option( 'sts_setup_attempts' );
		return $report;
	}

	/* ------------------------------------------------------------------ */

	private static function language_and_theme() {
		if ( file_exists( WP_LANG_DIR . '/es_ES.mo' ) || file_exists( WP_LANG_DIR . '/es_ES.l10n.php' ) ) {
			update_option( 'WPLANG', 'es_ES' );
		}
		update_option( 'timezone_string', 'America/Lima' );
		update_option( 'date_format', 'd/m/Y' );
		update_option( 'time_format', 'H:i' );
		update_option( 'blogname', 'ST Store' );
		update_option( 'blogdescription', 'Equipos y materiales de ingeniería eléctrica con stock real' );

		if ( wp_get_theme( 'stenergy-store' )->exists() && get_stylesheet() !== 'stenergy-store' ) {
			switch_theme( 'stenergy-store' );
		}
	}

	private static function store_options() {
		$options = array(
			// Tienda / moneda (Perú, soles).
			'woocommerce_store_address'                   => 'Av. Ejemplo 123',
			'woocommerce_store_city'                      => 'Lima',
			'woocommerce_default_country'                 => 'PE:LMA',
			'woocommerce_store_postcode'                  => '15001',
			'woocommerce_currency'                        => 'PEN',
			'woocommerce_currency_pos'                    => 'left_space',
			'woocommerce_price_thousand_sep'              => ',',
			'woocommerce_price_decimal_sep'               => '.',
			'woocommerce_price_num_decimals'              => '2',
			'woocommerce_allowed_countries'               => 'specific',
			'woocommerce_specific_allowed_countries'      => array( 'PE' ),
			'woocommerce_ship_to_countries'               => '',
			'woocommerce_ship_to_destination'             => 'billing_only',
			'woocommerce_default_customer_address'        => 'base',
			// IGV incluido en el precio.
			'woocommerce_calc_taxes'                      => 'yes',
			'woocommerce_prices_include_tax'              => 'yes',
			'woocommerce_tax_based_on'                    => 'base',
			'woocommerce_tax_display_shop'                => 'incl',
			'woocommerce_tax_display_cart'                => 'incl',
			'woocommerce_price_display_suffix'            => ' <small>IGV incl.</small>',
			'woocommerce_tax_total_display'               => 'itemized',
			// Inventario.
			'woocommerce_manage_stock'                    => 'yes',
			'woocommerce_hold_stock_minutes'              => '60',
			'woocommerce_notify_low_stock'                => 'yes',
			'woocommerce_notify_no_stock'                 => 'yes',
			'woocommerce_notify_low_stock_amount'         => '5',
			'woocommerce_notify_no_stock_amount'          => '0',
			'woocommerce_hide_out_of_stock_items'         => 'no',
			'woocommerce_stock_format'                    => '',
			// Compra y cuentas.
			'woocommerce_enable_guest_checkout'           => 'yes',
			'woocommerce_enable_checkout_login_reminder'  => 'yes',
			'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
			'woocommerce_enable_myaccount_registration'   => 'yes',
			'woocommerce_registration_generate_password'  => 'no',
			'woocommerce_registration_generate_username'  => 'yes',
			'woocommerce_enable_coupons'                  => 'yes',
			'woocommerce_calc_discounts_sequentially'     => 'no',
			'woocommerce_cart_redirect_after_add'         => 'no',
			'woocommerce_enable_ajax_add_to_cart'         => 'yes',
			'woocommerce_enable_reviews'                  => 'yes',
			'woocommerce_enable_review_rating'            => 'yes',
			'woocommerce_review_rating_required'          => 'yes',
			'woocommerce_review_rating_verification_label' => 'yes',
			'woocommerce_review_rating_verification_required' => 'no',
			// Correos.
			'woocommerce_email_from_name'                 => 'ST Store',
			'woocommerce_email_from_address'              => 'ventas@ststore.test',
			'woocommerce_email_footer_text'               => 'ST Store · Proyecto universitario · {site_title}',
			// Sin asistentes ni avisos externos.
			'woocommerce_coming_soon'                     => 'no',
			'woocommerce_store_pages_only'                => 'no',
			'woocommerce_allow_tracking'                  => 'no',
			'woocommerce_show_marketplace_suggestions'    => 'no',
			'woocommerce_task_list_hidden'                => 'yes',
			'woocommerce_task_list_complete'              => 'yes',
			'woocommerce_onboarding_profile'              => array(
				'completed' => true,
				'skipped'   => true,
			),
		);
		foreach ( $options as $key => $value ) {
			update_option( $key, $value );
		}
	}

	/* ------------------------------------------------------------------ */

	private static function upsert_page( $slug, $title, $content, $extra = array() ) {
		$page = get_page_by_path( $slug );
		$args = array_merge(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			),
			$extra
		);
		if ( $page ) {
			$args['ID'] = $page->ID;
			wp_update_post( $args );
			return $page->ID;
		}
		return (int) wp_insert_post( $args );
	}

	/**
	 * Reutiliza la página que WooCommerce creó al activarse (si existe) para no duplicar.
	 */
	private static function wc_page( $key, $slug, $title, $content ) {
		$id = (int) wc_get_page_id( $key );
		if ( $id > 0 && get_post( $id ) ) {
			wp_update_post(
				array(
					'ID'           => $id,
					'post_title'   => $title,
					'post_name'    => $slug,
					'post_content' => $content,
					'post_status'  => 'publish',
				)
			);
		} else {
			$id = self::upsert_page( $slug, $title, $content );
		}
		update_option( 'woocommerce_' . $key . '_page_id', $id );
		return $id;
	}

	private static function pages() {
		$p = array();

		$p['shop']     = self::wc_page( 'shop', 'tienda', 'Tienda', '' );
		$p['cart']     = self::wc_page( 'cart', 'carrito', 'Carrito', '[woocommerce_cart]' );
		$p['checkout'] = self::wc_page( 'checkout', 'finalizar-compra', 'Finalizar compra', '[woocommerce_checkout]' );
		$p['account']  = self::wc_page( 'myaccount', 'mi-cuenta', 'Mi cuenta', '[woocommerce_my_account]' );

		$p['terms']    = self::upsert_page( 'terminos-y-condiciones', 'Términos y condiciones', self::text_terms() );
		$p['privacy']  = self::upsert_page( 'politica-de-privacidad', 'Política de privacidad', self::text_privacy() );
		$p['shipping'] = self::upsert_page( 'envios-y-devoluciones', 'Envíos y devoluciones', self::text_shipping() );
		$p['about']    = self::upsert_page( 'nosotros', 'Nosotros', self::text_about() );
		$p['contact']  = self::upsert_page( 'contacto', 'Contacto', '[sts_contacto]' );
		$p['home']     = self::upsert_page( 'inicio', 'Inicio', '' );

		update_option( 'woocommerce_terms_page_id', $p['terms'] );
		update_option( 'wp_page_for_privacy_policy', $p['privacy'] );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $p['home'] );
		update_option( 'page_for_posts', 0 );

		return $p;
	}

	private static function text_terms() {
		return '<p><em>Texto de ejemplo del proyecto universitario ST Store. No constituye un contrato real.</em></p>'
			. '<h2>1. Compras y precios</h2><p>Los precios se muestran en soles (PEN) e incluyen IGV. El precio y la disponibilidad se confirman al finalizar la compra: si un producto se agota mientras compras, el sistema te lo indicará antes de cobrar.</p>'
			. '<h2>2. Stock</h2><p>El inventario mostrado en la tienda se descuenta de forma definitiva cuando el pedido queda confirmado (pago aprobado o pedido pendiente de validación de pago).</p>'
			. '<h2>3. Comprobantes</h2><p>Puedes solicitar boleta (DNI) o factura (RUC y razón social) al momento de comprar. Los comprobantes de esta demostración no tienen validez tributaria.</p>'
			. '<h2>4. Cambios y devoluciones</h2><p>Consulta la página <a href="' . esc_url( sts_page_url( 'envios-y-devoluciones' ) ) . '">Envíos y devoluciones</a>.</p>';
	}

	private static function text_privacy() {
		return '<p><em>Texto de ejemplo del proyecto universitario ST Store.</em></p>'
			. '<h2>Datos que recopilamos</h2><p>Nombre, documento (DNI/RUC), correo, teléfono y dirección de entrega, únicamente para procesar tu pedido y emitir tu comprobante.</p>'
			. '<h2>Uso de los datos</h2><p>No vendemos ni compartimos tus datos con terceros ajenos al despacho del pedido. Puedes solicitar la eliminación de tu cuenta escribiendo a ' . esc_html( sts_contact( 'email' ) ) . '.</p>'
			. '<h2>Chatbot</h2><p>El asistente virtual no almacena el contenido de tus mensajes; solo registra de forma anónima el tipo de consulta para mejorar sus respuestas.</p>';
	}

	private static function text_shipping() {
		return '<h2>Envíos</h2><ul>'
			. '<li><strong>Lima y Callao:</strong> delivery en 24 a 48 horas por S/ 15.00. <strong>Gratis desde S/ 500.00.</strong> También puedes recoger tu pedido en tienda sin costo.</li>'
			. '<li><strong>Resto del Perú:</strong> courier en 3 a 5 días hábiles por S/ 35.00. <strong>Gratis desde S/ 2,000.00.</strong></li></ul>'
			. '<p>Cuando tu pedido sale de almacén recibes un correo con el número de seguimiento.</p>'
			. '<h2>Devoluciones y garantía</h2><p>Tienes 7 días calendario para solicitar el cambio de un producto sin uso y en su empaque original. Los equipos tienen garantía de fábrica de 12 meses (la cobertura exacta depende de cada marca).</p>'
			. '<p><em>Texto de ejemplo del proyecto universitario.</em></p>';
	}

	private static function text_about() {
		return '<p>ST Store es el proyecto del curso <strong>E-Business y Analítica Web</strong>: una tienda en línea de equipos y materiales de ingeniería eléctrica con inventario en tiempo real, compra completa en línea y atención por chatbot.</p>'
			. '<p>Cada integrante aportó 4 productos al catálogo, con su propio archivo y sus propios commits en GitHub.</p>'
			. '[sts_equipo]';
	}

	/* ------------------------------------------------------------------ */

	private static function taxes() {
		global $wpdb;
		$exists = $wpdb->get_var( "SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_name = 'IGV' LIMIT 1" ); // phpcs:ignore
		if ( ! $exists ) {
			WC_Tax::_insert_tax_rate(
				array(
					'tax_rate_country'  => 'PE',
					'tax_rate_state'    => '',
					'tax_rate'          => '18.0000',
					'tax_rate_name'     => 'IGV',
					'tax_rate_priority' => 1,
					'tax_rate_compound' => 0,
					'tax_rate_shipping' => 0,
					'tax_rate_order'    => 1,
					'tax_rate_class'    => '',
				)
			);
		}
	}

	/**
	 * @return array IDs de instancia de los métodos de envío creados.
	 */
	private static function shipping() {
		foreach ( WC_Shipping_Zones::get_zones() as $z ) {
			$zone = new WC_Shipping_Zone( $z['id'] );
			if ( in_array( $zone->get_zone_name(), array( 'Lima y Callao', 'Resto del Perú' ), true ) ) {
				$zone->delete();
			}
		}

		$ids = array();

		$lima = new WC_Shipping_Zone();
		$lima->set_zone_name( 'Lima y Callao' );
		$lima->set_zone_order( 0 );
		$lima->add_location( 'PE:LMA', 'state' );
		$lima->add_location( 'PE:CAL', 'state' );
		$lima->save();

		$ids['lima_flat'] = $lima->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $ids['lima_flat'] . '_settings',
			array(
				'title'      => 'Delivery en Lima y Callao (24 a 48 h)',
				'tax_status' => 'none',
				'cost'       => '15',
			)
		);
		$ids['lima_free'] = $lima->add_shipping_method( 'free_shipping' );
		update_option(
			'woocommerce_free_shipping_' . $ids['lima_free'] . '_settings',
			array(
				'title'            => 'Envío gratis (compras desde S/ 500)',
				'requires'         => 'min_amount',
				'min_amount'       => '500',
				'ignore_discounts' => 'no',
			)
		);
		$ids['pickup'] = $lima->add_shipping_method( 'local_pickup' );
		update_option(
			'woocommerce_local_pickup_' . $ids['pickup'] . '_settings',
			array(
				'title'      => 'Recojo en tienda (gratis)',
				'tax_status' => 'none',
				'cost'       => '0',
			)
		);

		$peru = new WC_Shipping_Zone();
		$peru->set_zone_name( 'Resto del Perú' );
		$peru->set_zone_order( 1 );
		$peru->add_location( 'PE', 'country' );
		$peru->save();

		$ids['peru_flat'] = $peru->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $ids['peru_flat'] . '_settings',
			array(
				'title'      => 'Courier a provincias (3 a 5 días)',
				'tax_status' => 'none',
				'cost'       => '35',
			)
		);
		$ids['peru_free'] = $peru->add_shipping_method( 'free_shipping' );
		update_option(
			'woocommerce_free_shipping_' . $ids['peru_free'] . '_settings',
			array(
				'title'            => 'Envío gratis (compras desde S/ 2,000)',
				'requires'         => 'min_amount',
				'min_amount'       => '2000',
				'ignore_discounts' => 'no',
			)
		);

		return $ids;
	}

	private static function payments( $ship ) {
		update_option(
			'woocommerce_bacs_settings',
			array(
				'enabled'      => 'yes',
				'title'        => 'Transferencia bancaria',
				'description'  => 'Transfiere a nuestra cuenta y envíanos la constancia. Despachamos cuando se confirma el pago.',
				'instructions' => 'Indica el número de pedido como referencia de la transferencia y envía la constancia por WhatsApp.',
			)
		);
		update_option(
			'woocommerce_bacs_accounts',
			array(
				array(
					'account_name'   => 'ST Store S.A.C. (cuenta de ejemplo)',
					'account_number' => '000-0000000-0-00',
					'sort_code'      => '',
					'bank_name'      => 'Banco de ejemplo',
					'iban'           => 'CCI 000-000-000000000000-00',
					'bic'            => '',
				),
			)
		);
		update_option(
			'woocommerce_cod_settings',
			array(
				'enabled'            => 'yes',
				'title'              => 'Pago contra entrega',
				'description'        => 'Paga en efectivo cuando recibas tu pedido (solo Lima y Callao).',
				'instructions'       => 'Ten el monto exacto a la mano; el courier no siempre tiene sencillo.',
				'enable_for_methods' => array( 'flat_rate:' . $ship['lima_flat'], 'local_pickup:' . $ship['pickup'] ),
				'enable_for_virtual' => 'no',
			)
		);
		update_option( 'woocommerce_cheque_settings', array( 'enabled' => 'no' ) );
		update_option( 'woocommerce_paypal_settings', array( 'enabled' => 'no' ) );
		update_option(
			'woocommerce_sts_card_sim_settings',
			array(
				'enabled'     => 'yes',
				'title'       => 'Tarjeta de crédito o débito (pasarela simulada)',
				'description' => 'Simulación para la demostración: no se procesan tarjetas reales.',
			)
		);
		update_option(
			'woocommerce_sts_yape_settings',
			array(
				'enabled'     => 'yes',
				'title'       => 'Yape / Plin',
				'description' => 'Paga desde tu celular y registra el número de operación.',
				'number'      => '999 999 999',
				'holder'      => 'ST Store S.A.C. (ejemplo)',
			)
		);
		update_option(
			'woocommerce_gateway_order',
			array(
				'sts_card_sim' => 0,
				'sts_yape'     => 1,
				'bacs'         => 2,
				'cod'          => 3,
			)
		);
	}

	private static function coupons() {
		if ( wc_get_coupon_id_by_code( 'BIENVENIDO10' ) ) {
			return;
		}
		$coupon = new WC_Coupon();
		$coupon->set_code( 'BIENVENIDO10' );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( 10 );
		$coupon->set_description( '10% de descuento en tu compra (cupón de bienvenida de la demo).' );
		$coupon->save();
	}

	private static function demo_customer() {
		if ( username_exists( self::DEMO_USER ) ) {
			return;
		}
		$id = wp_insert_user(
			array(
				'user_login'   => self::DEMO_USER,
				'user_pass'    => self::DEMO_PASSWORD,
				'user_email'   => self::DEMO_EMAIL,
				'first_name'   => 'Cliente',
				'last_name'    => 'Demo',
				'display_name' => 'Cliente Demo',
				'role'         => 'customer',
			)
		);
		if ( is_wp_error( $id ) ) {
			return;
		}
		$billing = array(
			'billing_first_name'   => 'Cliente',
			'billing_last_name'    => 'Demo',
			'billing_country'      => 'PE',
			'billing_state'        => 'LMA',
			'billing_city'         => 'Lima',
			'billing_address_1'    => 'Av. Ejemplo 456',
			'billing_phone'        => '999888777',
			'billing_email'        => self::DEMO_EMAIL,
		);
		foreach ( $billing as $k => $v ) {
			update_user_meta( $id, $k, $v );
		}
	}

	/* ------------------------------------------------------------------ */

	private static function menus( $pages ) {
		$main = self::fresh_menu( 'Menú principal' );
		wp_update_nav_menu_item( $main, 0, array( 'menu-item-title' => 'Inicio', 'menu-item-url' => home_url( '/' ), 'menu-item-status' => 'publish', 'menu-item-type' => 'custom' ) );
		foreach ( array( 'shop' => 'Tienda', 'about' => 'Nosotros', 'shipping' => 'Envíos', 'contact' => 'Contacto' ) as $key => $label ) {
			wp_update_nav_menu_item(
				$main,
				0,
				array(
					'menu-item-title'     => $label,
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $pages[ $key ],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}

		$help = self::fresh_menu( 'Menú de ayuda' );
		foreach ( array( 'shipping' => 'Envíos y devoluciones', 'terms' => 'Términos y condiciones', 'privacy' => 'Política de privacidad', 'account' => 'Mi cuenta', 'contact' => 'Contacto' ) as $key => $label ) {
			wp_update_nav_menu_item(
				$help,
				0,
				array(
					'menu-item-title'     => $label,
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $pages[ $key ],
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}

		set_theme_mod(
			'nav_menu_locations',
			array(
				'primary' => $main,
				'footer'  => $help,
			)
		);
	}

	private static function fresh_menu( $name ) {
		$menu = wp_get_nav_menu_object( $name );
		if ( $menu ) {
			foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
				wp_delete_post( $item->ID, true );
			}
			return (int) $menu->term_id;
		}
		return (int) wp_create_nav_menu( $name );
	}

	private static function permalinks() {
		global $wp_rewrite;
		// Estas funciones solo existen en el admin; sin ellas WordPress no escribe el .htaccess en una visita web.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules( true );
	}
}
