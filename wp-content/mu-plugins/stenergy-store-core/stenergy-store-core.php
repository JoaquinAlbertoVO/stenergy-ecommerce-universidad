<?php
/**
 * ST Store - Núcleo de la tienda.
 *
 * Se carga como mu-plugin: no hay que activarlo. Al abrir el sitio por primera vez
 * después de instalar WordPress activa WooCommerce y el chatbot, aplica el tema hijo
 * y carga el catálogo desde sample-data/productos-por-integrante/*.csv.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STS_CORE_VERSION', '1.0.0' );
define( 'STS_CORE_DIR', __DIR__ );
define( 'STS_CORE_URL', WPMU_PLUGIN_URL . '/stenergy-store-core' );
define( 'STS_DATA_DIR', trailingslashit( ABSPATH ) . 'sample-data/' );
define( 'STS_SETUP_VERSION', 4 );

require_once STS_CORE_DIR . '/includes/helpers.php';
require_once STS_CORE_DIR . '/includes/class-sts-mail-log.php';
require_once STS_CORE_DIR . '/includes/class-sts-admin.php';

STS_Mail_Log::init();
STS_Admin::init();

/**
 * Las clases que dependen de WooCommerce se cargan cuando WooCommerce ya está disponible.
 */
add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		require_once STS_CORE_DIR . '/includes/class-sts-seeder.php';
		require_once STS_CORE_DIR . '/includes/class-sts-setup.php';
		require_once STS_CORE_DIR . '/includes/class-sts-checkout.php';
		require_once STS_CORE_DIR . '/includes/class-sts-order-status.php';
		require_once STS_CORE_DIR . '/includes/class-sts-team.php';

		STS_Checkout::init();
		STS_Order_Status::init();
		STS_Team::init();

		add_filter(
			'woocommerce_payment_gateways',
			function ( $gateways ) {
				require_once STS_CORE_DIR . '/includes/class-sts-gateway-card-sim.php';
				require_once STS_CORE_DIR . '/includes/class-sts-gateway-yape.php';
				$gateways[] = 'STS_Gateway_Card_Sim';
				$gateways[] = 'STS_Gateway_Yape';
				return $gateways;
			}
		);
	},
	20
);

/**
 * Primera visita tras instalar WordPress: deja la demo lista sin pasos manuales.
 * Solo corre en peticiones web (no en WP-CLI, AJAX, REST ni cron).
 */
add_action( 'init', 'sts_maybe_bootstrap', 20 );

function sts_maybe_bootstrap() {
	// Para un servidor público: define( 'STS_DISABLE_BOOTSTRAP', true ); en wp-config.php desactiva la configuración automática.
	if ( defined( 'STS_DISABLE_BOOTSTRAP' ) && STS_DISABLE_BOOTSTRAP ) {
		return;
	}
	if ( (int) get_option( 'sts_setup_done', 0 ) >= STS_SETUP_VERSION ) {
		return;
	}
	if ( wp_installing() || ! is_blog_installed() ) {
		return;
	}
	if ( ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	// Solo en visitas reales de un navegador (GET). El instalador de WordPress hace peticiones internas
	// (User-Agent "WordPress/...") mientras instala y no hay que interferir con ellas.
	global $pagenow;
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : ''; // phpcs:ignore
	$agent  = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : ''; // phpcs:ignore
	if ( 'GET' !== $method || 0 === strpos( $agent, 'WordPress/' ) || in_array( $pagenow, array( 'wp-login.php', 'wp-cron.php', 'xmlrpc.php' ), true ) ) {
		return;
	}
	if ( get_transient( 'sts_setup_lock' ) ) {
		return;
	}

	$attempts = (int) get_option( 'sts_setup_attempts', 0 );
	if ( $attempts >= 4 ) {
		return; // Algo falló: no insistir. Ver ST Store > Estado en el panel.
	}
	update_option( 'sts_setup_attempts', $attempts + 1, false );
	set_transient( 'sts_setup_lock', 1, 180 );

	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	$reload = false;
	foreach ( array( 'woocommerce/woocommerce.php', 'stenergy-chatbot/stenergy-chatbot.php' ) as $plugin ) {
		if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin ) && ! is_plugin_active( $plugin ) ) {
			$result = activate_plugin( $plugin );
			if ( ! is_wp_error( $result ) ) {
				$reload = true;
			}
		}
	}

	if ( $reload ) {
		delete_transient( 'sts_setup_lock' );
		delete_transient( '_wc_activation_redirect' );
		sts_reload_request();
	}

	if ( ! class_exists( 'STS_Setup' ) ) {
		delete_transient( 'sts_setup_lock' );
		return;
	}

	STS_Setup::run();
	delete_transient( 'sts_setup_lock' );
	// El tema nuevo recién queda activo: se recarga para que esta visita ya use todo (functions.php, rutas, menús).
	// Se hace en wp_loaded (prioridad 99) para que WordPress termine antes de escribir los enlaces permanentes (.htaccess).
	add_action( 'wp_loaded', 'sts_reload_request', 99 );
}

/**
 * Redirige a la misma URL que se pidió (nueva petición con el estado ya guardado).
 */
function sts_reload_request() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore
	wp_redirect( ( is_ssl() ? 'https://' : 'http://' ) . $host . $uri ); // phpcs:ignore WordPress.Security.SafeRedirect
	exit;
}

/**
 * WP-CLI: wp stenergy setup | wp stenergy seed
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'stenergy',
		new class() {
			/**
			 * Configura la tienda completa (opciones, páginas, envíos, pagos, catálogo...).
			 */
			public function setup() {
				if ( ! class_exists( 'STS_Setup' ) ) {
					WP_CLI::error( 'WooCommerce no está activo.' );
				}
				$report = STS_Setup::run();
				WP_CLI::success( 'Tienda configurada. ' . wp_json_encode( $report ) );
			}

			/**
			 * Importa/actualiza solo el catálogo desde sample-data/productos-por-integrante/*.csv
			 */
			public function seed() {
				if ( ! class_exists( 'STS_Seeder' ) ) {
					WP_CLI::error( 'WooCommerce no está activo.' );
				}
				$report = STS_Seeder::import_all();
				WP_CLI::success( wp_json_encode( $report ) );
				foreach ( $report['warnings'] as $w ) {
					WP_CLI::warning( $w );
				}
			}
		}
	);
}
