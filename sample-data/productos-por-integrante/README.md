# Productos por integrante (4 c/u)

El profesor pidió **4 productos por cada integrante** (4 personas = 16 productos) y que cada
quien tenga sus propios commits en GitHub. Cada integrante tiene **su propio archivo CSV**: esos
archivos son la fuente real del catálogo de la tienda (no es un ejemplo suelto).

| Archivo | Integrante |
|---|---|
| `joaquin-villalobos.csv` | Joaquín Villalobos |
| `maykel.csv` | Mayquel Bernaola |
| `jsolorzano.csv` | Jordy Solorzano |
| `alex.csv` | Alex Lahuanampa |

La meta es **4 filas por archivo**. El avance en vivo (cuántos productos aportó cada uno) se ve en la
página *Nosotros* de la tienda.

> El nombre del archivo debe coincidir con el que figura en [`_integrantes.json`](_integrantes.json);
> si usas otro nombre, cámbialo también ahí.

## Cómo agregar tus productos (sin instalar nada)

1. Entra a GitHub, a la carpeta `sample-data/productos-por-integrante/`.
2. Abre tu archivo (o `_plantilla.csv` si aún no tienes) → ícono del lápiz.
3. Agrega o corrige filas respetando las columnas. Un producto por fila.
4. **Commit changes** → "Commit directly to the main branch". Ese commit queda a tu nombre.

## Reglas del formato

| Columna | Qué poner |
|---|---|
| `Type` | siempre `simple` |
| `SKU` | único en toda la tienda. Usa un prefijo tuyo (`MAY-001`, `JSO-001`...) |
| `Name`, `Short description`, `Description` | texto en español, con datos técnicos reales |
| `Published` / `Is featured?` | `1` o `0` |
| `Stock` | **cantidad en unidades** (número entero). Con esto la tienda controla el inventario: baja al vender y se muestra "Agotado" en 0 |
| `Regular price` | precio de **venta** en soles, con IGV incluido (ej. `189.90`; también acepta `1,850.00`) |
| `Categories` | una o varias separadas por coma. Reutiliza los nombres existentes: *Equipos de medición*, *Equipos de seguridad (EPP)*, *Herramientas industriales*, *Iluminación y energía* |
| `Tags` | palabras clave separadas por coma (ayudan al buscador y al chatbot) |

Opcionales: `Sale price` (precio en oferta) y `Low stock amount` (umbral de "últimas unidades", por defecto 5).

Si guardas el CSV desde Excel en español, funciona igual (acepta `;` y la codificación de Windows).
**No es alquiler**: son productos de venta con stock y precio de venta.

## Imágenes (opcional)

Si un producto no tiene foto, la tienda genera una imagen referencial con su nombre y categoría.
Para poner la foto real, sube `sample-data/imagenes/<SKU>.jpg` (o `.png` / `.webp`), por ejemplo
`sample-data/imagenes/MAY-001.jpg`.

## Ver tus productos en la tienda local

```bash
git pull
```

Luego, en el panel de WordPress: **ST Store → Estado → "Re-sincronizar catálogo desde los CSV"**.
Actualiza por SKU: no duplica productos ni borra pedidos. Si algo está mal escrito (sin precio,
sin SKU...) ahí mismo aparece un aviso con el archivo y la línea.
