<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bandeja de correos simulados.
 *
 * En un localhost no hay servidor de correo, así que los correos que enviaría
 * WooCommerce (confirmación de pedido, aviso al almacén, número de seguimiento...)
 * se guardan y se pueden revisar en ST Store > Correos simulados.
 * Para enviar correos reales: define( 'STS_REAL_MAIL', true ); en wp-config.php.
 */
class STS_Mail_Log {

	const CPT = 'sts_mail';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'pre_wp_mail', array( __CLASS__, 'capture' ), 10, 2 );
	}

	public static function enabled() {
		return apply_filters( 'sts_simulate_mail', ! ( defined( 'STS_REAL_MAIL' ) && STS_REAL_MAIL ) );
	}

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'label'    => 'Correos simulados',
				'public'   => false,
				'show_ui'  => false,
				'supports' => array( 'title', 'editor' ),
			)
		);
	}

	public static function capture( $null, $atts ) {
		if ( ! self::enabled() ) {
			return $null;
		}
		$to = isset( $atts['to'] ) ? ( is_array( $atts['to'] ) ? implode( ', ', $atts['to'] ) : (string) $atts['to'] ) : '';

		kses_remove_filters();
		wp_insert_post(
			array(
				'post_type'    => self::CPT,
				'post_status'  => 'publish',
				'post_title'   => isset( $atts['subject'] ) ? (string) $atts['subject'] : '(sin asunto)',
				'post_content' => isset( $atts['message'] ) ? (string) $atts['message'] : '',
				'meta_input'   => array( '_sts_to' => $to ),
			)
		);
		kses_init_filters();

		self::prune( 100 );
		return true;
	}

	private static function prune( $keep ) {
		$old = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 50,
				'offset'         => $keep,
				'orderby'        => 'ID',
				'order'          => 'DESC',
			)
		);
		foreach ( $old as $id ) {
			wp_delete_post( $id, true );
		}
	}

	public static function recent( $limit = 50 ) {
		return get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'ID',
				'order'          => 'DESC',
			)
		);
	}
}
