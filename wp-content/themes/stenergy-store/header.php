<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$contact = function_exists( 'sts_contact' ) ? sts_contact() : array();
$shop    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content">Saltar al contenido</a>

<div class="sts-topbar">
	<div class="sts-container sts-topbar__in">
		<span>Envío gratis en Lima desde S/ 500 · Boleta o factura con RUC</span>
		<span class="sts-topbar__links">
			<?php if ( ! empty( $contact['whatsapp'] ) ) : ?>
				<a href="<?php echo esc_url( sts_whatsapp_url( 'Hola, quisiera información sobre un producto.' ) ); ?>" target="_blank" rel="noopener">WhatsApp <?php echo esc_html( $contact['whatsapp_label'] ); ?></a>
			<?php endif; ?>
			<?php if ( ! empty( $contact['email'] ) ) : ?>
				<a href="mailto:<?php echo esc_attr( $contact['email'] ); ?>"><?php echo esc_html( $contact['email'] ); ?></a>
			<?php endif; ?>
		</span>
	</div>
</div>

<header class="sts-header">
	<div class="sts-container sts-header__in">
		<button class="sts-burger" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="sts-nav"><span></span><span></span><span></span></button>

		<a class="sts-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="ST Store - Inicio">
			<svg viewBox="0 0 64 64" width="42" height="42" aria-hidden="true"><rect width="64" height="64" rx="14" fill="#FFC421"/><g fill="#00247E" transform="translate(8 0) skewX(-8)"><path d="M10 15H31V23H19V27H31V50H10V42H22V35H10Z"/><path d="M33 15H55V23H48V50H40V23H33Z"/></g></svg>
			<span class="sts-logo__text"><strong>ST</strong> STORE<small>Ingeniería eléctrica</small></span>
		</a>

		<form class="sts-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="Buscar equipos, marcas o SKU…" aria-label="Buscar productos">
			<input type="hidden" name="post_type" value="product">
			<button type="submit" aria-label="Buscar"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M11 4a7 7 0 1 0 0 14 7 7 0 0 0 0-14zM20 20l-4-4"/></svg></button>
		</form>

		<div class="sts-header__actions">
			<a class="sts-account-link" href="<?php echo esc_url( $account ); ?>">
				<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0"/></svg>
				<span><?php echo is_user_logged_in() ? 'Mi cuenta' : 'Ingresar'; ?></span>
			</a>
			<?php sts_cart_link(); ?>
		</div>
	</div>

	<nav class="sts-nav" id="sts-nav" aria-label="Principal">
		<div class="sts-container sts-nav__in">
			<?php
			$cats = taxonomy_exists( 'product_cat' ) ? get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'parent'     => 0,
					'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
				)
			) : array();
			if ( $cats && ! is_wp_error( $cats ) ) :
				?>
				<div class="sts-catmenu">
					<button type="button" class="sts-catmenu__btn" aria-expanded="false">Catálogo ▾</button>
					<ul class="sts-catmenu__list">
						<li><a href="<?php echo esc_url( $shop ); ?>"><strong>Ver todo el catálogo</strong></a></li>
						<?php foreach ( $cats as $cat ) : ?>
							<li><a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?> <span>(<?php echo (int) $cat->count; ?>)</span></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'sts-menu',
					'fallback_cb'    => false,
					'depth'          => 1,
				)
			);
			?>
		</div>
	</nav>
</header>
