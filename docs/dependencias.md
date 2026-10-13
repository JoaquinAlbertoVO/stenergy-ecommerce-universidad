# Dependencias

Todo el software incluido es libre (GPL) y viene dentro de `wp-content/`. Lo único que no se incluye es lo que no se
puede redistribuir por licencia.

## Lo que usa la tienda (se activa solo en la primera visita)

| Componente | Tipo | Para qué |
|---|---|---|
| WordPress (es_ES) | núcleo | Sitio y panel |
| **WooCommerce** | plugin gratuito | Catálogo, stock, carrito, checkout, pedidos, cupones, envíos |
| **Hello Elementor** | tema padre (gratuito) | Estructura base; el diseño es del tema hijo *ST Store* |
| **ST Store** (`themes/stenergy-store`) | tema hijo propio | Portada, encabezado, estilos, imágenes referenciales de producto |
| **ST Store - Núcleo** (`mu-plugins/stenergy-store-*`) | código propio | Configuración Perú (PEN, IGV, envíos), carga de CSV, checkout DNI/RUC, pasarelas, estados de pedido, panel |
| **ST Store - Chatbot** (`plugins/stenergy-chatbot`) | plugin propio | Asistente virtual |

## Incluidos pero apagados (de la Entrega 1)

Elementor, Essential Addons for Elementor, FiboSearch, Social Chat (WhatsApp) y Code Snippets siguen en
`wp-content/plugins/`, **inactivos**. Se pueden activar desde el panel si se quiere construir páginas con Elementor, pero la
tienda no los necesita.

## No incluido

**Elementor Pro** es de pago y su licencia no permite redistribuir el código. En la Entrega 1 la landing original lo usaba
para dos widgets (carrusel y grilla dinámica de productos); en la Entrega 2 esas secciones las resuelve el tema propio con
WooCommerce, así que **ya no hace falta**.
