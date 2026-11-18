<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Consentimiento de cookies (banner + Google Consent Mode v2).
 *
 * - Antes de que el visitante elija, Google Analytics 4 NO se carga y WooCommerce no crea
 *   las cookies sbjs_* del origen de pedidos (Order Attribution).
 * - La elección se guarda 180 días en la cookie técnica sts_cookie_consent ("v1.a1" acepta, "v1.a0" rechaza).
 * - El visitante puede cambiarla cuando quiera con cualquier enlace a #preferencias-cookies
 *   (el footer tiene uno) o con el shortcode [sts_preferencias_cookies].
 */
class STS_Consent {

	const COOKIE = 'sts_cookie_consent';
	const DAYS   = 180;

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'print_defaults' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		// Antes que los scripts del footer (wp_print_footer_scripts corre en la prioridad 20).
		add_action( 'wp_footer', array( __CLASS__, 'print_banner' ), 5 );
		add_filter( 'wc_order_attribution_allow_tracking', array( __CLASS__, 'analytics_allowed' ) );
		add_filter( 'wp_nav_menu_items', array( __CLASS__, 'footer_link' ), 10, 2 );
		add_shortcode( 'sts_tabla_cookies', array( __CLASS__, 'shortcode_table' ) );
		add_shortcode( 'sts_preferencias_cookies', array( __CLASS__, 'shortcode_button' ) );
	}

	/** ¿El visitante aceptó las cookies de analítica? (lectura en el servidor) */
	public static function analytics_allowed() {
		$value = isset( $_COOKIE[ self::COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) ) : '';
		return 'v1.a1' === $value;
	}

	/**
	 * Valores por defecto de Consent Mode v2: tienen que ir antes que cualquier gtag('config').
	 * Lee la cookie en el navegador para que una elección previa se aplique desde la primera línea.
	 */
	public static function print_defaults() {
		if ( is_admin() ) {
			return;
		}
		?>
<!-- Consentimiento de cookies (ST Store) -->
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
(function () {
	var m = document.cookie.match(/(?:^|;\s*)<?php echo esc_js( self::COOKIE ); ?>=([^;]*)/);
	var v = m ? decodeURIComponent(m[1]) : '';
	var analytics = v === 'v1.a1';
	gtag('consent', 'default', {
		ad_storage: 'denied',
		ad_user_data: 'denied',
		ad_personalization: 'denied',
		analytics_storage: analytics ? 'granted' : 'denied',
		functionality_storage: 'granted',
		security_storage: 'granted'
	});
	var waiting = [];
	window.stsConsent = {
		decided: v === 'v1.a1' || v === 'v1.a0',
		analytics: analytics,
		/* Ejecuta f() solo cuando haya consentimiento de analítica (ahora o cuando el visitante acepte). */
		whenAnalytics: function (f) { if (this.analytics) { f(); } else { waiting.push(f); } },
		_grant: function () { this.analytics = true; while (waiting.length) { waiting.shift()(); } }
	};
})();
</script>
		<?php
	}

	public static function enqueue() {
		wp_enqueue_style( 'sts-consent', STS_CORE_URL . '/assets/consent.css', array(), STS_CORE_VERSION . '.' . filemtime( STS_CORE_DIR . '/assets/consent.css' ) );
		wp_enqueue_script( 'sts-consent', STS_CORE_URL . '/assets/consent.js', array(), STS_CORE_VERSION . '.' . filemtime( STS_CORE_DIR . '/assets/consent.js' ), true );
		wp_localize_script(
			'sts-consent',
			'stsConsentCfg',
			array(
				'cookie' => self::COOKIE,
				'days'   => self::DAYS,
			)
		);
	}

	public static function print_banner() {
		$policy = sts_page_url( 'politica-de-cookies' );
		?>
<div class="sts-consent" id="sts-consent" role="dialog" aria-labelledby="sts-consent-title" aria-describedby="sts-consent-text" hidden>
	<div class="sts-consent__box">
		<p class="sts-consent__title" id="sts-consent-title">Usamos cookies</p>
		<p class="sts-consent__text" id="sts-consent-text">
			Usamos cookies necesarias para que funcionen el carrito y tu cuenta. Con tu permiso también usamos
			cookies de analítica (Google Analytics 4 y el origen de visitas de WooCommerce) para entender cómo se usa la tienda.
			<a href="<?php echo esc_url( $policy ); ?>">Política de cookies</a>
		</p>
		<div class="sts-consent__prefs" id="sts-consent-prefs" hidden>
			<label class="sts-consent__opt">
				<input type="checkbox" checked disabled>
				<span><strong>Necesarias</strong> · sesión, carrito y seguridad. Siempre activas.</span>
			</label>
			<label class="sts-consent__opt">
				<input type="checkbox" id="sts-consent-analytics">
				<span><strong>Analítica</strong> · Google Analytics 4 (_ga, _ga_*) y origen de visitas (sbjs_*).</span>
			</label>
		</div>
		<div class="sts-consent__actions">
			<button type="button" class="sts-consent__btn" data-consent="reject">Rechazar</button>
			<button type="button" class="sts-consent__btn sts-consent__btn--ghost" data-consent="settings" aria-controls="sts-consent-prefs" aria-expanded="false">Configurar</button>
			<button type="button" class="sts-consent__btn sts-consent__btn--ghost" data-consent="save" hidden>Guardar selección</button>
			<button type="button" class="sts-consent__btn" data-consent="accept">Aceptar</button>
		</div>
	</div>
</div>
		<?php
	}

	/** Agrega "Preferencias de cookies" al final del menú del footer. */
	public static function footer_link( $items, $args ) {
		if ( isset( $args->theme_location ) && 'footer' === $args->theme_location ) {
			$items .= '<li class="menu-item"><a href="#preferencias-cookies">Preferencias de cookies</a></li>';
		}
		return $items;
	}

	public static function shortcode_button() {
		return '<p><a class="button" href="#preferencias-cookies">Cambiar mis preferencias de cookies</a></p>';
	}

	/** Lista de cookies que usa la tienda (se mantiene aquí, junto al código que las crea). */
	public static function cookies() {
		return array(
			array( 'sts_cookie_consent', 'ST Store (propia)', 'Necesaria', 'Guarda si aceptaste o rechazaste la analítica.', self::DAYS . ' días' ),
			array( 'wordpress_logged_in_*, wordpress_sec_*', 'ST Store (propia)', 'Necesaria', 'Mantiene la sesión de tu cuenta.', 'Sesión o 14 días (Recordarme)' ),
			array( 'wp_woocommerce_session_*', 'ST Store (propia)', 'Necesaria', 'Guarda el carrito de compras.', '2 días' ),
			array( 'woocommerce_cart_hash, woocommerce_items_in_cart', 'ST Store (propia)', 'Necesaria', 'Detecta cambios en el carrito.', 'Sesión' ),
			array( '_ga, _ga_*', 'Google (tercero)', 'Analítica', 'Distingue visitantes y sesiones para Google Analytics 4.', '2 años' ),
			array( 'sbjs_*', 'ST Store (WooCommerce)', 'Analítica', 'Registra de dónde llegó la visita (buscador, red social, directo) para asociarlo al pedido.', 'Sesión a 6 meses' ),
		);
	}

	public static function shortcode_table() {
		$html  = '<table class="sts-cookie-table"><thead><tr><th>Cookie</th><th>Quién la pone</th><th>Tipo</th><th>Para qué sirve</th><th>Duración</th></tr></thead><tbody>';
		foreach ( self::cookies() as $row ) {
			$html .= '<tr>';
			foreach ( $row as $i => $cell ) {
				$html .= 0 === $i ? '<td><code>' . esc_html( $cell ) . '</code></td>' : '<td>' . esc_html( $cell ) . '</td>';
			}
			$html .= '</tr>';
		}
		return $html . '</tbody></table>';
	}
}
