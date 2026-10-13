<?php
/**
 * Portada de ST Store. Todo sale de la base de datos: categorías, stock y productos reales.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$stats = function_exists( 'sts_store_stats' ) ? sts_store_stats() : array( 'products' => 0, 'units' => 0, 'categories' => 0 );
$shop  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '#';
$cats  = taxonomy_exists( 'product_cat' ) ? get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
		'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
	)
) : array();
$low = function_exists( 'sts_low_stock_ids' ) ? sts_low_stock_ids( 4, 10 ) : array();
?>
<main id="content" class="sts-home">

	<section class="sts-hero">
		<div class="sts-container sts-hero__in">
			<div class="sts-hero__copy">
				<p class="sts-eyebrow">Ingeniería eléctrica · Venta con stock real</p>
				<h1>Equipos de medición, seguridad y energía, listos para despachar.</h1>
				<p>Mira cuántas unidades hay antes de comprar, paga en línea y recibe tu boleta o factura con RUC. Sin cotizaciones interminables.</p>
				<p class="sts-hero__cta">
					<a class="button sts-btn--accent" href="<?php echo esc_url( $shop ); ?>">Ver catálogo</a>
					<a class="button sts-btn--ghost" href="<?php echo esc_url( function_exists( 'sts_whatsapp_url' ) ? sts_whatsapp_url( 'Hola, quisiera cotizar una compra por volumen.' ) : '#' ); ?>" target="_blank" rel="noopener">Cotizar por volumen</a>
				</p>
			</div>
			<div class="sts-hero__card" aria-label="Inventario en vivo">
				<p class="sts-hero__live"><span class="sts-dot"></span> Inventario en vivo</p>
				<dl>
					<div><dt><?php echo (int) $stats['products']; ?></dt><dd>productos publicados</dd></div>
					<div><dt><?php echo esc_html( number_format_i18n( $stats['units'] ) ); ?></dt><dd>unidades disponibles</dd></div>
					<div><dt><?php echo (int) $stats['categories']; ?></dt><dd>categorías</dd></div>
				</dl>
			</div>
		</div>
	</section>

	<?php if ( $cats && ! is_wp_error( $cats ) ) : ?>
	<section class="sts-section">
		<div class="sts-container">
			<h2 class="sts-title">Compra por categoría</h2>
			<div class="sts-cats">
				<?php
				foreach ( $cats as $cat ) :
					list( $icon, $c1, $c2 ) = sts_cat_style( $cat->name );
					?>
					<a class="sts-cat" href="<?php echo esc_url( get_term_link( $cat ) ); ?>" style="--c1:<?php echo esc_attr( $c1 ); ?>;--c2:<?php echo esc_attr( $c2 ); ?>">
						<svg viewBox="0 0 24 24" width="44" height="44" aria-hidden="true"><?php echo sts_icon_svg( $icon ); // phpcs:ignore ?></svg>
						<span class="sts-cat__name"><?php echo esc_html( $cat->name ); ?></span>
						<span class="sts-cat__count"><?php echo (int) $cat->count; ?> producto<?php echo 1 === (int) $cat->count ? '' : 's'; ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<section class="sts-section sts-section--alt">
		<div class="sts-container">
			<h2 class="sts-title">Últimos ingresos</h2>
			<?php echo do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC"]' ); // phpcs:ignore ?>
			<p class="sts-more"><a href="<?php echo esc_url( $shop ); ?>">Ver todo el catálogo →</a></p>
		</div>
	</section>

	<?php if ( $low ) : ?>
	<section class="sts-section">
		<div class="sts-container">
			<h2 class="sts-title">Últimas unidades <small>antes de que se agoten</small></h2>
			<?php echo do_shortcode( '[products ids="' . esc_attr( implode( ',', $low ) ) . '" columns="4" orderby="post__in"]' ); // phpcs:ignore ?>
		</div>
	</section>
	<?php endif; ?>

	<section class="sts-section sts-section--alt">
		<div class="sts-container">
			<h2 class="sts-title">Cómo comprar en ST Store</h2>
			<ol class="sts-steps">
				<li><span>1</span><strong>Elige</strong> tu equipo y revisa cuántas unidades hay.</li>
				<li><span>2</span><strong>Agrega al carrito</strong> y ajusta cantidades o aplica un cupón.</li>
				<li><span>3</span><strong>Paga</strong> con tarjeta, Yape/Plin, transferencia o contra entrega.</li>
				<li><span>4</span><strong>Recibe</strong> tu pedido y tu boleta o factura con seguimiento.</li>
			</ol>
		</div>
	</section>

	<section class="sts-section">
		<div class="sts-container sts-perks">
			<div><h3>Stock verificado</h3><p>El inventario se descuenta al confirmar el pedido, así que lo que ves es lo que hay.</p></div>
			<div><h3>Factura con RUC</h3><p>Compras para tu empresa con razón social y RUC validado en el checkout.</p></div>
			<div><h3>Envíos a todo el Perú</h3><p>Delivery en Lima y Callao, courier a provincias y recojo en tienda.</p></div>
			<div><h3>Asistente virtual</h3><p>Pregunta por stock, precios, envíos o el estado de tu pedido en el chat.</p></div>
		</div>
	</section>

	<section class="sts-cta">
		<div class="sts-container sts-cta__in">
			<div>
				<h2>¿Compras para una obra o una planta?</h2>
				<p>Escríbenos y armamos una cotización por volumen con factura.</p>
			</div>
			<a class="button sts-btn--accent" href="<?php echo esc_url( sts_page_url( 'contacto' ) ); ?>">Contactar a ventas</a>
		</div>
	</section>
</main>
<?php
get_footer();
