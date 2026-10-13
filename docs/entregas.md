# Plan de entregas

> Ajustar fechas y alcance según lo que pida el profesor.

## Entrega 1 — Landing page ✅

Landing en WordPress con Elementor, capturas y catálogo de ejemplo. Material en [`entrega-1/`](entrega-1/README.md).
La portada actual ya no depende de Elementor Pro: es parte del tema propio (ver Entrega 2).

## Entrega 2 — ≈ 80 % del proyecto (en curso)

Pedido del profesor: flujo de compra **completo** (carrito y todo lo que conlleva), **4 productos por integrante** y,
si es posible, **chatbot**.

| Requisito | Resultado |
|---|---|
| Flujo de compra completo | ✅ Catálogo → carrito → cupón → checkout (boleta/factura) → pago → pedido → seguimiento. Mapa paso a paso del diagrama UML en [`flujo-de-compra.md`](flujo-de-compra.md) |
| Manejo de stock | ✅ Stock por producto, descuento al vender, reposición al cancelar, reserva en pedidos pendientes, avisos de "últimas unidades" y "agotado" |
| 4 productos por integrante (16) | 🔄 Cada integrante edita su CSV en `sample-data/productos-por-integrante/`. Joaquín ✅ · Mayquel y Jordy tienen 3 · Alex falta crear su archivo |
| Chatbot | ✅ Plugin propio con datos reales de la tienda. [`chatbot.md`](chatbot.md) |
| Evidencia | ✅ Capturas en [`capturas/entrega-2/`](capturas/entrega-2/) y prueba automática `tools/test_purchase_flow.py` (28 verificaciones) |

Cómo se verifica rápido: ver el recorrido de demostración en [`flujo-de-compra.md`](flujo-de-compra.md).

## Para llegar al 100 %

- Completar los 16 productos y sus fotos (`sample-data/imagenes/<SKU>.jpg`). Ya hay fotos para 5 productos; faltan las de los otros.
- **Analítica web** (el curso lo pide): conectar Google Analytics 4 con los eventos de e-commerce (`view_item`, `add_to_cart`,
  `begin_checkout`, `purchase`) y un tablero con embudo de compra; sumar las estadísticas del chatbot.
- Pasarela de pago real (Niubiz / Culqi / Izipay) y comprobante electrónico autorizado por SUNAT *(solo si el curso lo exige)*.
- Pruebas con usuarios y ajustes de usabilidad; informe final.

## Cómo se genera y se prueba el contenido (proceso interno)

- El catálogo se carga desde los CSV al abrir el sitio por primera vez y con el botón *Re-sincronizar* del panel.
- `python tools/test_purchase_flow.py <url>` recorre una compra completa y valida las reglas de negocio.
- `node tools/capture-screenshots.js <url>` regenera todas las capturas. Ver [`../tools/README.md`](../tools/README.md).
