# Entrega 1 — Notas sobre el contenido real de la landing

El contenido y catálogo de ejemplo ahora viven en
[`/sample-data`](../../sample-data/) (`homepage.sql` y
`productos-woocommerce-ejemplo.csv`), junto con la guía de instalación en
[`/INSTALL.md`](../../INSTALL.md). Esta carpeta guarda solo el detalle y
las capturas.

## Sobre `homepage.sql`

Contiene, tal cual salieron del backup real de WordPress (All-in-One WP
Migration, 2026-09-15), las filas de:

- `wp_posts` (id 9) — el contenido y textos reales de la página de inicio.
- `wp_postmeta` — la data de Elementor que arma el diseño
  (`_elementor_data`), versión de Elementor, plantilla, título/meta SEO
  (Yoast) y el ID de la imagen destacada.

Nota: en el dump original la tabla se llama `SERVMASK_PREFIX_posts` /
`SERVMASK_PREFIX_postmeta` — es el prefijo genérico que usa All-in-One WP
Migration (ver `INSTALL.md` para cómo importarlo).

Se revisó el contenido antes de subirlo: es texto de marketing público
(el que cualquier visitante ve en la web), sin contraseñas, tokens ni datos
de clientes.

## Widgets usados en esta página

Extraídos del JSON de `_elementor_data`:

**Gratuitos (Elementor Core):** heading, text-editor, image, icon, button,
divider, counter, flip-box.

**Requieren Elementor Pro:** `loop-carousel` y `woocommerce-products` — se
usan para el carrusel "Productos Top" y la grilla "Nuestros Productos", que
muestran productos reales de WooCommerce de forma dinámica.

Como Elementor Pro es un plugin de pago, su código **no se incluye en este
repositorio** (ver `../dependencias.md` e `INSTALL.md`).

## Capturas

`screenshots/` tiene la landing real (`strental.com.pe`) capturada en
orden de scroll, como evidencia visual del diseño.
