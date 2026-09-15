# E-commerce de Productos de Ingeniería Eléctrica (Proyecto Universitario)

Proyecto de curso: tienda en línea (venta con stock) de productos de ingeniería
eléctrica, construida en WordPress + WooCommerce. Está inspirado en el sitio
real de alquiler de **ST Energy** (`strental.com.pe`), pero adaptado al
requisito del curso: en vez de alquiler, este proyecto maneja **venta con
inventario/stock**.

Este repositorio es independiente del repositorio interno de la empresa
(`stenergy-wordpress`). Aquí solo va lo necesario para mostrarle el avance al
profesor, sin datos comerciales reales ni plugins de pago.

## Estado actual

✅ Entrega 1 — Landing page (contenido real extraído + capturas + catálogo de
   ejemplo para WooCommerce)
🔲 Entrega 2 — Catálogo de productos con stock (ampliar el catálogo de ejemplo)
🔲 Entrega 3 — Carrito de compras
🔲 Entrega 4 — Checkout / pasarela de pago
🔲 Entrega 5 — Panel de administración de pedidos e inventario

(Ajustar esta lista según el cronograma real que dé el profesor.)

## Qué NO va en este repositorio

- **Código de plugins de terceros**, ni gratis ni de pago (Elementor,
  Elementor Pro, WooCommerce, etc.) — se documentan como dependencias en
  [`content/dependencias.md`](content/dependencias.md), no se vendorizan.
  En especial, **Elementor Pro** es de pago y su licencia no permite
  redistribuir su código fuente.
- **Datos comerciales reales** de ST Energy (proveedores, precios de
  alquiler reales, clientes, pedidos). El catálogo de ejemplo en
  `content/entrega-1-landing/productos-woocommerce-ejemplo.csv` usa
  productos y precios ficticios, en modalidad de venta (no alquiler).
- **`wp-config.php`** ni ninguna credencial de base de datos.
- Backups completos (`.wpress`, `.zip`) — se sube el contenido ya extraído
  y revisado, no el backup crudo.

## Cómo se genera el contenido de cada entrega

La landing y el resto del sitio están armados con Elementor dentro de la base
de datos de WordPress, no como archivos de tema sueltos. Para traer ese
contenido a este repo:

1. En el WordPress real, ir a **Plugins > All-in-One WP Migration > Export** y
   exportar un backup completo (`.wpress`).
2. Extraer de ese backup solo el contenido de las páginas involucradas en la
   entrega actual (nunca el dump completo de la base de datos).
3. Revisar que no haya datos comerciales reales (precios, proveedores,
   contactos de clientes) antes de commitear.

## Estructura

```text
stenergy-ecommerce-universidad/
├── .gitignore
├── README.md
├── docs/
│   └── entregas.md                    # detalle de cada entrega y su alcance
└── content/
    ├── dependencias.md                # tema y plugins usados, sin vendorizar código
    └── entrega-1-landing/
        ├── README.md                  # qué es este contenido y qué le falta
        ├── homepage.sql               # wp_posts/wp_postmeta reales de la home
        ├── productos-woocommerce-ejemplo.csv  # catálogo de ejemplo (Products > Import)
        └── screenshots/               # capturas de la landing real en vivo
```
