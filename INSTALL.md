# Cómo levantar ST Store en tu localhost (XAMPP)

Este repositorio es un proyecto WordPress completo (núcleo, tema, plugins y datos de la demo).
**No necesitas importar ninguna base de datos**: después de instalar WordPress, la tienda se
configura sola la primera vez que se abre.

**Requisitos:** XAMPP con Apache y MySQL (PHP 8.0 o superior) y Git. No hace falta internet.

## Pasos

1. **Clonar dentro de `htdocs`**

   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/JoaquinAlbertoVO/stenergy-ecommerce-universidad.git
   ```

   (Puedes renombrar la carpeta, por ejemplo a `stenergy-store`; abajo se usa ese nombre.)

2. **Iniciar Apache y MySQL** desde el panel de XAMPP y **crear una base de datos vacía**: en
   `http://localhost/phpmyadmin` → *Nueva* → nombre `stenergy_store`, cotejamiento `utf8mb4_unicode_ci`.

3. **Abrir** `http://localhost/stenergy-store/` y seguir el asistente de WordPress:
   - Idioma: Español.
   - Base de datos: nombre `stenergy_store`, usuario `root`, contraseña vacía, servidor `localhost`, prefijo `wp_`.
   - Datos del sitio: título *ST Store*, el usuario y la contraseña de administrador que tú elijas.

4. **Entrar al sitio o al panel.** En la primera visita (la primera vez puede tardar hasta medio minuto) se activa WooCommerce y el
   chatbot, se aplica el tema *ST Store*, y se crean las páginas, el IGV, los envíos, los medios de pago,
   el cupón y los **productos de los CSV** de cada integrante. Al terminar verás la portada de la tienda.

Tienda: `http://localhost/stenergy-store/` · Panel: `http://localhost/stenergy-store/wp-admin/`

## Datos para probar (solo localhost)

| Qué | Valor |
|---|---|
| Cliente de demostración | usuario `cliente.demo` · contraseña `Cliente-Demo-2026` |
| Cupón de descuento | `BIENVENIDO10` (10 %) |
| Tarjeta aprobada / rechazada | `4242 4242 4242 4242` / `4000 0000 0000 0002` (pasarela simulada, no cobra nada real) |
| Factura | RUC válido de prueba `20100070970` + cualquier razón social |
| Boleta | cualquier DNI de 8 dígitos |
| Yape / Plin | cualquier n.º de operación de 6 a 12 dígitos (el pedido queda *En espera*) |
| Correos | no se envían de verdad: míralos en **ST Store → Correos simulados** |

Recorrido sugerido de una compra: [`docs/flujo-de-compra.md`](docs/flujo-de-compra.md).

## Trabajar en equipo (cada quien sube sus productos)

El catálogo sale de `sample-data/productos-por-integrante/*.csv` (un archivo por integrante). Después de un
`git pull`, en el panel: **ST Store → Estado → Re-sincronizar catálogo desde los CSV**. Actualiza por SKU, no duplica.
Más detalle en [`sample-data/productos-por-integrante/README.md`](sample-data/productos-por-integrante/README.md).

## Problemas frecuentes

| Síntoma | Solución |
|---|---|
| Las páginas internas (`/tienda/`, `/carrito/`) dan 404 | Apache necesita `mod_rewrite` y `AllowOverride All` (vienen así en XAMPP). Entra a *Ajustes → Enlaces permanentes* y pulsa **Guardar cambios** para regenerar el `.htaccess`. |
| La web se ve en inglés | *Ajustes → Generales → Idioma del sitio → Español*. Los archivos de traducción ya vienen incluidos. |
| El sitio no se configuró solo | Abre **ST Store → Estado** en el panel y usa *Re-aplicar configuración completa*. También sirve `wp stenergy setup` si usas WP-CLI. |
| Quiero empezar de cero | Borra la base de datos, créala de nuevo y repite el paso 3. |
| Me aparece la carpeta con otro nombre en la URL | Usa el nombre real de tu carpeta en `htdocs`; no hay que cambiar nada en el proyecto. |

## Si algún día se publica en un servidor real

- Define `define( 'STS_DISABLE_BOOTSTRAP', true );` en `wp-config.php` para apagar la configuración automática.
- Cambia o elimina la cuenta `cliente.demo` y usa contraseñas propias.
- La **pasarela de tarjeta es simulada** y Yape/Plin es manual: para cobrar de verdad hay que integrar una pasarela real
  (Niubiz, Culqi, Izipay, Mercado Pago...) y un servicio de comprobantes electrónicos autorizado por SUNAT.
- Para enviar correos reales: `define( 'STS_REAL_MAIL', true );` y configurar un SMTP.

## Qué incluye y qué no

Incluye WordPress, el tema hijo *ST Store*, WooCommerce, el plugin del chatbot y los datos de ejemplo, todo con software
libre. **No** incluye Elementor Pro (plugin de pago); ya no hace falta: la portada es parte del tema.
Detalle de dependencias en [`docs/dependencias.md`](docs/dependencias.md).
