# Flujo de compra: del diagrama UML a lo que ya está implementado

Diagrama UML de actividades (3 carriles: Cliente, Sistema, Almacén): [`diagramas/flujo-compra.puml`](diagramas/flujo-compra.puml)
(se renderiza pegándolo en planttext.com o plantuml.com).

La tabla muestra, paso por paso, dónde vive cada acción en la tienda y cómo comprobarla.

| Paso del diagrama | Cómo está implementado | Dónde verlo |
|---|---|---|
| Buscar o filtrar productos | Buscador del encabezado (nombre, marca, SKU), menú *Catálogo* por categorías, orden y paginación de WooCommerce | Encabezado, `/tienda/` |
| Ver ficha (precio, stock, especificaciones) | Ficha con precio (IGV incluido), mensaje de stock en vivo, descripción, pestañas y reseñas | `/producto/...` |
| ¿Hay stock? → "Agotado" | Con stock controlado por producto: "N unidades en stock", "¡Últimas N unidades!", "Agotado por ahora"; el botón de compra se bloquea en 0 | Tarjetas del catálogo y ficha |
| Agregar al carrito + actualizar contador | Botón AJAX; el contador y el subtotal del encabezado se actualizan solos y aparece un aviso "agregado" | Encabezado |
| Revisar carrito y cantidades | Página de carrito con cantidades, quitar, totales con IGV; la cantidad máxima respeta el stock | `/carrito/` |
| Iniciar checkout | Botón *Finalizar compra* | `/finalizar-compra/` |
| ¿Cuenta? Iniciar sesión / Registrarse / Invitado | Checkout permite las tres opciones | Checkout |
| Dirección de envío y **datos de facturación** | Boleta (DNI de 8 dígitos) o Factura (RUC de 11 dígitos con dígito verificador + razón social). Departamentos del Perú | Checkout |
| Calcular costo de envío | Zonas: *Lima y Callao* (S/ 15, gratis desde S/ 500, recojo en tienda) y *Resto del Perú* (S/ 35, gratis desde S/ 2,000) | Checkout |
| Cupón de descuento | Cupón `BIENVENIDO10` (10 %) en carrito o checkout | Carrito |
| Método de pago | Tarjeta (pasarela simulada), Yape/Plin (validación manual), transferencia bancaria, contra entrega (solo Lima) | Checkout |
| Aceptar términos y condiciones | Casilla obligatoria con página de términos | Checkout |
| Procesar pago · ¿Pago aprobado? | Pasarela **simulada**: `4242 4242 4242 4242` aprueba, `4000 0000 0000 0002` rechaza (y permite reintentar). No acepta tarjetas reales | Checkout |
| Revalidar stock (evitar sobreventa) | WooCommerce valida el stock al crear el pedido y reserva las unidades durante 60 min para pedidos sin pagar | Automático |
| Reducir stock (venta definitiva) | Se descuenta al aprobar el pago (o al quedar en espera por Yape/transferencia) y se repone si se cancela | Ficha del producto, panel |
| Generar orden y factura | Pedido numerado con el comprobante solicitado (tipo, RUC/DNI, razón social) en la confirmación, en el correo y en el panel. *(Es un comprobante de demostración, no emite CPE a SUNAT)* | Pedido recibido |
| Correo de confirmación / aviso al almacén | Correos de WooCommerce capturados en una **bandeja simulada** (en localhost no hay servidor de correo) | Panel → ST Store → Correos simulados |
| Preparar pedido · Coordinar despacho | Estados del pedido **En preparación** → **Enviado** (con courier y n.º de seguimiento) | Panel → Pedidos |
| Enviar n.º de seguimiento | Al marcar *Enviado* el cliente recibe un correo con el seguimiento, visible también en *Mi cuenta* | Correos simulados, Mi cuenta |
| Estado "Entregado" | El estado *Completado* de WooCommerce se muestra como **Entregado** | Panel → Pedidos |
| Calificar / reseñar | Reseñas con estrellas habilitadas en las fichas | Ficha del producto |

## Estados del pedido

`Pendiente` → (pago aprobado) `Procesando` → `En preparación` → `Enviado` → `Entregado`
Pago por Yape/Plin o transferencia: `En espera` (stock ya reservado) → `Procesando` cuando el administrador valida el pago.
Pago rechazado: `Fallido` (no se descuenta stock).

## Cómo hacer una compra de demostración completa

1. Entra a la tienda, abre un producto y fíjate en las unidades en stock.
2. Agrega 2 unidades al carrito y aplica el cupón `BIENVENIDO10`.
3. En el checkout elige *Factura*, escribe el RUC de prueba `20100070970` y una razón social.
4. Paga con la tarjeta `4242 4242 4242 4242`. Verás la pantalla de pedido recibido con el comprobante.
5. Vuelve a la ficha: el stock bajó en 2 unidades.
6. En el panel (**WooCommerce → Pedidos**) pasa el pedido a *En preparación* y luego a *Enviado* (escribe un n.º de seguimiento) y revisa el correo en **ST Store → Correos simulados**.

Para validar todo automáticamente: `python tools/test_purchase_flow.py http://localhost/<carpeta>` (ver [`tools/README.md`](../tools/README.md)).
