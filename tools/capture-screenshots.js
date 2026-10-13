#!/usr/bin/env node
/**
 * Genera las capturas de pantalla del proyecto recorriendo la tienda como un cliente:
 * portada, catálogo, ficha, chatbot, carrito con cupón, checkout con factura (RUC),
 * pedido recibido y, si se indican credenciales, el panel de administración.
 *
 * Requiere Node.js y Edge o Chrome instalados:
 *   npm i --no-save playwright-core
 *   node tools/capture-screenshots.js http://localhost/stenergy-store
 * Capturas del panel (opcional):
 *   STS_ADMIN_USER=usuario STS_ADMIN_PASS=clave node tools/capture-screenshots.js http://localhost/stenergy-store
 *
 * Idealmente se corre sobre una instalación recién hecha, para que el stock y los pedidos
 * se vean tal como los dejan los CSV.
 */
const path = require('path');
const fs = require('fs');
const { chromium } = require('playwright-core');

const BASE = (process.argv[2] || 'http://localhost/stenergy-store').replace(/\/$/, '');
const OUT = process.argv[3] || path.join(__dirname, '..', 'docs', 'capturas', 'entrega-2');
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
	const shot = async (name, opts) => {
		// El encabezado fijo (sticky) se repite a mitad de página en capturas completas: se fija arriba solo para la captura.
		await page.addStyleTag({ content: '.sts-header{position:static !important}' });
		await page.screenshot({ path: path.join(OUT, name), ...(opts || { fullPage: true }) });
		console.log('  ✔', name);
	};
	const idle = () => page.waitForLoadState('networkidle').catch(() => {});
	const go = async (url) => { await page.goto(url.startsWith('http') ? url : BASE + url, { waitUntil: 'load' }); await idle(); };

	console.log('Capturas en', OUT);

	// 1-3. Portada, catálogo, ficha
	await go('/');
	await shot('01-portada.png');
	await go('/tienda/');
	await shot('02-catalogo.png');
	await go('/?s=fluke&post_type=product');
	await shot('03-ficha-producto.png');

	// 4. Chatbot con datos reales
	await go('/');
	await page.click('.sts-bot__fab');
	const ask = async (text) => {
		await page.fill('.sts-bot__form input', text);
		await page.press('.sts-bot__form input', 'Enter');
		await page.waitForSelector('.sts-bot__typing', { state: 'attached', timeout: 5000 }).catch(() => {});
		await page.waitForSelector('.sts-bot__typing', { state: 'detached', timeout: 20000 });
		await page.waitForTimeout(300);
	};
	await ask('¿Cómo son los envíos?');
	await ask('¿Aceptan Yape?');
	await ask('¿Hay stock de multímetro Fluke?');
	await page.waitForTimeout(500);
	await shot('04-chatbot.png', { fullPage: false });
	await page.click('.sts-bot__close'); // que no aparezca abierto en las demás capturas

	// 5. Carrito con cupón
	await go('/?s=detector+de+tension&post_type=product');
	await page.fill('input.qty', '2');
	await page.click('.single_add_to_cart_button');
	await idle();
	await go('/tienda/');
	await page.click('li.product:nth-child(2) .add_to_cart_button');
	await page.waitForSelector('.sts-toast.is-visible', { timeout: 15000 }).catch(() => {});
	await go('/carrito/');
	await page.fill('#coupon_code', 'BIENVENIDO10');
	await page.click('button[name="apply_coupon"]');
	await idle();
	await page.waitForSelector('.woocommerce-message', { timeout: 15000 }).catch(() => {});
	await shot('05-carrito.png');

	// 6. Checkout con factura (RUC) y tarjeta de prueba
	await go('/finalizar-compra/');
	await page.selectOption('#billing_comprobante', 'factura');
	await page.fill('#billing_documento', '20100070970');
	await page.fill('#billing_razon_social', 'Constructora Demo SAC');
	await page.fill('#billing_first_name', 'Ana');
	await page.fill('#billing_last_name', 'Quispe');
	await page.fill('#billing_address_1', 'Av. Javier Prado 1234');
	await page.fill('#billing_address_2', 'San Isidro, cerca al óvalo');
	await page.fill('#billing_city', 'Lima');
	await page.fill('#billing_phone', '987654321');
	await page.fill('#billing_email', 'ana.quispe@constructora-demo.test');
	await idle();
	await page.check('#payment_method_sts_card_sim');
	await page.fill('#sts_card_number', '4242 4242 4242 4242');
	await page.check('#terms');
	await shot('06-checkout-factura.png');

	// 7. Pedido recibido
	await Promise.all([page.waitForURL(/order-received|pedido-recibido/, { timeout: 60000 }), page.click('#place_order')]);
	await idle();
	await shot('07-pedido-recibido.png');

	// 8. Panel de administración (opcional)
	if (ADMIN_USER && ADMIN_PASS) {
		await go('/wp-login.php');
		await page.fill('#user_login', ADMIN_USER);
		await page.fill('#user_pass', ADMIN_PASS);
		await Promise.all([page.waitForURL(/wp-admin/, { timeout: 60000 }), page.click('#wp-submit')]);
		await idle();
		await go('/wp-admin/');
		await shot('08-admin-escritorio.png');
		await go('/wp-admin/edit.php?post_type=product');
		await shot('09-admin-productos.png');
		await go('/wp-admin/admin.php?page=wc-orders');
		await shot('10-admin-pedidos.png');
		await go('/wp-admin/admin.php?page=sts-store&tab=correos');
		await shot('11-admin-correos-simulados.png');
	}

	// 9. Móvil
	const mobile = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, deviceScaleFactor: 2, locale: 'es-PE' });
	const m = await mobile.newPage();
	await m.goto(BASE + '/', { waitUntil: 'load' });
	await m.waitForLoadState('networkidle').catch(() => {});
	await m.screenshot({ path: path.join(OUT, '12-movil-portada.png') });
	await m.click('.sts-bot__fab');
	await m.waitForTimeout(500);
	await m.screenshot({ path: path.join(OUT, '13-movil-chatbot.png') });
	console.log('  ✔ capturas móviles');

	await browser.close();
	console.log('Listo.');
})().catch((e) => { console.error('Falló:', e.message); process.exit(1); });
