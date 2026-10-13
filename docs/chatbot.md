# Asistente virtual (chatbot) de ST Store

Plugin propio: [`wp-content/plugins/stenergy-chatbot`](../wp-content/plugins/stenergy-chatbot). Aparece como botón
amarillo "¿Te ayudo?" en todas las páginas de la tienda.

## Qué responde (con datos reales de la tienda)

| Consulta del cliente | Qué hace el bot |
|---|---|
| «¿Hay stock de multímetro Fluke?», «precio del panel solar», «UPS» | Busca en el catálogo (nombre, SKU, categoría, etiquetas) y muestra tarjetas con **stock en vivo**, precio y botones *Ver* / *Agregar* |
| «¿Qué categorías tienen?», «productos de iluminación» | Lista las categorías con su cantidad de productos o muestra los de una categoría |
| «Recomiéndame algo» | Sugiere productos destacados o con stock |
| «¿Cómo son los envíos?» | Lee las zonas y tarifas **configuradas en WooCommerce** (si cambian, el bot responde lo nuevo) |
| «¿Qué medios de pago aceptan?» | Lista las pasarelas **activas** en ese momento |
| «¿Emiten factura con RUC?» | Explica boleta/factura y la validación de RUC |
| «Tienen cupón?» | Muestra los cupones vigentes |
| «Quiero ver mi pedido» | Pide **número de pedido + correo** y, si coinciden, informa estado, total y seguimiento |
| «Hablar con un asesor», «cotizar por volumen» | Entrega WhatsApp, correo, teléfono y horario |
| Garantía, devoluciones, horario, cómo comprar | Respuestas con los textos de la política de la tienda |

## Cómo está hecho

- **Sin IA externa ni claves de API**: es un motor de reglas en PHP (`includes/class-chatbot-engine.php`) que detecta la
  intención del mensaje (normaliza tildes y mayúsculas) y consulta WooCommerce. Funciona sin internet, es gratis y es
  predecible, lo que lo hace fácil de explicar y de evaluar.
- **Endpoint REST** `POST /wp-json/stenergy-chatbot/v1/message` → devuelve mensajes de texto, tarjetas de producto,
  botones rápidos y enlaces. El front-end (`assets/chatbot.js`) los pinta con `textContent` (sin HTML de terceros).
- La conversación se conserva mientras navegas (sessionStorage) y se borra al cerrar la pestaña.

## Privacidad y seguridad

- No se guarda el texto de las conversaciones. Solo se cuenta el **tipo de consulta** y, de los mensajes que el bot no
  entendió, el texto sin correos ni números largos (para mejorar el bot): **ST Store → Chatbot**.
- La consulta de pedidos exige número **y** correo coincidentes, nunca revela si el pedido existe y tiene límite de intentos.
- Límite de 60 mensajes por IP cada 10 minutos.

## Cómo ampliarlo

1. Una intención nueva = una entrada en el arreglo `$rules` de `reply()` + un método `answer_<intencion>()`.
2. Para respuestas con IA generativa, se puede conectar el caso `fallback` a un modelo de lenguaje (requiere una clave
   de API y revisar la privacidad); hoy se prefirió un bot sin dependencias externas.
3. Pruebas automáticas: `python tools/test_purchase_flow.py` incluye el chatbot (sección 6).

## Para el análisis (curso de Analítica Web)

La página **ST Store → Chatbot** muestra qué preguntan más los clientes (stock, envíos, pagos...) y qué consultas no se
pudieron responder. Es insumo directo para decidir qué productos sumar, qué políticas aclarar y qué respuestas mejorar.
