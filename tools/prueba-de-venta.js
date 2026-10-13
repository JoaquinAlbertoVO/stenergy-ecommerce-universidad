#!/usr/bin/env node
/**
 * Prueba de venta completa con capturas paso a paso: se ve el stock ANTES, la compra y el stock DESPUÉS.
 *
 *   npm i --no-save playwright-core
 *   node tools/prueba-de-venta.js http://localhost/stenergy-store
 * Con panel de administración (pedido, nota de stock y listado de productos):
 *   STS_ADMIN_USER=usuario STS_ADMIN_PASS=clave node tools/prueba-de-venta.js http://localhost/stenergy-store
 *
 * Opcionales: STS_PRODUCTO (texto de búsqueda, por defecto "guantes") y STS_CANTIDAD (por defecto 3).
 * Escribe las capturas en docs/capturas/prueba-de-venta/ (y resumen.json si se define STS_GUARDAR_RESUMEN).
 */
const path = require('path');
const fs = require('fs');
const { chromium } = require('playwright-core');

const BASE = (process.argv[2] || 'http://localhost/stenergy-store').replace(/\/$/, '');
const OUT = process.argv[3] || path.join(__dirname, '..', 'docs', 'capturas', 'prueba-de-venta');
const BUSQUEDA = process.env.STS_PRODUCTO || 'guantes';
const CANTIDAD = parseInt(process.env.STS_CANTIDAD || '3', 10);
const ADMIN_USER = process.env.STS_ADMIN_USER;
const ADMIN_PASS = process.env.STS_ADMIN_PASS;

fs.mkdirSync(OUT, { recursive: true });

async function launch() {
	for (const channel of ['msedge', 'chrome']) {
		try { return await chromium.launch({ channel }); } catch (e) { /* probar el siguiente */ }
	}
	throw new Error('No se encontró Edge ni Chrome instalado.');
}

