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

## Si se publica en un servidor real

Hay que agregar un aviso de cookies y de privacidad que explique el uso de Google Analytics (Ley 29733 de Protección de Datos Personales).
Para el localhost del curso no hace falta.
