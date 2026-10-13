<?php
/**
 * Plugin Name: ST Store - Asistente virtual (chatbot)
 * Description: Chatbot propio de la tienda. Responde con datos reales de WooCommerce (stock, precios, envíos, medios de pago, estado del pedido). No usa servicios externos ni claves de API.
 * Version:     1.0.0
 * Author:      Equipo ST Store
 * Text Domain: stenergy-chatbot
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STS_BOT_VERSION', '1.0.0' );
define( 'STS_BOT_DIR', plugin_dir_path( __FILE__ ) );
define( 'STS_BOT_URL', plugin_dir_url( __FILE__ ) );

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		require_once STS_BOT_DIR . 'includes/class-chatbot-engine.php';
		require_once STS_BOT_DIR . 'includes/class-chatbot-plugin.php';
		STS_Chatbot_Plugin::init();
	},
	30
);
