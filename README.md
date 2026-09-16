# E-commerce de Productos de Ingeniería Eléctrica (Proyecto Universitario)

Proyecto de curso: tienda en línea (venta con stock) de productos de ingeniería
eléctrica, construida en WordPress + WooCommerce. Está inspirado en el sitio
real de alquiler de **ST Energy** (`strental.com.pe`), pero adaptado al
requisito del curso: en vez de alquiler, este proyecto maneja **venta con
inventario/stock**.

Este es un proyecto WordPress completo y clonable: incluye el núcleo de
WordPress, el tema y los plugins gratuitos usados por la landing. Para
levantarlo en tu propio localhost, sigue [`INSTALL.md`](INSTALL.md).

## Estado actual

✅ **Entrega 1 — Landing page** (esta entrega)
🔲 Entrega 2 — Catálogo de productos con stock
🔲 Entrega 3 — Carrito de compras
🔲 Entrega 4 — Checkout / pasarela de pago
🔲 Entrega 5 — Panel de administración de pedidos e inventario

Detalle de alcance de cada entrega en [`docs/entregas.md`](docs/entregas.md).

## Vista previa de la landing (Entrega 1)

![Hero](content/entrega-1-landing/screenshots/01-hero.png)
![Carrusel de productos](content/entrega-1-landing/screenshots/02-carrusel-productos-top.png)
![Línea de equipos y productos](content/entrega-1-landing/screenshots/03-linea-equipos-y-productos.png)
![Productos y contadores](content/entrega-1-landing/screenshots/04-productos-y-contadores.png)
![Por qué elegirnos](content/entrega-1-landing/screenshots/05-porque-elegirnos.png)
![Footer y contacto](content/entrega-1-landing/screenshots/06-footer-contacto.png)

## Estructura

```text
stenergy-ecommerce-universidad/
├── INSTALL.md                 # cómo clonar y levantar el sitio en tu localhost
├── wp-admin/, wp-includes/, wp-*.php   # núcleo de WordPress (oficial, GPL)
├── wp-config-sample.php       # copiar a wp-config.php con tus datos de BD
├── wp-content/
│   ├── themes/hello-elementor/
│   └── plugins/                # Elementor, WooCommerce, Essential Addons, etc. (todos gratuitos)
├── sample-data/
│   ├── homepage.sql            # contenido real de la home (texto + Elementor)
│   └── productos-woocommerce-ejemplo.csv  # catálogo de ejemplo con stock
├── docs/
│   └── entregas.md             # alcance de cada entrega
└── content/
    ├── dependencias.md         # detalle de plugins y por qué Elementor Pro no está incluido
    └── entrega-1-landing/
        └── screenshots/        # capturas de la landing real en vivo
```

## Qué NO va en este repositorio

- **Elementor Pro**: es un plugin de pago, su licencia no permite
  redistribuir el código fuente. Ver [`INSTALL.md`](INSTALL.md) para el
  detalle de qué secciones de la landing lo necesitan.
- **Datos comerciales reales** de ST Energy (proveedores, precios de
  alquiler reales, clientes, pedidos). El catálogo de ejemplo usa
  productos y precios ficticios, en modalidad de venta (no alquiler).
- **`wp-config.php`** ni ninguna credencial de base de datos real.
