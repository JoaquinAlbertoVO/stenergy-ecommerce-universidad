<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Panel "ST Store": estado de la demo, ajustes de contacto, correos simulados
 * y widget de inventario en el escritorio.
 */
class STS_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_sts_resync', array( __CLASS__, 'handle_resync' ) );
		add_action( 'admin_post_sts_reapply', array( __CLASS__, 'handle_reapply' ) );
		add_action( 'admin_post_sts_save_contact', array( __CLASS__, 'handle_save_contact' ) );
		add_action( 'wp_dashboard_setup', array( __CLASS__, 'dashboard_widget' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	private static function cap() {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
	}

	public static function menu() {
		add_menu_page( 'ST Store', 'ST Store', 'manage_options', 'sts-store', array( __CLASS__, 'page' ), 'dashicons-store', 56 );
	}

	public static function notices() {
		if ( ! self::cap() ) {
			return;
		}
		$flash = get_transient( 'sts_flash_' . get_current_user_id() );
		if ( $flash ) {
			delete_transient( 'sts_flash_' . get_current_user_id() );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $flash ) . '</p></div>';
		}
		if ( (int) get_option( 'sts_setup_done', 0 ) < STS_SETUP_VERSION && 'sts-store' !== ( $_GET['page'] ?? '' ) ) { // phpcs:ignore
			echo '<div class="notice notice-warning"><p><strong>ST Store:</strong> la demo aún no está configurada. '
				. '<a href="' . esc_url( admin_url( 'admin.php?page=sts-store' ) ) . '">Abrir el estado de la tienda</a>.</p></div>';
		}
	}

	private static function flash( $message ) {
		set_transient( 'sts_flash_' . get_current_user_id(), $message, 60 );
	}

	private static function back( $tab = 'estado' ) {
		wp_safe_redirect( admin_url( 'admin.php?page=sts-store&tab=' . $tab ) );
		exit;
	}

	public static function handle_resync() {
		if ( ! self::cap() || ! check_admin_referer( 'sts_resync' ) ) {
			wp_die( 'No autorizado.' );
		}
		$report = class_exists( 'STS_Seeder' ) ? STS_Seeder::import_all() : null;
		if ( $report ) {
			update_option( 'sts_setup_report', $report, false );
			self::flash( sprintf( 'Catálogo sincronizado: %d productos creados, %d actualizados.', $report['created'], $report['updated'] ) );
		}
		self::back();
	}

	public static function handle_reapply() {
		if ( ! self::cap() || ! check_admin_referer( 'sts_reapply' ) ) {
			wp_die( 'No autorizado.' );
		}
		if ( class_exists( 'STS_Setup' ) ) {
			STS_Setup::run();
			self::flash( 'Configuración de la tienda aplicada de nuevo.' );
		}
		self::back();
	}

	public static function handle_save_contact() {
		if ( ! self::cap() || ! check_admin_referer( 'sts_save_contact' ) ) {
			wp_die( 'No autorizado.' );
		}
		$clean = array();
		foreach ( array_keys( sts_contact_defaults() ) as $key ) {
			$clean[ $key ] = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		}
		update_option( 'sts_contact', $clean );
		self::flash( 'Datos de contacto guardados.' );
		self::back( 'ajustes' );
	}

	public static function page() {
		if ( ! self::cap() ) {
			return;
		}
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'estado'; // phpcs:ignore
		$tabs = array(
			'estado'  => 'Estado',
			'ajustes' => 'Ajustes de contacto',
			'correos' => 'Correos simulados',
		);
		echo '<div class="wrap"><h1>ST Store</h1><nav class="nav-tab-wrapper">';
		foreach ( $tabs as $slug => $label ) {
			printf(
				'<a class="nav-tab %s" href="%s">%s</a>',
				$tab === $slug ? 'nav-tab-active' : '',
				esc_url( admin_url( 'admin.php?page=sts-store&tab=' . $slug ) ),
				esc_html( $label )
			);
		}
		echo '</nav>';
		if ( 'ajustes' === $tab ) {
			self::tab_ajustes();
		} elseif ( 'correos' === $tab ) {
			self::tab_correos();
		} else {
			self::tab_estado();
		}
		echo '</div>';
	}

	private static function tab_estado() {
		$done   = (int) get_option( 'sts_setup_done', 0 );
		$report = get_option( 'sts_setup_report', array() );
		$counts = function_exists( 'wp_count_posts' ) ? wp_count_posts( 'product' ) : null;
		echo '<h2>Estado de la demo</h2><table class="widefat striped" style="max-width:720px"><tbody>';
		printf( '<tr><td>Configuración inicial</td><td>%s</td></tr>', $done >= STS_SETUP_VERSION ? '<strong style="color:#1e7e34">Completada</strong>' : '<strong style="color:#b45309">Pendiente</strong> (se aplica sola al abrir el sitio; ver los botones de abajo)' ); // phpcs:ignore
		printf( '<tr><td>Productos publicados</td><td>%d</td></tr>', $counts ? (int) $counts->publish : 0 );
		if ( $report ) {
			printf( '<tr><td>Última sincronización del catálogo</td><td>%d creados · %d actualizados · %d archivos CSV leídos</td></tr>', (int) $report['created'], (int) $report['updated'], (int) $report['files'] );
		}
		echo '</tbody></table>';
		if ( ! empty( $report['warnings'] ) ) {
			echo '<h3>Avisos al leer los CSV</h3><ul style="list-style:disc;margin-left:20px">';
			foreach ( $report['warnings'] as $w ) {
				echo '<li>' . esc_html( $w ) . '</li>';
			}
			echo '</ul>';
		}
		echo '<p style="margin-top:20px">El catálogo sale de <code>sample-data/productos-por-integrante/*.csv</code>. Cada integrante sube su archivo con su propio commit; '
			. 'después de hacer <code>git pull</code> usa este botón para reflejarlo en la tienda (no duplica: actualiza por SKU).</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block;margin-right:10px">';
		wp_nonce_field( 'sts_resync' );
		echo '<input type="hidden" name="action" value="sts_resync"><button class="button button-primary">Re-sincronizar catálogo desde los CSV</button></form>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline-block">';
		wp_nonce_field( 'sts_reapply' );
		echo '<input type="hidden" name="action" value="sts_reapply"><button class="button">Re-aplicar configuración completa</button></form>';
	}

	private static function tab_ajustes() {
		$data = sts_contact();
		$labels = array(
			'whatsapp'       => 'WhatsApp (solo dígitos, con código de país)',
			'whatsapp_label' => 'WhatsApp (como se muestra)',
			'email'          => 'Correo de ventas',
			'phone'          => 'Teléfono',
			'address'        => 'Dirección',
			'hours'          => 'Horario de atención',
		);
		echo '<h2>Datos de contacto</h2><p>Se usan en el pie de página, en la página de contacto y en las respuestas del chatbot.</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><table class="form-table">';
		foreach ( $labels as $key => $label ) {
			printf(
				'<tr><th><label for="%1$s">%2$s</label></th><td><input class="regular-text" id="%1$s" name="%1$s" value="%3$s"></td></tr>',
				esc_attr( $key ),
				esc_html( $label ),
				esc_attr( $data[ $key ] )
			);
		}
		echo '</table>';
		wp_nonce_field( 'sts_save_contact' );
		echo '<input type="hidden" name="action" value="sts_save_contact">';
		submit_button( 'Guardar' );
		echo '</form>';
	}

	private static function tab_correos() {
		echo '<h2>Correos simulados</h2><p>En localhost no se envían correos reales: los que generaría la tienda (confirmación de pedido, aviso al almacén, número de seguimiento) quedan aquí.</p>';
		$view = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0; // phpcs:ignore
		if ( $view ) {
			$mail = get_post( $view );
			if ( $mail && STS_Mail_Log::CPT === $mail->post_type ) {
				printf( '<p><a href="%s">&larr; Volver</a></p><h3>%s</h3><p>Para: %s</p>', esc_url( admin_url( 'admin.php?page=sts-store&tab=correos' ) ), esc_html( $mail->post_title ), esc_html( get_post_meta( $mail->ID, '_sts_to', true ) ) );
				printf( '<iframe sandbox="" style="width:100%%;max-width:800px;height:620px;background:#fff;border:1px solid #ccd0d4" srcdoc="%s"></iframe>', esc_attr( $mail->post_content ) );
				return;
			}
		}
		$mails = STS_Mail_Log::recent( 50 );
		if ( ! $mails ) {
			echo '<p>Todavía no hay correos. Haz una compra de prueba y aparecerán aquí.</p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>Fecha</th><th>Para</th><th>Asunto</th><th></th></tr></thead><tbody>';
		foreach ( $mails as $mail ) {
			printf(
				'<tr><td>%s</td><td>%s</td><td>%s</td><td><a href="%s">Ver</a></td></tr>',
				esc_html( get_date_from_gmt( $mail->post_date_gmt, 'd/m/Y H:i' ) ),
				esc_html( get_post_meta( $mail->ID, '_sts_to', true ) ),
				esc_html( $mail->post_title ),
				esc_url( admin_url( 'admin.php?page=sts-store&tab=correos&view=' . $mail->ID ) )
			);
		}
		echo '</tbody></table>';
	}

	public static function dashboard_widget() {
		if ( ! self::cap() || ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		wp_add_dashboard_widget( 'sts_inventory', 'ST Store · Inventario y pedidos', array( __CLASS__, 'render_widget' ) );
	}

	public static function render_widget() {
		global $wpdb;
		$low  = (int) get_option( 'woocommerce_notify_low_stock_amount', 5 );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, CAST(m.meta_value AS SIGNED) AS qty
				 FROM {$wpdb->posts} p
				 JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_stock'
				 JOIN {$wpdb->postmeta} mm ON mm.post_id = p.ID AND mm.meta_key = '_manage_stock' AND mm.meta_value = 'yes'
				 WHERE p.post_type = 'product' AND p.post_status = 'publish' AND CAST(m.meta_value AS SIGNED) <= %d
				 ORDER BY qty ASC LIMIT 8",
				$low
			)
		);
		echo '<p><strong>Productos agotados o con stock bajo (≤ ' . (int) $low . ')</strong></p>';
		if ( ! $rows ) {
			echo '<p>Todo el inventario está por encima del mínimo.</p>';
		} else {
			echo '<ul style="margin:0 0 12px">';
			foreach ( $rows as $r ) {
				printf(
					'<li><a href="%s">%s</a> — <strong style="color:%s">%d u.</strong></li>',
					esc_url( get_edit_post_link( $r->ID ) ),
					esc_html( $r->post_title ),
					$r->qty <= 0 ? '#b91c1c' : '#b45309',
					(int) $r->qty
				);
			}
			echo '</ul>';
		}
		$statuses = wc_get_order_statuses();
		echo '<p><strong>Pedidos por estado</strong></p><ul style="margin:0">';
		foreach ( $statuses as $key => $label ) {
			$n = wc_orders_count( str_replace( 'wc-', '', $key ) );
			if ( $n ) {
				printf( '<li>%s: <strong>%d</strong></li>', esc_html( $label ), (int) $n );
			}
		}
		echo '</ul>';
		printf( '<p><a class="button" href="%s">Ver pedidos</a></p>', esc_url( admin_url( 'admin.php?page=wc-orders' ) ) );
	}
}
