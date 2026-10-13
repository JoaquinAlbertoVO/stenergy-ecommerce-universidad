<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$c = function_exists( 'sts_contact' ) ? sts_contact() : array();
?>
<footer class="sts-footer">
	<div class="sts-container sts-footer__grid">
		<div>
			<p class="sts-footer__brand"><strong>ST</strong> STORE</p>
			<p>Tienda en línea de equipos y materiales de ingeniería eléctrica, con inventario en tiempo real y compra completa desde la web.</p>
			<p class="sts-footer__small">Proyecto del curso E-Business y Analítica Web · Datos de demostración.</p>
		</div>
		<div>
			<h3>Ayuda</h3>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'sts-footer__menu',
					'fallback_cb'    => false,
					'depth'          => 1,
				)
			);
			?>
		</div>
		<div>
			<h3>Contacto</h3>
			<ul class="sts-footer__menu">
				<?php if ( ! empty( $c['whatsapp'] ) ) : ?>
					<li>WhatsApp: <a href="<?php echo esc_url( sts_whatsapp_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $c['whatsapp_label'] ); ?></a></li>
				<?php endif; ?>
				<li>Correo: <a href="mailto:<?php echo esc_attr( $c['email'] ?? '' ); ?>"><?php echo esc_html( $c['email'] ?? '' ); ?></a></li>
				<li>Tel.: <?php echo esc_html( $c['phone'] ?? '' ); ?></li>
				<li><?php echo esc_html( $c['hours'] ?? '' ); ?></li>
			</ul>
		</div>
		<div>
			<h3>Medios de pago</h3>
			<p class="sts-chips"><span>Tarjeta</span><span>Yape</span><span>Plin</span><span>Transferencia</span><span>Contra entrega</span></p>
			<p class="sts-footer__small">Precios en soles (S/) con IGV incluido.</p>
		</div>
	</div>
	<div class="sts-footer__bottom">
		<div class="sts-container">© <?php echo esc_html( gmdate( 'Y' ) ); ?> ST Store — Proyecto universitario. Imágenes y datos de ejemplo.</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
