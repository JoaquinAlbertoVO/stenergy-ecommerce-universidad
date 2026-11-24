<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redes sociales y aviso de tienda de demostración.
 *
 * - Enlaces a TikTok, Instagram y Facebook (se configuran en ST Store > Ajustes; si un campo está vacío no se muestra).
 * - Botones para compartir cada producto (WhatsApp, Facebook, copiar enlace y el menú de compartir del celular).
 *   Los enlaces compartidos llevan parámetros UTM para que GA4 muestre de qué red llegó la visita.
 * - GA4 registra social_click (clic hacia una red) y share (producto compartido): ver assets/analytics.js.
 * - Franja superior que avisa que es una tienda de demostración (no se cobra ni se envía nada).
 */
class STS_Social {

	const NETWORKS = array(
		'tiktok'    => 'TikTok',
		'instagram' => 'Instagram',
		'facebook'  => 'Facebook',
	);

	public static function init() {
		add_action( 'wp_body_open', array( __CLASS__, 'demo_bar' ), 1 );
		add_action( 'woocommerce_share', array( __CLASS__, 'share_buttons' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_shortcode( 'sts_redes', array( __CLASS__, 'shortcode' ) );
	}

	/** Redes con URL configurada: array( 'tiktok' => array( 'label' => 'TikTok', 'url' => '...' ) ). */
	public static function networks() {
		$out = array();
		foreach ( self::NETWORKS as $key => $label ) {
			$url = trim( (string) sts_contact( $key ) );
			if ( $url && wp_http_validate_url( $url ) ) {
				$out[ $key ] = array(
					'label' => $label,
					'url'   => $url,
				);
			}
		}
		return $out;
	}

	/**
	 * Lista de enlaces a las redes. $where se envía a GA4 como link_location (encabezado, pie, contacto...).
	 */
	public static function links( $where = 'pie', $class = '' ) {
		$nets = self::networks();
		if ( ! $nets ) {
			return '';
		}
		$html = '<ul class="sts-social ' . esc_attr( $class ) . '" aria-label="Redes sociales de ST Store">';
		foreach ( $nets as $key => $net ) {
			$html .= sprintf(
				'<li><a class="sts-social__link sts-social__link--%1$s" href="%2$s" target="_blank" rel="noopener" data-sts-social="%1$s" data-sts-where="%3$s">%4$s<span>%5$s</span></a></li>',
				esc_attr( $key ),
				esc_url( $net['url'] ),
				esc_attr( $where ),
				self::icon( $key ),
				esc_html( $net['label'] )
			);
		}
		return $html . '</ul>';
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'donde' => 'contenido' ), $atts );
		return self::links( $atts['donde'], 'sts-social--big' );
	}

	/** Íconos simples (trazos propios, sin logos oficiales). */
	private static function icon( $key ) {
		$paths = array(
			'tiktok'    => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.5 2.5 2.5 4.5 5 4.5"/>',
			'instagram' => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r=".6" fill="currentColor"/>',
			'facebook'  => '<path d="M14.5 21v-8h3l.5-3.5h-3.5V7.5c0-1 .4-1.8 1.8-1.8H18V2.6c-.4 0-1.6-.1-2.9-.1-2.9 0-4.6 1.7-4.6 4.9v2.1H7.5V13h3v8"/>',
		);
		return '<svg aria-hidden="true" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $paths[ $key ] . '</svg>';
	}

	/** URL de la página con parámetros UTM para medir en GA4 de qué red llegan las visitas. */
	public static function utm( $url, $source, $campaign = 'compartir' ) {
		return add_query_arg(
			array(
				'utm_source'   => $source,
				'utm_medium'   => 'social',
				'utm_campaign' => $campaign,
			),
			$url
		);
	}

	public static function share_buttons() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$url   = get_permalink( $product->get_id() );
		$title = wp_strip_all_tags( $product->get_name() );
		$sku   = $product->get_sku() ? $product->get_sku() : (string) $product->get_id();
		$wa    = 'https://api.whatsapp.com/send?text=' . rawurlencode( $title . ' en ST Store: ' . self::utm( $url, 'whatsapp' ) );
		$fb    = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( self::utm( $url, 'facebook' ) );
		?>
<div class="sts-share" data-item-id="<?php echo esc_attr( $sku ); ?>" data-title="<?php echo esc_attr( $title ); ?>" data-url="<?php echo esc_url( self::utm( $url, 'compartir_movil' ) ); ?>" data-copy-url="<?php echo esc_url( self::utm( $url, 'enlace_copiado' ) ); ?>">
	<span class="sts-share__label">Compartir:</span>
	<button type="button" class="sts-share__btn" data-share="native" hidden>Compartir…</button>
	<a class="sts-share__btn" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener" data-share="whatsapp">WhatsApp</a>
	<a class="sts-share__btn" href="<?php echo esc_url( $fb ); ?>" target="_blank" rel="noopener" data-share="facebook">Facebook</a>
	<button type="button" class="sts-share__btn" data-share="copy">Copiar enlace</button>
	<span class="sts-share__ok" role="status" aria-live="polite"></span>
</div>
		<?php
		$links = self::links( 'producto', 'sts-social--inline' );
		if ( $links ) {
			echo '<div class="sts-share sts-share--follow"><span class="sts-share__label">Síguenos:</span>' . $links . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- armado con esc_* en links().
		}
	}

	public static function demo_bar() {
		?>
<div class="sts-demo-bar" role="note">
	<strong>Tienda de demostración</strong> · Proyecto universitario: los pedidos no se cobran ni se envían. Usa la tarjeta de prueba 4242 4242 4242 4242, nunca una tarjeta real.
</div>
		<?php
	}

	public static function enqueue() {
		wp_enqueue_style( 'sts-social', STS_CORE_URL . '/assets/social.css', array(), STS_CORE_VERSION . '.' . filemtime( STS_CORE_DIR . '/assets/social.css' ) );
		if ( function_exists( 'is_product' ) && is_product() ) {
			wp_enqueue_script( 'sts-share', STS_CORE_URL . '/assets/share.js', array(), STS_CORE_VERSION . '.' . filemtime( STS_CORE_DIR . '/assets/share.js' ), true );
		}
	}
}
