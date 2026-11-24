# Publicación en internet (cPanel + GitHub Actions)

La tienda se publica en el subdominio **`ststore.<dominio>`** de un hosting con cPanel.
Cada `git push` a `main` la actualiza sola con el workflow [`.github/workflows/deploy.yml`](../.github/workflows/deploy.yml),
que sube por FTPS solo los archivos que cambiaron.

## Puesta en marcha (una sola vez)

1. **Subdominio** — cPanel → *Dominios* → *Crear un nuevo dominio* → `ststore.<dominio>`.
   Anotar la carpeta raíz que propone (por ejemplo `ststore.<dominio>`).
2. **HTTPS** — cPanel → *SSL/TLS Status* → *Run AutoSSL* (si el subdominio no tiene certificado aún).
3. **Cuenta FTP** — cPanel → *Cuentas FTP* → usuario `deploy`, con **directorio = la carpeta del subdominio**.
   Así el workflow no puede tocar nada fuera de la tienda.
4. **Secretos en GitHub** — repo → *Settings* → *Secrets and variables* → *Actions* → *New repository secret*:
   `FTP_SERVER` (servidor FTP que muestra cPanel), `FTP_USERNAME` (usuario completo, `deploy@<dominio>`), `FTP_PASSWORD`.
5. **Primer envío** — repo → *Actions* → *Publicar en el hosting* → *Run workflow*.
   La primera vez sube WordPress completo (unos 10 000 archivos, puede tardar más de media hora).
6. **Base de datos** — cPanel → *Asistente de bases de datos MySQL*: crear base, usuario y darle *Todos los privilegios*.
7. **Instalar WordPress** — abrir `https://ststore.<dominio>`: el instalador pide los datos de la base del paso 6
   y crea el administrador. **Usar una contraseña nueva y fuerte**, no las de prueba que figuran en este repo.
8. **Configuración automática** — al abrir la tienda por primera vez se activan WooCommerce y el chatbot, se aplica
   el tema y se cargan los 16 productos con sus fotos (igual que en localhost).
9. **Redes sociales** — panel → *ST Store* → *Ajustes de contacto*: pegar las URL de TikTok, Instagram y Facebook.

## Qué no se publica

`database/`, `docs/`, `tools/`, `wp-config.php`, `.htaccess`, `wp-content/uploads/` y los plugins que la tienda no usa.
El `wp-config.php` del hosting lo crea el instalador y no se sobrescribe con los envíos.

## Seguridad de la demo

- Arriba de cada página hay una franja que avisa que es una tienda de demostración.
- La pasarela de tarjeta es simulada: solo acepta las tarjetas de prueba y no guarda números.
- Los correos no se envían: quedan en *ST Store* → *Correos simulados* (definir `STS_REAL_MAIL` en `wp-config.php` para enviarlos).
- La cuenta `cliente.demo` tiene una contraseña pública (está en `INSTALL.md`): solo sirve para probar compras.

## Medir las visitas que llegan desde TikTok

Poner en la biografía de TikTok (y en cada video) enlaces con parámetros UTM, por ejemplo:

```
https://ststore.<dominio>/?utm_source=tiktok&utm_medium=social&utm_campaign=perfil
https://ststore.<dominio>/producto/<producto>/?utm_source=tiktok&utm_medium=social&utm_campaign=video_<tema>
```

En GA4: *Informes* → *Adquisición* → *Adquisición de tráfico* → dimensión *Fuente / medio de la sesión* → `tiktok / social`.
Los botones *Compartir* de cada producto ya agregan sus propios UTM (`whatsapp`, `facebook`, `enlace_copiado`).
WooCommerce además guarda el origen de cada pedido (columna *Origen* en *Pedidos*) cuando el cliente aceptó las cookies de analítica.
