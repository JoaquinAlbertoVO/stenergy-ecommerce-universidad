# Dependencias (plugins y tema)

Este repositorio **sí incluye** el código de los plugins y el tema
**gratuitos/GPL** usados por la landing (están en `wp-content/`). Lo único
que se excluye es lo que no se puede redistribuir por licencia.

## Tema

- **Hello Elementor** (gratuito, oficial de Elementor) — incluido en
  `wp-content/themes/hello-elementor`.

## Plugins incluidos en el repositorio (todos gratuitos/GPL)

| Plugin | Uso en la landing |
|---|---|
| Elementor | Maquetador de la página (widgets básicos) |
| WooCommerce | Catálogo de productos (fuente de los carruseles) |
| Essential Addons for Elementor | Widgets adicionales usados en la página |
| FiboSearch (Ajax Search for WooCommerce) | Buscador del header |
| Social Chat (WP WhatsApp Chat) | Botón flotante de WhatsApp |
| Code Snippets | Fragmentos de código personalizados del sitio |

## Plugin excluido por licencia

**Elementor Pro** — es un plugin de pago, su licencia no permite
redistribuir el código fuente. La landing real usa dos de sus widgets
(`loop-carousel` y `woocommerce-products`) para mostrar carruseles y
grillas de productos de forma dinámica. Sin Elementor Pro instalado,
el resto de la página funciona igual; solo esas dos secciones específicas
no se renderizan. Ver [`INSTALL.md`](../INSTALL.md) para más detalle.

## Otros plugins del sitio real (no incluidos, no necesarios para la Entrega 1)

Akismet, All-in-One WP Migration, Google Listings & Ads, Yoast SEO,
WP Statistics. Se evaluará sumarlos según lo requiera cada entrega
siguiente (catálogo, carrito, checkout).
