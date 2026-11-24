# Google Analytics 4 en ST Store

Integración propia, sin plugins externos:
[`class-sts-analytics.php`](../wp-content/mu-plugins/stenergy-store-core/includes/class-sts-analytics.php) (servidor) y
[`analytics.js`](../wp-content/mu-plugins/stenergy-store-core/assets/analytics.js) (navegador).
Mientras no haya un ID de medición no se carga nada.

## Activarlo

1. En [analytics.google.com](https://analytics.google.com/), crear una **cuenta** y una **propiedad** (zona horaria Perú, moneda **PEN**).
2. Agregar un **flujo de datos Web** con la URL del sitio. En localhost sirve cualquier URL, por ejemplo `http://localhost/stenergy-store`.
3. Copiar el **ID de medición** (`G-XXXXXXXXXX`).
4. En WordPress, ir a **ST Store → Google Analytics 4**, pegar el ID y guardar.
   También se puede fijar en `wp-config.php` con `define( 'STS_GA4_ID', 'G-XXXXXXXXXX' );`.

Opciones de esa pantalla:

- **Modo depuración:** los eventos aparecen al instante en GA4 → *Administrar → DebugView*. Conviene usarlo para probar y para la demo en clase.
- **No medir administradores** (activado por defecto): las visitas con sesión de administrador no se envían. Para probar, usa una ventana de incógnito o la cuenta `cliente.demo`.

## Eventos

| Evento | Cuándo se envía | Dónde se genera |
|---|---|---|
| `page_view` | Cada página (automático de GA4) | gtag |
| `view_item_list` | Listado de productos: inicio, tienda, categoría, búsqueda, relacionados | servidor |
| `select_item` | Clic en un producto de un listado | navegador |
| `view_item` | Ficha de producto | servidor |
| `add_to_cart` | Botón del catálogo (AJAX) o formulario de la ficha | navegador / sesión de WooCommerce |
| `remove_from_cart` | La "x" de una fila en el carrito | navegador |
| `view_cart` | Página del carrito | servidor |
| `begin_checkout` | Página "Finalizar compra" | servidor |
| `add_shipping_info` · `add_payment_info` | Al pulsar "Realizar el pedido", con el envío y el medio de pago elegidos | navegador |
| `purchase` | Página "Pedido recibido". Se envía **una sola vez por pedido** (queda marcado en el pedido) | servidor |
| `chatbot_open` | Se abre el asistente virtual (evento propio) | navegador |
| `whatsapp_click` | Clic en un enlace de WhatsApp, con `link_location`: encabezado, pie, contenido o chatbot (evento propio) | navegador |
| `social_click` | Clic hacia el perfil de TikTok, Instagram o Facebook, con `social_network` y `link_location` (evento propio) | navegador |
| `share` | Producto compartido (evento recomendado de GA4) con `method` (whatsapp, facebook, copiar_enlace, menu_compartir) e `item_id` | navegador |

Visitas que llegan desde TikTok y otras redes: ver *Medir las visitas que llegan desde TikTok* en [`despliegue.md`](despliegue.md).

Cada producto se envía como `item` con `item_id` (SKU), `item_name`, `item_category`, `price` (con IGV, como lo ve el cliente) y `quantity`.
El evento `purchase` lleva `transaction_id` (número de pedido), `value` (total pagado), `tax` (IGV), `shipping`, `coupon` y `payment_type`.
Los pedidos con pago rechazado no se cuentan como compra.

## Dónde verlo en GA4

- **Tiempo real** y **DebugView**: comprobar que llegan los eventos mientras se hace una compra de prueba.
- *Informes → Monetización → Compras de comercio electrónico*: productos vistos, agregados al carrito y comprados.
  Los informes estándar tardan entre 24 y 48 h en llenarse.
- *Explorar → Exploración del embudo*: armar el embudo `view_item → add_to_cart → begin_checkout → add_payment_info → purchase`.
- Para usar `chatbot_open` y `whatsapp_click` en los informes, márcalos como **eventos clave** en *Administrar → Eventos*.
  El parámetro `link_location` se registra como dimensión personalizada en *Administrar → Definiciones personalizadas*.

## Consentimiento de cookies (Consent Mode v2)

La tienda muestra un aviso de cookies (`includes/class-sts-consent.php`, `assets/consent.js`, `assets/consent.css`):

- Antes de elegir, Consent Mode queda en `denied` y **gtag.js no se descarga**: no se crean `_ga` ni `_ga_*`.
  Los eventos de esa página quedan en `dataLayer` y se envían si el visitante acepta.
- WooCommerce no crea las cookies `sbjs_*` (origen de pedidos) hasta que se acepta (filtro `wc_order_attribution_allow_tracking`).
- Aceptar, Rechazar o Configurar (casilla de Analítica). La elección se guarda 180 días en `sts_cookie_consent`
  (`v1.a1` acepta, `v1.a0` rechaza). Al rechazar se borran las cookies `_ga*` y `sbjs_*`.
- El enlace **Preferencias de cookies** del footer (o cualquier enlace a `#preferencias-cookies`) vuelve a abrir el aviso.
- Página **Política de cookies** (`/politica-de-cookies/`) con la tabla de cookies (`[sts_tabla_cookies]`) y el botón de preferencias
  (`[sts_preferencias_cookies]`). Se crea una sola vez: el texto lo redacta el equipo en el panel y re-aplicar la configuración no lo pisa.

Para probar GA4 en DebugView hay que **aceptar** las cookies primero.
Para volver a ver el aviso: borrar la cookie `sts_cookie_consent` o usar el enlace del footer.
