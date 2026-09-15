# Dependencias (plugins y tema)

No se versiona código de plugins de terceros en este repositorio — ni
gratuitos ni de pago. En su lugar, se documenta acá qué usa el sitio real
para que cualquiera pueda reproducir el entorno instalando lo mismo.

## Tema

- **Hello Elementor** (gratuito, oficial de Elementor) — tema base, casi
  todo el diseño lo arma Elementor por encima.

## Plugins usados en la landing (Entrega 1)

| Plugin | Tipo | Uso en la landing |
|---|---|---|
| Elementor | Gratuito | Maquetador de la página (widgets básicos) |
| **Elementor Pro** | **De pago** | Widgets `loop-carousel` y `woocommerce-products` (carruseles/grillas de productos) |
| WooCommerce | Gratuito | Fuente de los productos que muestran esos carruseles |

## Otros plugins del sitio real (no necesarios para la Entrega 1)

Akismet, All-in-One WP Migration, Code Snippets, Contact Form 7,
Essential Addons for Elementor, Google Listings & Ads, Yoast SEO,
WP Statistics, WP WhatsApp Chat, Ajax Search for WooCommerce.
Se irán sumando a la lista según lo requiera cada entrega siguiente
(catálogo, carrito, checkout).

## Nota sobre Elementor Pro

Es un plugin con licencia paga; redistribuir su código violaría los
términos de esa licencia. Quien vaya a levantar el sitio localmente para
desarrollar necesita su propia licencia (la empresa ya tiene una activa en
el sitio real). El **contenido** de las páginas (qué texto/imagen va en
cada widget) sí es propio y por eso se documenta en
`entrega-1-landing/homepage.sql`; lo que no se comparte es el motor que
interpreta ese contenido.
