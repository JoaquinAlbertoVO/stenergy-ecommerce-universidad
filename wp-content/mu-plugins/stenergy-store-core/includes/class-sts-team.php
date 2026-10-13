<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes de la tienda: equipo (con los productos aportados por cada integrante)
 * y contacto (datos + formulario). También muestra quién aportó cada ficha de producto.
 */
class STS_Team {

	public static function init() {
		add_shortcode( 'sts_equipo', array( __CLASS__, 'equipo' ) );
		add_shortcode( 'sts_contacto', array( __CLASS__, 'contacto' ) );
		add_action( 'admin_post_nopriv_sts_contact', array( __CLASS__, 'handle_contact' ) );
		add_action( 'admin_post_sts_contact', array( __CLASS__, 'handle_contact' ) );

		add_filter( 'manage_edit-product_columns', array( __CLASS__, 'admin_column' ), 20 );
		add_action( 'manage_product_posts_custom_column', array( __CLASS__, 'admin_column_value' ), 10, 2 );
		add_action( 'woocommerce_product_meta_end', array( __CLASS__, 'product_contributor' ) );
	}

	public static function equipo() {
		global $wpdb;
		$team   = STS_Seeder::team_map();
		$counts = $wpdb->get_results( "SELECT meta_value AS file, COUNT(*) AS n FROM {$wpdb->postmeta} WHERE meta_key = '" . STS_Seeder::META_SOURCE . "' GROUP BY meta_value", OBJECT_K ); // phpcs:ignore

		if ( ! $team ) {
			return '';
		}
		$out = '<div class="sts-team">';
		foreach ( $team as $file => $m ) {
			$n    = isset( $counts[ $file ] ) ? (int) $counts[ $file ]->n : 0;
			$name = isset( $m['nombre'] ) ? $m['nombre'] : $file;
			$init = mb_strtoupper( mb_substr( $name, 0, 1 ) );
			$out .= '<article class="sts-team__card"><span class="sts-team__avatar" aria-hidden="true">' . esc_html( $init ) . '</span>'
				. '<h3>' . esc_html( $name ) . '</h3>'
				. '<p>' . esc_html( isset( $m['rol'] ) ? $m['rol'] : '' ) . '</p>'
				. '<p class="sts-team__count"><strong>' . $n . '</strong> producto' . ( 1 === $n ? '' : 's' ) . ' en el catálogo</p></article>';
		}
		return $out . '</div>';
	}

	public static function contacto() {
		$c    = sts_contact();
		$sent = isset( $_GET['sts_sent'] ) ? sanitize_key( $_GET['sts_sent'] ) : ''; // phpcs:ignore

		ob_start();
		echo '<div class="sts-contact">';
		if ( '1' === $sent ) {
			echo '<p class="sts-notice sts-notice--ok">¡Gracias! Recibimos tu mensaje y te responderemos pronto.</p>';
		} elseif ( 'error' === $sent ) {
			echo '<p class="sts-notice sts-notice--err">No pudimos enviar tu mensaje. Revisa los datos e inténtalo otra vez.</p>';
		}
		echo '<div class="sts-contact__info"><h2>Escríbenos</h2><ul>';
		printf( '<li><strong>WhatsApp:</strong> <a href="%s" target="_blank" rel="noopener">%s</a></li>', esc_url( sts_whatsapp_url( 'Hola, quisiera información sobre un producto.' ) ), esc_html( $c['whatsapp_label'] ) );
		printf( '<li><strong>Correo:</strong> <a href="mailto:%1$s">%1$s</a></li>', esc_attr( $c['email'] ) );
		printf( '<li><strong>Teléfono:</strong> %s</li>', esc_html( $c['phone'] ) );
		printf( '<li><strong>Dirección:</strong> %s</li>', esc_html( $c['address'] ) );
		printf( '<li><strong>Horario:</strong> %s</li>', esc_html( $c['hours'] ) );
		echo '</ul></div>';

		echo '<form class="sts-contact__form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<h2>Envíanos un mensaje</h2>';
		wp_nonce_field( 'sts_contact' );
		echo '<input type="hidden" name="action" value="sts_contact">';
		echo '<p style="position:absolute;left:-9999px" aria-hidden="true"><label>No llenar<input name="sts_website" tabindex="-1" autocomplete="off"></label></p>';
		echo '<p><label>Nombre<input name="nombre" required maxlength="80"></label></p>';
		echo '<p><label>Correo<input type="email" name="correo" required maxlength="120"></label></p>';
		echo '<p><label>Asunto<input name="asunto" required maxlength="120"></label></p>';
		echo '<p><label>Mensaje<textarea name="mensaje" rows="5" required maxlength="1500"></textarea></label></p>';
		echo '<p><button type="submit" class="button">Enviar mensaje</button></p></form></div>';
		return ob_get_clean();
	}

	public static function handle_contact() {
		$back = wp_get_referer() ? remove_query_arg( 'sts_sent', wp_get_referer() ) : sts_page_url( 'contacto' );

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'sts_contact' ) || ! empty( $_POST['sts_website'] ) ) {
			wp_safe_redirect( add_query_arg( 'sts_sent', 'error', $back ) );
			exit;
		}
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
		$key = 'sts_contact_' . md5( $ip );
		$hits = (int) get_transient( $key );
		if ( $hits >= 5 ) {
			wp_safe_redirect( add_query_arg( 'sts_sent', 'error', $back ) );
			exit;
		}
		set_transient( $key, $hits + 1, HOUR_IN_SECONDS );

		$nombre  = sanitize_text_field( wp_unslash( $_POST['nombre'] ?? '' ) );
		$correo  = sanitize_email( wp_unslash( $_POST['correo'] ?? '' ) );
		$asunto  = sanitize_text_field( wp_unslash( $_POST['asunto'] ?? '' ) );
		$mensaje = sanitize_textarea_field( wp_unslash( $_POST['mensaje'] ?? '' ) );

		if ( ! $nombre || ! is_email( $correo ) || ! $asunto || ! $mensaje ) {
			wp_safe_redirect( add_query_arg( 'sts_sent', 'error', $back ) );
			exit;
		}
		wp_mail(
			sts_contact( 'email' ),
			'Contacto web: ' . $asunto,
			"Nombre: $nombre\nCorreo: $correo\n\n$mensaje",
			array( 'Reply-To: ' . $nombre . ' <' . $correo . '>' )
		);
		wp_safe_redirect( add_query_arg( 'sts_sent', '1', $back ) );
		exit;
	}

	public static function admin_column( $cols ) {
		$cols['sts_autor'] = 'Aportado por';
		return $cols;
	}

	public static function admin_column_value( $col, $post_id ) {
		if ( 'sts_autor' === $col ) {
			echo esc_html( get_post_meta( $post_id, STS_Seeder::META_CONTRIBUTOR, true ) ?: '—' );
		}
	}

	public static function product_contributor() {
		global $product;
		if ( ! $product ) {
			return;
		}
		$who = get_post_meta( $product->get_id(), STS_Seeder::META_CONTRIBUTOR, true );
		if ( $who ) {
			echo '<span class="sts-contributor">Ficha aportada por: ' . esc_html( $who ) . '</span>';
		}
	}
}
