<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Google Analytics 4 con medición de comercio electrónico.
 *
 * Se activa al guardar un ID de medición (G-XXXXXXXXXX) en ST Store > Google Analytics 4
 * o con define( 'STS_GA4_ID', 'G-XXXXXXXXXX' ); en wp-config.php.
 *
 * Eventos que envía (nombres y parámetros recomendados por GA4):
 *   view_item_list, select_item, view_item, add_to_cart, remove_from_cart,
 *   view_cart, begin_checkout, add_shipping_info, add_payment_info, purchase
 * y dos eventos propios: chatbot_open y whatsapp_click.
 */
class STS_Analytics {

	const OPTION = 'sts_ga4';
	const QUEUE  = 'sts_ga4_queue';
	const SENT   = '_sts_ga4_purchase_sent';

	/** Propiedad GA4 del equipo: se usa mientras nadie haya guardado otro ID en el panel (dejarlo vacío ahí desactiva la medición). */
	const DEFAULT_ID = 'G-KRY8R9ENKH';

	/** Productos mostrados en los listados de esta página (para view_item_list). */
	private static $list_items = array();

	/** Eventos que se emiten al cargar esta página. */
	private static $events = array();

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'print_gtag' ), 2 );
		add_action( 'woocommerce_after_shop_loop_item', array( __CLASS__, 'collect_list_item' ), 99 );
		add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'queue_add_to_cart' ), 10, 4 );
		add_action( 'woocommerce_thankyou', array( __CLASS__, 'purchase' ), 5 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_footer', array( __CLASS__, 'page_data' ), 5 );
	}

	/* ---------------------------------------------------------------- Ajustes */

	public static function defaults() {
		return array(
			'measurement_id' => '',
			'debug'          => 0,
			'exclude_admins' => 1,
		);
	}

	public static function settings() {
		$saved = get_option( self::OPTION, null );
		$s     = wp_parse_args( is_array( $saved ) ? $saved : array( 'measurement_id' => self::DEFAULT_ID ), self::defaults() );
		if ( defined( 'STS_GA4_ID' ) && STS_GA4_ID ) {
			$s['measurement_id'] = STS_GA4_ID;
		}
		$s['measurement_id'] = strtoupper( trim( (string) $s['measurement_id'] ) );
		return $s;
	}

	public static function valid_id( $id ) {
		return (bool) preg_match( '/^G-[A-Z0-9]{4,20}$/', (string) $id );
	}

	public static function measurement_id() {
		$id = self::settings()['measurement_id'];
		return self::valid_id( $id ) ? $id : '';
	}

	/** No se mide a quien administra la tienda (para no ensuciar los datos), salvo que se desactive la opción. */
	private static function excluded_user() {
		return self::settings()['exclude_admins'] && ( current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ) );
	}

	private static function active() {
		return self::measurement_id() && ! is_admin() && ! self::excluded_user();
	}

	/* ---------------------------------------------------------------- Datos de productos */

	/**
	 * Producto en el formato "item" de GA4.
	 *
	 * @param WC_Product|int $product  Producto o ID.
	 * @param int            $quantity Cantidad.
	 * @param float|null     $price    Precio unitario (si no se pasa, el que ve el cliente en la tienda).
	 */
	public static function item( $product, $quantity = 1, $price = null ) {
		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( $product );
		}
		if ( ! $product ) {
			return null;
		}
		$parent_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		$item      = array(
			'item_id'   => $product->get_sku() ? $product->get_sku() : (string) $product->get_id(),
			'item_name' => wp_strip_all_tags( $product->get_name() ),
			'price'     => round( (float) ( null === $price ? wc_get_price_to_display( $product ) : $price ), 2 ),
			'quantity'  => max( 1, (int) $quantity ),
		);
		$cats = wc_get_product_terms( $parent_id, 'product_cat', array( 'fields' => 'names' ) );
		foreach ( array_slice( (array) $cats, 0, 5 ) as $i => $name ) {
			$item[ 0 === $i ? 'item_category' : 'item_category' . ( $i + 1 ) ] = wp_strip_all_tags( $name );
		}
		if ( $product->is_type( 'variation' ) ) {
			$item['item_variant'] = wp_strip_all_tags( wc_get_formatted_variation( $product, true, false ) );
		}
		return $item;
	}

	private static function sum( $items ) {
		$total = 0;
		foreach ( $items as $item ) {
			$total += $item['price'] * $item['quantity'];
		}
		return round( $total, 2 );
	}

	private static function event( $name, $params ) {
		self::$events[] = array( $name, $params );
	}

	private static function currency() {
		return get_woocommerce_currency();
	}

	/* ---------------------------------------------------------------- Hooks del servidor */

	public static function collect_list_item() {
		global $product;
		if ( ! self::active() || ! $product instanceof WC_Product || isset( self::$list_items[ $product->get_id() ] ) ) {
			return;
		}
		$item = self::item( $product );
		if ( $item ) {
			$item['index']                         = count( self::$list_items );
			self::$list_items[ $product->get_id() ] = $item;
		}
	}

	/**
	 * Agregar al carrito desde la ficha del producto (formulario normal, la página se recarga):
	 * el evento se guarda en la sesión y se envía en la siguiente página que se cargue.
	 * Los botones AJAX del catálogo se miden en el navegador (analytics.js).
	 */
	public static function queue_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id ) {
		if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		if ( ! self::measurement_id() || self::excluded_user() || ! WC()->session ) {
			return;
		}
		$item = self::item( $variation_id ? $variation_id : $product_id, $quantity );
		if ( ! $item ) {
			return;
		}
		$queue   = (array) WC()->session->get( self::QUEUE, array() );
		$queue[] = array(
			'add_to_cart',
			array(
				'currency' => self::currency(),
				'value'    => self::sum( array( $item ) ),
				'items'    => array( $item ),
			),
		);
		WC()->session->set( self::QUEUE, array_slice( $queue, -10 ) );
	}

	public static function purchase( $order_id ) {
		if ( ! self::active() ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order || $order->has_status( 'failed' ) || $order->get_meta( self::SENT ) ) {
			return;
		}
		$incl  = 'incl' === get_option( 'woocommerce_tax_display_cart' );
		$items = array();
		foreach ( $order->get_items() as $line ) {
			$item = self::item( $line->get_product(), $line->get_quantity(), $order->get_item_total( $line, $incl ) );
			if ( $item ) {
				$item['index'] = count( $items );
				$items[]       = $item;
			}
		}
		$params = array(
			'transaction_id' => (string) $order->get_order_number(),
			'currency'       => $order->get_currency(),
			'value'          => round( (float) $order->get_total(), 2 ),
			'tax'            => round( (float) $order->get_total_tax(), 2 ),
			'shipping'       => round( (float) $order->get_shipping_total(), 2 ),
			'items'          => $items,
		);
		$coupons = $order->get_coupon_codes();
		if ( $coupons ) {
			$params['coupon'] = implode( ',', $coupons );
		}
		if ( $order->get_payment_method_title() ) {
			$params['payment_type'] = $order->get_payment_method_title();
		}
		self::event( 'purchase', $params );

		// Recargar la página de "pedido recibido" no debe contar la venta dos veces.
		$order->update_meta_data( self::SENT, current_time( 'mysql' ) );
		$order->save();
	}

	/* ---------------------------------------------------------------- Salida en la página */

	public static function print_gtag() {
		if ( ! self::active() ) {
			return;
		}
		$id     = self::measurement_id();
		$config = array( 'currency' => self::currency() );
		if ( self::settings()['debug'] ) {
			$config['debug_mode'] = true;
		}
		?>
<!-- Google Analytics 4 (ST Store) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $id ); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', <?php echo wp_json_encode( $id ); ?>, <?php echo wp_json_encode( $config ); ?>);
</script>
		<?php
	}

	public static function enqueue() {
		if ( ! self::active() ) {
			return;
		}
		$file = STS_CORE_DIR . '/assets/analytics.js';
		wp_enqueue_script( 'sts-analytics', STS_CORE_URL . '/assets/analytics.js', array( 'jquery' ), STS_CORE_VERSION . '.' . filemtime( $file ), true );
	}

	/**
	 * Eventos de la página y catálogo de productos que necesita analytics.js.
	 * Corre en wp_footer antes de imprimir los scripts, cuando ya se renderizó el contenido.
	 */
	public static function page_data() {
		if ( ! self::active() || ! wp_script_is( 'sts-analytics', 'enqueued' ) ) {
			return;
		}

		// Eventos pendientes de la página anterior (agregar al carrito desde la ficha).
		// Las peticiones AJAX que pintan HTML (actualizar el carrito) no los consumen.
		$xhr = isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 'xmlhttprequest' === strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) );
		if ( ! $xhr && WC()->session ) {
			foreach ( (array) WC()->session->get( self::QUEUE, array() ) as $queued ) {
				self::$events[] = $queued;
			}
			WC()->session->set( self::QUEUE, array() );
		}

		$currency = self::currency();
		$cart     = array();

		if ( is_product() ) {
			$product = wc_get_product( get_queried_object_id() );
			$item    = $product ? self::item( $product ) : null;
			if ( $item ) {
				self::event(
					'view_item',
					array(
						'currency' => $currency,
						'value'    => $item['price'],
						'items'    => array( $item ),
					)
				);
			}
		}

		if ( ( is_cart() || is_checkout() ) && WC()->cart && ! is_wc_endpoint_url( 'order-received' ) ) {
			foreach ( WC()->cart->get_cart() as $key => $line ) {
				$item = self::item( $line['data'], $line['quantity'] );
				if ( $item ) {
					$cart[ $key ] = $item;
				}
			}
			if ( $cart ) {
				$items = array_values( $cart );
				$event = array(
					'currency' => $currency,
					'value'    => self::sum( $items ),
					'items'    => $items,
				);
				$coupons = WC()->cart->get_applied_coupons();
				if ( $coupons ) {
					$event['coupon'] = implode( ',', $coupons );
				}
				self::event( is_cart() ? 'view_cart' : 'begin_checkout', $event );
			}
		}

		if ( self::$list_items ) {
			self::event(
				'view_item_list',
				array(
					'item_list_id'   => self::list_id(),
					'item_list_name' => self::list_name(),
					'items'          => array_values( self::$list_items ),
				)
			);
		}

		$data = array(
			'currency' => $currency,
			'list'     => array(
				'id'   => self::list_id(),
				'name' => self::list_name(),
			),
			'products' => (object) self::$list_items,
			'cart'     => (object) $cart,
			'events'   => self::$events,
		);
		wp_add_inline_script( 'sts-analytics', 'window.stsGa4 = ' . wp_json_encode( $data ) . ';', 'before' );
	}

	private static function list_id() {
		if ( is_front_page() ) {
			return 'inicio';
		}
		if ( is_search() ) {
			return 'busqueda';
		}
		if ( is_product_category() ) {
			return 'categoria_' . get_queried_object()->slug;
		}
		if ( is_product() ) {
			return 'relacionados';
		}
		if ( is_cart() ) {
			return 'carrito_sugeridos';
		}
		return 'tienda';
	}

	private static function list_name() {
		if ( is_front_page() ) {
			return 'Página de inicio';
		}
		if ( is_search() ) {
			return 'Resultados de búsqueda';
		}
		if ( is_product_category() ) {
			return 'Categoría: ' . wp_strip_all_tags( single_term_title( '', false ) );
		}
		if ( is_product() ) {
			return 'Productos relacionados';
		}
		if ( is_cart() ) {
			return 'Sugeridos en el carrito';
		}
		return 'Tienda';
	}
}
