# Herramientas de desarrollo

Nada de esto es necesario para usar la tienda; sirve para probar y documentar.

## `test_purchase_flow.py` — prueba de humo del flujo de compra

Recorre por HTTP lo que haría un cliente (producto → carrito → cupón → checkout con boleta/factura → pago aprobado o
rechazado → Yape → pedido recibido) y comprueba que el **stock baja**, que RUC/DNI inválidos se rechazan y que el chatbot
responde con datos reales. Solo necesita Python 3 (sin librerías extra).

```bash
python tools/test_purchase_flow.py http://localhost/stenergy-store
```

Asume el catálogo de la demo (usa los productos `JOA-002` y `JSO-001`). Crea pedidos de prueba, así que úsalo en una
instalación de desarrollo, no con ventas reales.

## `capture-screenshots.js` — capturas de pantalla automáticas

Hace el mismo recorrido con Edge/Chrome y guarda las capturas en `docs/capturas/entrega-2/` (portada, catálogo, ficha,
chatbot, carrito con cupón, checkout con factura, pedido recibido, panel y vista móvil).

```bash
npm i --no-save playwright-core
node tools/capture-screenshots.js http://localhost/stenergy-store
# con capturas del panel de administración:
STS_ADMIN_USER=usuario STS_ADMIN_PASS=clave node tools/capture-screenshots.js http://localhost/stenergy-store
```

Conviene correrlo en una instalación recién hecha para que el stock y los pedidos se vean limpios.
