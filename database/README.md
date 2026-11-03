# Base de datos de ST Store

`stenergy_store.sql` es el volcado completo (MariaDB/MySQL) de la tienda tal como está en nuestro localhost:
productos con stock, páginas, configuración de WooCommerce, cupón, usuarios de prueba y los pedidos de prueba.

Todos los datos personales son ficticios (correos `@ststore.test`, `@demo.test`, `@example.com`).

## Importarla (opcional)

No es obligatorio: la tienda también se configura sola desde cero (ver [`INSTALL.md`](../INSTALL.md)).
Para tener exactamente nuestros datos:

1. Clonar el repo en `C:\xampp\htdocs\stenergy-store` (ese nombre de carpeta, porque la URL guardada es
   `http://localhost/stenergy-store`).
2. En `http://localhost/phpmyadmin` → *Importar* → elegir `database/stenergy_store.sql` → *Continuar*.
   El archivo crea la base `stenergy_store` por sí solo.
   Por consola: `C:\xampp\mysql\bin\mysql.exe -u root < database/stenergy_store.sql`
3. Abrir `http://localhost/stenergy-store/`.

Usuarios: administrador `profesor` y cliente `cliente.demo` (contraseña en `INSTALL.md`).

## Actualizar el volcado

```bash
C:\xampp\mysql\bin\mysqldump.exe -u root --single-transaction --default-character-set=utf8mb4 --databases stenergy_store > database/stenergy_store.sql
```

Antes de subirlo, revisar que no tenga correos, teléfonos, direcciones ni DNI reales (por ejemplo de compras de prueba).