(async () => {
	const browser = await launch();
	const ctx = await browser.newContext({ viewport: { width: 1366, height: 820 }, locale: 'es-PE' });
	const page = await ctx.newPage();
	const idle = () => page.waitForLoadState('networkidle').catch(() => {});
	const go = async (url) => { await page.goto(url.startsWith('http') ? url : BASE + url, { waitUntil: 'load' }); await idle(); };
	const shot = async (name, opts) => {
		await page.addStyleTag({ content: '.sts-header{position:static !important} .sts-bot__fab{display:none !important}' }).catch(() => {});
		await page.screenshot({ path: path.join(OUT, name), ...(opts || { fullPage: false }) });
		console.log('  ✔', name);
	};
	const resumen = {};
	console.log('Prueba de venta en', BASE, '· producto:', BUSQUEDA, '· cantidad:', CANTIDAD);

	// 1. Catálogo: tarjeta del producto con su stock ANTES de comprar
	await go('/tienda/');
	const tarjeta = page.locator('li.product', { hasText: new RegExp(BUSQUEDA, 'i') }).first();
	resumen.producto = (await tarjeta.locator('.woocommerce-loop-product__title').innerText()).trim();
	resumen.stock_catalogo_antes = (await tarjeta.locator('.sts-stock').innerText()).trim();
	await tarjeta.screenshot({ path: path.join(OUT, '01-catalogo-stock-antes.png') });
	console.log('  ✔ 01-catalogo-stock-antes.png ·', resumen.stock_catalogo_antes);

	// 2. Ficha del producto con el stock ANTES
	await go(`/?s=${encodeURIComponent(BUSQUEDA)}&post_type=product`);
	resumen.stock_ficha_antes = (await page.locator('p.stock').first().innerText()).trim();
	await shot('02-ficha-stock-antes.png');
	console.log('     stock antes:', resumen.stock_ficha_antes);

	// 3. Agregar al carrito
	await page.fill('input.qty', String(CANTIDAD));
	await page.click('.single_add_to_cart_button');
	await page.waitForSelector('.woocommerce-message, .sts-toast.is-visible', { timeout: 20000 }).catch(() => {});
	await idle();
	await shot('03-agregado-al-carrito.png');

	// 4. Carrito con cupón
	await go('/carrito/');
	await page.fill('#coupon_code', 'BIENVENIDO10');
	await page.click('button[name="apply_coupon"]');
	await idle();
	await page.waitForSelector('.woocommerce-message', { timeout: 15000 }).catch(() => {});
	await shot('04-carrito-con-cupon.png', { fullPage: true });

	// 5. Checkout: boleta con DNI
	await go('/finalizar-compra/');
	await page.selectOption('#billing_comprobante', 'boleta');
	await page.fill('#billing_documento', '45871236');
	await page.fill('#billing_first_name', 'Luis');
	await page.fill('#billing_last_name', 'Ramírez');
	await page.fill('#billing_address_1', 'Av. Arequipa 2450');
	await page.fill('#billing_address_2', 'Lince');
	await page.fill('#billing_city', 'Lima');
	await page.fill('#billing_phone', '987654321');
	await page.fill('#billing_email', 'luis.ramirez@demo.test');
	await idle();
	await shot('05-checkout-datos.png', { fullPage: true });

	// 6. Pago con tarjeta de prueba
	await page.check('#payment_method_sts_card_sim');
	await page.fill('#sts_card_number', '4242 4242 4242 4242');
	await page.check('#terms');
	await page.locator('#payment').scrollIntoViewIfNeeded();
	await shot('06-checkout-pago.png');

	// 7. Pedido recibido
	await Promise.all([page.waitForURL(/order-received|pedido-recibido/, { timeout: 60000 }), page.click('#place_order')]);
	await idle();
	resumen.pedido = (await page.locator('.woocommerce-order-overview__order strong').first().innerText().catch(() => '?')).trim();
	resumen.url_pedido = page.url();
	await shot('07-pedido-recibido.png', { fullPage: true });
	console.log('     pedido #' + resumen.pedido);

	// 8. Ficha del producto con el stock DESPUÉS
	await go(`/?s=${encodeURIComponent(BUSQUEDA)}&post_type=product`);
	resumen.stock_ficha_despues = (await page.locator('p.stock').first().innerText()).trim();
	await shot('08-ficha-stock-despues.png');
	console.log('     stock después:', resumen.stock_ficha_despues);

	// 9. Catálogo después
	await go('/tienda/');
	const tarjeta2 = page.locator('li.product', { hasText: new RegExp(BUSQUEDA, 'i') }).first();
	resumen.stock_catalogo_despues = (await tarjeta2.locator('.sts-stock').innerText()).trim();
	await tarjeta2.screenshot({ path: path.join(OUT, '09-catalogo-stock-despues.png') });
	console.log('  ✔ 09-catalogo-stock-despues.png ·', resumen.stock_catalogo_despues);

	// 10. Chatbot responde con el stock actualizado
	await go('/');
	await page.click('.sts-bot__fab');
	await page.fill('.sts-bot__form input', `¿Cuántas unidades hay de ${BUSQUEDA} dieléctricos?`.replace('guantes dieléctricos', 'guantes dieléctricos'));
	await page.press('.sts-bot__form input', 'Enter');
	await page.waitForSelector('.sts-bot__typing', { state: 'attached', timeout: 5000 }).catch(() => {});
	await page.waitForSelector('.sts-bot__typing', { state: 'detached', timeout: 20000 });
	await page.waitForTimeout(500);
	await page.addStyleTag({ content: '.sts-header{position:static !important}' });
	await page.screenshot({ path: path.join(OUT, '10-chatbot-stock-actualizado.png') });
	console.log('  ✔ 10-chatbot-stock-actualizado.png');

	// 11-14. Administración
	if (ADMIN_USER && ADMIN_PASS) {
		const adm = await ctx.newPage();
		await adm.goto(BASE + '/wp-login.php', { waitUntil: 'load' });
		await adm.fill('#user_login', ADMIN_USER);
		await adm.fill('#user_pass', ADMIN_PASS);
		await Promise.all([adm.waitForURL(/wp-admin/, { timeout: 60000 }), adm.click('#wp-submit')]);
		const aGo = async (u) => { await adm.goto(BASE + u, { waitUntil: 'load' }); await adm.waitForLoadState('networkidle').catch(() => {}); };
		const aShot = async (name, opts) => { await adm.screenshot({ path: path.join(OUT, name), ...(opts || { fullPage: false }) }); console.log('  ✔', name); };

		await aGo('/wp-admin/admin.php?page=wc-orders');
		await aShot('11-admin-lista-de-pedidos.png');

		const id = (resumen.url_pedido.match(/order-received\/(\d+)/) || [])[1];
		if (id) {
			await aGo('/wp-admin/admin.php?page=wc-orders&action=edit&id=' + id);
			await aShot('12-admin-pedido-detalle.png', { fullPage: true });
		}
		await aGo('/wp-admin/edit.php?post_type=product&s=' + encodeURIComponent(BUSQUEDA));
		await aShot('13-admin-producto-stock.png');
		await aGo('/wp-admin/admin.php?page=sts-store&tab=correos');
		await aShot('14-admin-correo-confirmacion.png');
	}

	if (process.env.STS_GUARDAR_RESUMEN) fs.writeFileSync(path.join(OUT, 'resumen.json'), JSON.stringify(resumen, null, 2));
	console.log('\nResumen:', JSON.stringify(resumen, null, 2));
	await browser.close();
})().catch((e) => { console.error('Falló:', e.message); process.exit(1); });
