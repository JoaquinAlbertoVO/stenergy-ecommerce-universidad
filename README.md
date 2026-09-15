# E-commerce de Productos de Ingeniería Eléctrica (Proyecto Universitario)

Proyecto de curso: tienda en línea (venta con stock) de productos de ingeniería
eléctrica, construida en WordPress. Está inspirado en el sitio real de alquiler
de **ST Energy** (`stenergyedu.com`), pero adaptado al requisito del curso: en
vez de alquiler, este proyecto maneja **venta con inventario/stock**.

Este repositorio es independiente del repositorio interno de la empresa
(`stenergy-wordpress`). Aquí solo va lo necesario para mostrarle el avance al
profesor, sin datos comerciales reales ni plugins de pago.

## Estado actual

🔲 Entrega 1 — Landing page (en progreso)
🔲 Entrega 2 — Catálogo de productos con stock
🔲 Entrega 3 — Carrito de compras
🔲 Entrega 4 — Checkout / pasarela de pago
🔲 Entrega 5 — Panel de administración de pedidos e inventario

(Ajustar esta lista según el cronograma real que dé el profesor.)

## Qué NO va en este repositorio

- **`wp-content/plugins/pro-elements`** (Elementor Pro): es un plugin de pago,
  su licencia no permite redistribuir el código fuente. Para el curso alcanza
  con Elementor gratuito.
- **Datos comerciales reales** de ST Energy (proveedores, precios de alquiler
  reales, clientes). En `database/muestra/` va solo data de ejemplo (productos
  ficticios o genéricos, con precio de venta y stock).
- **`wp-config.php`** ni ninguna credencial de base de datos.
- Backups completos (`.wpress`, `.zip`) — se sube el contenido ya extraído.

## Cómo se genera el contenido de cada entrega

La landing y el resto del sitio están armados con Elementor dentro de la base
de datos de WordPress, no como archivos de tema sueltos. Para traer ese
contenido a este repo:

1. En el WordPress real, ir a **Plugins > All-in-One WP Migration > Export** y
   exportar un backup completo (`.wpress`).
2. Extraer de ese backup solo lo necesario para la entrega actual (tema,
   plugins gratuitos usados, y el contenido de las páginas involucradas).
3. Reemplazar cualquier dato comercial real (precios, proveedores, contactos)
   por datos de ejemplo antes de commitear.

## Estructura

```text
stenergy-ecommerce-universidad/
├── .gitignore
├── README.md
├── docs/
│   └── entregas.md          # detalle de cada entrega y su alcance
├── wp-content/
│   ├── themes/               # tema usado por la landing (pendiente de extraer)
│   └── plugins/               # solo plugins gratuitos necesarios
└── database/
    └── muestra/               # datos de ejemplo, nunca datos reales del negocio
```
