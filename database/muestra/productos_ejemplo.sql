-- Datos de EJEMPLO para el catálogo de la tienda universitaria.
-- No contiene proveedores, precios ni información real de ST Energy.

CREATE TABLE IF NOT EXISTS `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria` varchar(50) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `datos_tecnicos` text DEFAULT NULL,
  `precio_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `productos`
  (`categoria`, `nombre`, `descripcion`, `datos_tecnicos`, `precio_venta`, `stock`)
VALUES
  ('Medición', 'Multímetro digital de gancho', 'Multímetro de gancho para mediciones de corriente AC/DC.', 'Rango: 0-600A, CAT III 600V', 189.90, 25),
  ('Protección', 'Interruptor termomagnético 2x32A', 'Interruptor de protección para tableros eléctricos.', 'Curva C, 2 polos, 32A', 45.50, 60),
  ('Iluminación', 'Reflector LED industrial 100W', 'Reflector LED para exteriores e interiores industriales.', 'IP65, 100W, 6500K', 79.00, 40),
  ('EPP', 'Guantes dieléctricos Clase 0', 'Guantes de protección eléctrica hasta 1000V.', 'Clase 0, talla M/L', 65.00, 100);
