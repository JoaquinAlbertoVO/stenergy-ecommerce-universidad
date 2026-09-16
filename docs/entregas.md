# Plan de entregas

> Borrador inicial. Ajustar fechas y alcance según lo que pida el profesor.

## Entrega 1 — Landing page

**Objetivo:** mostrar la página de inicio de la tienda (hero, categorías
destacadas, propuesta de valor, contacto), adaptada de la landing real de
ST Energy pero orientada a venta en vez de alquiler.

**Contenido en el repo (avance actual):**
- ✅ `content/entrega-1-landing/homepage.sql` — contenido real de la página
  de inicio (texto, estructura de Elementor), extraído y revisado del
  backup del 2026-09-15.
- ✅ `content/dependencias.md` — qué tema y plugins usa la landing, y por
  qué Elementor Pro no se incluye como código (solo se documenta su uso).
- ✅ `content/entrega-1-landing/screenshots/` — capturas de la landing real
  en vivo, en orden de scroll.
- ✅ `content/entrega-1-landing/productos-woocommerce-ejemplo.csv` — 10
  productos ficticios en modalidad de venta con stock, listos para
  importar en WooCommerce y reemplazar los productos reales de alquiler
  que hoy alimentan los carruseles de la landing.

**Fuera de alcance:** catálogo completo, carrito, checkout, base de datos de
productos real.

## Entrega 2 — Catálogo de productos con stock

Pendiente de detallar: modelo de datos de producto (nombre, categoría,
descripción, precio de venta, stock disponible), listado y filtro por
categoría.

## Entrega 3 — Carrito de compras

Pendiente de detallar.

## Entrega 4 — Checkout / pasarela de pago

Pendiente de detallar.

## Entrega 5 — Panel de administración de pedidos e inventario

Pendiente de detallar.

## Cómo se genera el contenido de cada entrega (proceso interno)

La landing y el resto del sitio están armados con Elementor dentro de la base
de datos de WordPress, no como archivos de tema sueltos. Para traer ese
contenido a este repo:

1. En el WordPress real, ir a **Plugins > All-in-One WP Migration > Export** y
   exportar un backup completo (`.wpress`).
2. Extraer de ese backup solo el contenido de las páginas involucradas en la
   entrega actual (nunca el dump completo de la base de datos).
3. Revisar que no haya datos comerciales reales (precios, proveedores,
   contactos de clientes) antes de commitear.
