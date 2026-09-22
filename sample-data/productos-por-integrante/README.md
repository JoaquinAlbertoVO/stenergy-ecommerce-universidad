# Productos por integrante (3 c/u)

El profesor pidió 3 productos por cada integrante del equipo (4 personas
= 12 productos), y que cada quien tenga su propio commit en GitHub.

## Cómo participar

1. **Cada integrante necesita una cuenta de GitHub** y acceso de
   colaborador a este repositorio (Joaquín los invita — les llega un
   correo/notificación para aceptar la invitación).

2. Clona el repositorio en tu PC:

   ```bash
   git clone https://github.com/JoaquinAlbertoVO/stenergy-ecommerce-universidad.git
   cd stenergy-ecommerce-universidad
   ```

3. Copia `_plantilla.csv` a un archivo con tu nombre, por ejemplo:

   ```bash
   cp sample-data/productos-por-integrante/_plantilla.csv sample-data/productos-por-integrante/juan-perez.csv
   ```

4. Completa tus **3 productos** en ese archivo (electrónica/eléctrica
   industrial, modalidad de **venta con stock**, no alquiler). Sigue el
   mismo formato de columnas — puedes abrir el CSV con Excel o Google
   Sheets si prefieres.

5. Configura tu identidad de Git (una sola vez, con tu propio nombre y
   correo, para que el commit quede a tu nombre):

   ```bash
   git config user.name "Tu Nombre"
   git config user.email "tu-correo@ejemplo.com"
   ```

6. Sube tu archivo con tu propio commit:

   ```bash
   git add sample-data/productos-por-integrante/tu-nombre.csv
   git commit -m "Agrega 3 productos: <categoría de tus productos>"
   git push origin main
   ```

Cada archivo queda separado por persona a propósito, para que el profesor
vea en GitHub (pestaña **Commits** o **Insights > Contributors**) quién
aportó qué.

## Categorías sugeridas (para no repetir productos entre integrantes)

- Equipos de medición (multímetros, cámaras termográficas, luxómetros...)
- Equipos de protección personal / seguridad eléctrica
- Herramientas industriales (roscadoras, compresoras, taladros...)
- Iluminación y energía (reflectores LED, paneles solares...)

Cada integrante puede tomar una categoría distinta, o mezclar — lo
importante es que sean 3 productos reales de ingeniería eléctrica,
con precio de venta y stock (no precio de alquiler).
