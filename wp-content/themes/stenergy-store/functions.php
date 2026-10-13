<?php
/**
 * ST Store - tema hijo de Hello Elementor.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STS_THEME_VERSION', '1.0.0' );

add_action(
	'after_setup_theme',
	function () {
		register_nav_menus(
			array(
				'primary' => 'Menú principal',
				'footer'  => 'Menú de ayuda (pie de página)',
			)
		);
		add_theme_support( 'title-tag' );
		add_theme_support( 'woocommerce' );
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	},
	20
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$dir = get_stylesheet_directory();
		$uri = get_stylesheet_directory_uri();
		wp_enqueue_style( 'sts-store', $uri . '/assets/store.css', array(), filemtime( $dir . '/assets/store.css' ) );
		wp_enqueue_script( 'sts-store', $uri . '/assets/store.js', array( 'jquery' ), filemtime( $dir . '/assets/store.js' ), true );
	},
	30
);

/* El tema padre trae un reset (botones rosados, enlaces #c36...) que choca con este diseño: se usa solo su estructura. */
add_action(
	'wp_enqueue_scripts',
	function () {
		foreach ( array( 'hello-elementor', 'hello-elementor-theme-style', 'hello-elementor-header-footer' ) as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
	},
	100
);

/* Oculta el banner promocional (en inglés) que el tema padre muestra en el escritorio del panel. */
add_action(
	'admin_head',
	function () {
		echo '<style>#ehe-admin-cb{display:none !important}</style>';
	}
);

/* Si WordPress pierde la asignación de menús al cambiar de tema, se usan los menús creados por la demo. */
add_filter(
	'theme_mod_nav_menu_locations',
	function ( $locations ) {
		$locations = is_array( $locations ) ? $locations : array();
		foreach ( array( 'primary' => 'Menú principal', 'footer' => 'Menú de ayuda' ) as $location => $name ) {
			if ( empty( $locations[ $location ] ) ) {
				$menu = wp_get_nav_menu_object( $name );
				if ( $menu ) {
					$locations[ $location ] = (int) $menu->term_id;
				}
			}
		}
		return $locations;
	}
);

/* ---------- Catálogo: 4 columnas, 12 por página ---------- */
add_filter( 'loop_shop_columns', function () { return 4; } );
add_filter( 'loop_shop_per_page', function () { return 12; } );

/* ---------- Carrito en el encabezado (se actualiza por AJAX) ---------- */
function sts_cart_link() {
	$count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
	$total = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_subtotal() : '';
	?>
	<a class="sts-cart-link" href="<?php echo esc_url( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '#' ); ?>" aria-label="Ver carrito">
		<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 4h2l2.4 11h10.2L20 7H6.2M9 20a1 1 0 1 0 0 .01M17 20a1 1 0 1 0 0 .01"/></svg>
		<span class="sts-cart-count"><?php echo (int) $count; ?></span>
		<span class="sts-cart-total"><?php echo wp_kses_post( $total ); ?></span>
	</a>
	<?php
}

add_filter(
	'woocommerce_add_to_cart_fragments',
	function ( $fragments ) {
		ob_start();
		sts_cart_link();
		$fragments['a.sts-cart-link'] = ob_get_clean();
		return $fragments;
	}
);

/* ---------- Disponibilidad de stock en cada tarjeta del catálogo ---------- */
add_action(
	'woocommerce_after_shop_loop_item_title',
	function () {
		global $product;
		if ( ! $product ) {
			return;
		}
		$a = $product->get_availability();
		if ( ! empty( $a['availability'] ) ) {
			echo '<span class="sts-stock ' . esc_attr( $a['class'] ) . '">' . esc_html( $a['availability'] ) . '</span>';
		}
	},
	11
);

/* ---------- Imagen referencial cuando el producto aún no tiene foto ---------- */
function sts_cat_style( $category ) {
	$key = strtolower( remove_accents( (string) $category ) );
	if ( false !== strpos( $key, 'medicion' ) ) {
		return array( 'gauge', '#0F5B8E', '#0B3C5D' );
	}
	if ( false !== strpos( $key, 'seguridad' ) || false !== strpos( $key, 'epp' ) || false !== strpos( $key, 'proteccion' ) ) {
		return array( 'shield', '#C2610C', '#7C2D12' );
	}
	if ( false !== strpos( $key, 'herramienta' ) ) {
		return array( 'gear', '#475569', '#1E293B' );
	}
	if ( false !== strpos( $key, 'iluminacion' ) || false !== strpos( $key, 'energia' ) ) {
		return array( 'bolt', '#B7791F', '#78350F' );
	}
	return array( 'box', '#0F5B8E', '#0B3C5D' );
}

function sts_icon_svg( $icon ) {
	$s = 'fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"';
	switch ( $icon ) {
		case 'gauge':
			return '<path ' . $s . ' d="M4 17a8 8 0 1 1 16 0"/><path ' . $s . ' d="M12 17l4-5"/><circle cx="12" cy="17" r="1.2" fill="#fff"/><path ' . $s . ' d="M6.3 10.3l1 .8M12 6.5v1.3M17.7 10.3l-1 .8"/>';
		case 'shield':
			return '<path ' . $s . ' d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.500-7-10V6z"/><path ' . $s . ' d="M9 12l2 2 4-4"/>';
		case 'gear':
			return '<circle ' . $s . ' cx="12" cy="12" r="3"/><circle ' . $s . ' cx="12" cy="12" r="7.500" stroke-dasharray="3 2.350"/>';
		case 'bolt':
			return '<path ' . $s . ' d="M13 3L5 13.500h6L10 21l9-11h-6z"/>';
		default:
			return '<path ' . $s . ' d="M4 8l8-4 8 4v8l-8 4-8-4zM4 8l8 4 8-4M12 12v8"/>';
	}
}

function sts_product_svg( $product ) {
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	$cat   = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
	list( $icon, $c1, $c2 ) = sts_cat_style( $cat );

	$words = preg_split( '/\s+/', trim( $product->get_name() ) );
	$lines = array();
	$cur   = '';
	foreach ( $words as $w ) {
		if ( '' !== $cur && mb_strlen( $cur . ' ' . $w ) > 19 ) {
			$lines[] = $cur;
			$cur     = $w;
		} else {
			$cur = '' === $cur ? $w : $cur . ' ' . $w;
		}
	}
	if ( '' !== $cur ) {
		$lines[] = $cur;
	}
	if ( count( $lines ) > 3 ) {
		$lines    = array_slice( $lines, 0, 3 );
		$lines[2] = mb_substr( $lines[2], 0, 17 ) . '…';
	}

	$text = '';
	foreach ( $lines as $i => $line ) {
		$text .= sprintf( '<text x="300" y="%d" text-anchor="middle" font-size="34" font-weight="700" fill="#fff">%s</text>', 395 + $i * 44, htmlspecialchars( $line, ENT_XML1 | ENT_QUOTES, 'UTF-8' ) );
	}

	$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" font-family="Segoe UI, Arial, sans-serif">'
		. '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . $c1 . '"/><stop offset="1" stop-color="' . $c2 . '"/></linearGradient></defs>'
		. '<rect width="600" height="600" fill="url(#g)"/>'
		. '<circle cx="300" cy="215" r="92" fill="#ffffff" fill-opacity=".14"/>'
		. '<g transform="translate(228 143) scale(6)">' . sts_icon_svg( $icon ) . '</g>'
		. $text
		. '<text x="300" y="568" text-anchor="middle" font-size="18" fill="#fff" fill-opacity=".7">Imagen referencial · ST Store</text>'
		. '</svg>';

	return 'data:image/svg+xml;base64,' . base64_encode( $svg );
}

add_filter(
	'woocommerce_product_get_image',
	function ( $image, $product ) {
		if ( $product->get_image_id() ) {
			return $image;
		}
		return sprintf(
			'<img src="%s" alt="%s" class="woocommerce-placeholder wp-post-image sts-placeholder" width="600" height="600" loading="lazy">',
			esc_attr( sts_product_svg( $product ) ),
			esc_attr( $product->get_name() )
		);
	},
	10,
	2
);

add_filter(
	'woocommerce_single_product_image_thumbnail_html',
	function ( $html, $thumbnail_id ) {
		if ( $thumbnail_id ) {
			return $html;
		}
		global $product;
		if ( ! $product ) {
			return $html;
		}
		return '<div class="woocommerce-product-gallery__image--placeholder"><img src="' . esc_attr( sts_product_svg( $product ) ) . '" alt="' . esc_attr( $product->get_name() ) . '" class="wp-post-image sts-placeholder" width="600" height="600"></div>';
	},
	10,
	2
);

/* ---------- Datos para la portada ---------- */
function sts_store_stats() {
	global $wpdb;
	$units = (int) $wpdb->get_var( "SELECT COALESCE(SUM(CAST(m.meta_value AS SIGNED)),0) FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = '_stock' AND p.post_type = 'product' AND p.post_status = 'publish'" ); // phpcs:ignore
	$prods = (int) ( wp_count_posts( 'product' )->publish ?? 0 );
	$cats  = (int) wp_count_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
	return array( 'products' => $prods, 'units' => $units, 'categories' => $cats );
}

/** IDs de productos en stock con pocas unidades (para la sección "Últimas unidades"). */
function sts_low_stock_ids( $limit = 4, $max_qty = 10 ) {
	global $wpdb;
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			 JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = '_stock'
			 JOIN {$wpdb->postmeta} ms ON ms.post_id = p.ID AND ms.meta_key = '_manage_stock' AND ms.meta_value = 'yes'
			 WHERE p.post_type = 'product' AND p.post_status = 'publish'
			   AND CAST(s.meta_value AS SIGNED) BETWEEN 1 AND %d
			 ORDER BY CAST(s.meta_value AS SIGNED) ASC LIMIT %d",
			$max_qty,
			$limit
		)
	);
	return array_map( 'intval', $ids );
}
