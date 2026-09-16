# Cómo instalar este proyecto en tu XAMPP/localhost

Este repositorio contiene un proyecto WordPress completo: núcleo de
WordPress, tema y plugins (todos gratuitos/GPL). Falta un solo paso manual
por licencia: **Elementor Pro** (ver nota al final).

## 1. Clonar dentro de `htdocs`

```bash
cd C:/xampp/htdocs
git clone https://github.com/JoaquinAlbertoVO/stenergy-ecommerce-universidad.git
```

## 2. Crear la base de datos

En phpMyAdmin (`http://localhost/phpmyadmin`), crea una base de datos nueva,
por ejemplo `stenergy_ecommerce`, con collation `utf8mb4_unicode_ci`.

## 3. Configurar `wp-config.php`

Copia `wp-config-sample.php` a `wp-config.php` y completa:

```php
define( 'DB_NAME', 'stenergy_ecommerce' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
```

## 4. Instalar WordPress

Abre `http://localhost/stenergy-ecommerce-universidad/wp-admin/install.php`
y completa el instalador normal de WordPress (título del sitio, usuario y
contraseña de administrador).

## 5. Activar los plugins incluidos

Ya vienen en `wp-content/plugins/` — solo actívalos desde
**Plugins > Plugins instalados**:

- Elementor
- WooCommerce
- Essential Addons for Elementor
- FiboSearch (Ajax Search for WooCommerce)
- Social Chat (WhatsApp)
- Code Snippets

El tema **Hello Elementor** (en `wp-content/themes/`) actívalo desde
**Apariencia > Temas**.

## 6. Importar el contenido real de la página de inicio

`sample-data/homepage.sql` trae el contenido real (texto + estructura de
Elementor) de la landing de ST Rental. Las tablas del dump usan el prefijo
`SERVMASK_PREFIX_` (así viene del backup original). Para importarlo:

**Opción simple:** en `wp-config.php`, cambia la línea del prefijo a:

```php
$table_prefix = 'SERVMASK_PREFIX_';
```

y luego importa `sample-data/homepage.sql` en phpMyAdmin sobre esa misma
base de datos (agrega las tablas `..._posts` y `..._postmeta` con el
contenido real de la home).

## 7. Importar productos de ejemplo (con stock)

Ve a **WooCommerce > Productos > Importar** y sube
`sample-data/productos-woocommerce-ejemplo.csv`. Son 10 productos
ficticios de ingeniería eléctrica en modalidad de **venta con stock**
(no alquiler), listos para alimentar el catálogo.

## Nota sobre Elementor Pro

La landing real usa dos widgets de **Elementor Pro** (carrusel y grilla de
productos dinámicos). Elementor Pro es un plugin de pago — su licencia no
permite redistribuir el código, así que no está incluido aquí. Sin él, esas
dos secciones específicas no se renderizan; el resto de la página (hero,
textos, contadores, formulario, footer) funciona igual con el Elementor
gratuito incluido.
