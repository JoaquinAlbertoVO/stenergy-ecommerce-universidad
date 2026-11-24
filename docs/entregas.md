# Plan de entregas

> Ajustar fechas y alcance según lo que pida el profesor.

## Entrega 1 — Landing page ✅

Landing en WordPress con Elementor, capturas y catálogo de ejemplo. Material en [`entrega-1/`](entrega-1/README.md).
La portada actual ya no depende de Elementor Pro: es parte del tema propio (ver Entrega 2).

## Entrega 2 — ≈ 80 % del proyecto ✅

Pedido del profesor: flujo de compra **completo** (carrito y todo lo que conlleva), **4 productos por integrante** y,
si es posible, **chatbot**.

| Requisito | Resultado |
|---|---|
| Flujo de compra completo | ✅ Catálogo → carrito → cupón → checkout (boleta/factura) → pago → pedido → seguimiento. Mapa paso a paso del diagrama UML en [`flujo-de-compra.md`](flujo-de-compra.md) |
| Manejo de stock | ✅ Stock por producto, descuento al vender, reposición al cancelar, reserva en pedidos pendientes, avisos de "últimas unidades" y "agotado" |
| 4 productos por integrante (16) | ✅ Joaquín, Mayquel, Jordy y Alex: 4 productos cada uno con foto (CSV en `sample-data/productos-por-integrante/`, créditos de fotos en `sample-data/imagenes/CREDITOS.md`) |
| Chatbot | ✅ Plugin propio con datos reales de la tienda. [`chatbot.md`](chatbot.md) |
| Evidencia | ✅ Capturas en [`capturas/entrega-2/`](capturas/entrega-2/) y prueba automática `tools/test_purchase_flow.py` (28 verificaciones) |

Cómo se verifica rápido: ver el recorrido de demostración en [`flujo-de-compra.md`](flujo-de-compra.md).

## Entrega final — 100 % ✅

| Requisito | Resultado |
|---|---|
| 16 productos con foto y stock | ✅ 4 por integrante |
| Analítica web | ✅ Google Analytics 4 con eventos de e-commerce (`view_item_list` → `view_item` → `add_to_cart` → `begin_checkout` → `add_payment_info` → `purchase`), `chatbot_open` y `whatsapp_click`. Guía para armar el embudo en GA4: [`google-analytics-4.md`](google-analytics-4.md) |
| Privacidad y cookies | ✅ Aviso de cookies con Consent Mode v2, páginas Política de cookies y Política de privacidad |
| Diagrama del flujo de compra | ✅ [`diagramas/flujo-compra.png`](diagramas/flujo-compra.png) (editable en draw.io: [`flujo-compra.drawio`](diagramas/flujo-compra.drawio)) |
| Evidencia | ✅ Capturas actualizadas en [`capturas/entrega-2/`](capturas/entrega-2/) y prueba automática (28 verificaciones) |

Fuera de alcance del curso: pasarela de pago real (Niubiz / Culqi / Izipay) y comprobante electrónico SUNAT.
La tarjeta usa una pasarela simulada y Yape/Plin se valida a mano desde el panel.

## Cómo se genera y se prueba el contenido (proceso interno)

- El catálogo se carga desde los CSV al abrir el sitio por primera vez y con el botón *Re-sincronizar* del panel.
- `python tools/test_purchase_flow.py <url>` recorre una compra completa y valida las reglas de negocio.
- `node tools/capture-screenshots.js <url>` regenera todas las capturas. Ver [`../tools/README.md`](../tools/README.md).
