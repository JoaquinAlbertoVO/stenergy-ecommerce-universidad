# Entrega 1 — Contenido real de la landing

`homepage.sql` contiene, tal cual salieron del backup real de WordPress
(All-in-One WP Migration, 2026-09-15), las filas de:

- `wp_posts` (id 9) — el contenido y textos reales de la página de inicio.
- `wp_postmeta` — la data de Elementor que arma el diseño
  (`_elementor_data`), versión de Elementor, plantilla, título/meta SEO
  (Yoast) y el ID de la imagen destacada.

Nota: en el dump original la tabla se llama `SERVMASK_PREFIX_posts` /
`SERVMASK_PREFIX_postmeta` — es el prefijo genérico que usa All-in-One WP
Migration; al importar en un WordPress reemplázalo por el prefijo real
(normalmente `wp_`).

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
repositorio** (ver `../dependencias.md`). Para desarrollar/ver la página
funcionando de verdad hace falta una licencia propia de Elementor Pro
instalada localmente — eso se hace en el entorno local de cada uno, nunca
subiendo el plugin al repo.

## Qué falta para que esto sea "navegable"

Este archivo por sí solo no arma un sitio funcional: hace falta el tema
(Hello Elementor), los plugins de la lista de dependencias, y WooCommerce
con al menos algunos productos de ejemplo (con stock, no con precio de
alquiler). Para la Entrega 1 alcanza con mostrar este contenido +
capturas/demo de la página real como evidencia del avance.
