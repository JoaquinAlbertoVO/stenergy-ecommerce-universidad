<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Carga el catálogo desde sample-data/productos-por-integrante/*.csv
 *
 * - Un archivo por integrante (cada uno con su propio commit en GitHub).
 * - Es idempotente: si el SKU ya existe se actualiza, no se duplica.
 * - Todo producto con cantidad en "Stock" queda con control de inventario activado.
 * - Si existe sample-data/imagenes/<SKU>.jpg|png|webp se usa como imagen destacada.
 */
class STS_Seeder {

	const META_CONTRIBUTOR = '_sts_aportado_por';
	const META_SOURCE      = '_sts_archivo_csv';

	public static function import_all() {
		$report = array(
			'files'    => 0,
			'created'  => 0,
			'updated'  => 0,
			'skipped'  => 0,
			'warnings' => array(),
			'time'     => current_time( 'mysql' ),
		);

		$dir   = STS_DATA_DIR . 'productos-por-integrante/';
		$files = glob( $dir . '*.csv' );
		if ( ! $files ) {
			$report['warnings'][] = 'No se encontraron archivos CSV en sample-data/productos-por-integrante/.';
			return $report;
		}
		sort( $files );

		$team = self::team_map();
		foreach ( $files as $file ) {
			$base = basename( $file );
			if ( 0 === strpos( $base, '_' ) ) {
				continue; // _plantilla.csv
			}
			$report['files']++;
			$who = isset( $team[ $base ]['nombre'] ) ? $team[ $base ]['nombre'] : ucwords( str_replace( array( '-', '_' ), ' ', basename( $base, '.csv' ) ) );
			self::import_file( $file, $who, $report );
		}

		self::sync_counts();
		return $report;
	}

	public static function team_map() {
		$json = STS_DATA_DIR . 'productos-por-integrante/_integrantes.json';
		if ( ! file_exists( $json ) ) {
			return array();
		}
		$data = json_decode( (string) file_get_contents( $json ), true ); // phpcs:ignore
		return is_array( $data ) ? $data : array();
	}

	private static function import_file( $file, $who, &$report ) {
		$rows = self::read_csv( $file );
		$base = basename( $file );
		if ( ! $rows ) {
			$report['warnings'][] = "$base: archivo vacío o sin filas válidas.";
			return;
		}

		foreach ( $rows as $i => $row ) {
			$line = $i + 2;
			$name = trim( (string) self::val( $row, 'name' ) );
			$sku  = trim( (string) self::val( $row, 'sku' ) );
			$type = strtolower( trim( (string) self::val( $row, 'type' ) ) );

			if ( '' === $name || 0 === strpos( $name, 'Nombre del producto' ) ) {
				$report['skipped']++;
				continue; // fila vacía o sin completar de la plantilla
			}
			if ( '' === $sku ) {
				$report['warnings'][] = "$base (línea $line): \"$name\" no tiene SKU; se omitió.";
				$report['skipped']++;
				continue;
			}
			if ( '' !== $type && 'simple' !== $type ) {
				$report['warnings'][] = "$base (línea $line): solo se admiten productos de tipo simple; se omitió $sku.";
				$report['skipped']++;
				continue;
			}

			$price = self::parse_price( self::val( $row, 'regular price' ) );
			if ( null === $price ) {
				$report['warnings'][] = "$base (línea $line): $sku no tiene un precio válido; se omitió.";
				$report['skipped']++;
				continue;
			}

			$id      = wc_get_product_id_by_sku( $sku );
			$product = $id ? wc_get_product( $id ) : new WC_Product_Simple();
			if ( ! $product ) {
				continue;
			}
			$is_new = ! $id;

			$product->set_name( $name );
			$product->set_sku( $sku );
			$product->set_status( self::truthy( self::val( $row, 'published', '1' ) ) ? 'publish' : 'draft' );
			$vis = strtolower( trim( (string) self::val( $row, 'visibility in catalog', 'visible' ) ) );
			$product->set_catalog_visibility( in_array( $vis, array( 'visible', 'catalog', 'search', 'hidden' ), true ) ? $vis : 'visible' );
			$product->set_featured( self::truthy( self::val( $row, 'is featured', '0' ) ) );
			$product->set_short_description( wp_kses_post( (string) self::val( $row, 'short description' ) ) );
			$product->set_description( wp_kses_post( (string) self::val( $row, 'description' ) ) );
			$product->set_regular_price( (string) $price );

			$sale = self::parse_price( self::val( $row, 'sale price' ) );
			$product->set_sale_price( null !== $sale && $sale < $price ? (string) $sale : '' );

			// Inventario: si trae cantidad, se controla el stock.
			$qty_raw = trim( (string) self::val( $row, 'stock' ) );
			if ( '' !== $qty_raw && is_numeric( $qty_raw ) ) {
				$qty = max( 0, (int) $qty_raw );
				$product->set_manage_stock( true );
				$product->set_stock_quantity( $qty );
				$product->set_backorders( 'no' );
				$low = trim( (string) self::val( $row, 'low stock amount' ) );
				$product->set_low_stock_amount( '' !== $low && is_numeric( $low ) ? (int) $low : 5 );
				$product->set_stock_status( $qty > 0 ? 'instock' : 'outofstock' );
			} else {
				$product->set_manage_stock( false );
				$in_stock = self::truthy( self::val( $row, 'in stock', '1' ) );
				$product->set_stock_status( $in_stock ? 'instock' : 'outofstock' );
				$report['warnings'][] = "$base (línea $line): $sku no tiene cantidad en \"Stock\"; no se controlará su inventario.";
			}

			$product->set_category_ids( self::term_ids( (string) self::val( $row, 'categories' ), 'product_cat' ) );
			$product->set_tag_ids( self::term_ids( (string) self::val( $row, 'tags' ), 'product_tag' ) );

			$product_id = $product->save();
			update_post_meta( $product_id, self::META_CONTRIBUTOR, $who );
			update_post_meta( $product_id, self::META_SOURCE, $base );

			self::maybe_attach_image( $product_id, $sku, (string) self::val( $row, 'images' ) );

			if ( $is_new ) {
				$report['created']++;
			} else {
				$report['updated']++;
			}
		}
	}

	/**
	 * Lee un CSV tolerando separador coma/punto y coma (Excel en español) y
	 * codificación UTF-8 o Windows-1252. Devuelve filas asociativas con cabeceras normalizadas.
	 */
	private static function read_csv( $file ) {
		$content = (string) file_get_contents( $file ); // phpcs:ignore
		$content = preg_replace( '/^\xEF\xBB\xBF/', '', $content );
		if ( ! mb_check_encoding( $content, 'UTF-8' ) ) {
			$content = mb_convert_encoding( $content, 'UTF-8', 'Windows-1252' );
		}
		$first = strtok( $content, "\n" );
		$delim = substr_count( (string) $first, ';' ) > substr_count( (string) $first, ',' ) ? ';' : ',';

		$fh = fopen( 'php://temp', 'r+' );
		fwrite( $fh, $content );
		rewind( $fh );

		$header = fgetcsv( $fh, 0, $delim );
		if ( ! $header ) {
			fclose( $fh );
			return array();
		}
		$keys = array_map( array( __CLASS__, 'normalize_header' ), $header );

		$rows = array();
		while ( ( $cols = fgetcsv( $fh, 0, $delim ) ) !== false ) {
			if ( 1 === count( $cols ) && ( null === $cols[0] || '' === trim( $cols[0] ) ) ) {
				continue;
			}
			$row = array();
			foreach ( $keys as $n => $key ) {
				$row[ $key ] = isset( $cols[ $n ] ) ? trim( $cols[ $n ] ) : '';
			}
			$rows[] = $row;
		}
		fclose( $fh );
		return $rows;
	}

	private static function normalize_header( $h ) {
		$h = strtolower( remove_accents( trim( (string) $h ) ) );
		$h = trim( preg_replace( '/[^a-z0-9 ]+/', ' ', $h ) );
		$h = preg_replace( '/\s+/', ' ', $h );
		$aliases = array(
			'nombre'            => 'name',
			'producto'          => 'name',
			'codigo'            => 'sku',
			'precio'            => 'regular price',
			'price'             => 'regular price',
			'precio regular'    => 'regular price',
			'precio oferta'     => 'sale price',
			'cantidad'          => 'stock',
			'categoria'         => 'categories',
			'categorias'        => 'categories',
			'etiquetas'         => 'tags',
			'descripcion'       => 'description',
			'descripcion corta' => 'short description',
			'destacado'         => 'is featured',
			'imagenes'          => 'images',
		);
		return isset( $aliases[ $h ] ) ? $aliases[ $h ] : $h;
	}

	private static function val( $row, $key, $default = '' ) {
		return isset( $row[ $key ] ) && '' !== $row[ $key ] ? $row[ $key ] : $default;
	}

	private static function truthy( $v ) {
		return in_array( strtolower( trim( (string) $v ) ), array( '1', 'yes', 'si', 'sí', 'true', 'verdadero' ), true );
	}

	/**
	 * "S/ 1,850.00" -> 1850.00 | "1.850,00" -> 1850.00 | "189,90" -> 189.90
	 */
	private static function parse_price( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return null;
		}
		$raw = preg_replace( '/[^0-9.,]/', '', $raw );
		if ( '' === $raw ) {
			return null;
		}
		$has_dot   = false !== strpos( $raw, '.' );
		$has_comma = false !== strpos( $raw, ',' );
		if ( $has_dot && $has_comma ) {
			if ( strrpos( $raw, ',' ) > strrpos( $raw, '.' ) ) {
				$raw = str_replace( '.', '', $raw );
				$raw = str_replace( ',', '.', $raw );
			} else {
				$raw = str_replace( ',', '', $raw );
			}
		} elseif ( $has_comma ) {
			$raw = preg_match( '/,\d{1,2}$/', $raw ) ? str_replace( ',', '.', $raw ) : str_replace( ',', '', $raw );
		}
		$n = (float) $raw;
		return $n > 0 ? round( $n, 2 ) : null;
	}

	/**
	 * Crea (si hace falta) y devuelve los IDs de términos. Admite jerarquía "Padre > Hijo".
	 */
	private static function term_ids( $list, $taxonomy ) {
		$ids = array();
		foreach ( preg_split( '/\s*,\s*/', $list, -1, PREG_SPLIT_NO_EMPTY ) as $item ) {
			$parent = 0;
			foreach ( preg_split( '/\s*>\s*/', $item, -1, PREG_SPLIT_NO_EMPTY ) as $label ) {
				$label = trim( $label );
				$term  = term_exists( $label, $taxonomy, $parent );
				if ( ! $term ) {
					$term = wp_insert_term( $label, $taxonomy, 'product_cat' === $taxonomy ? array( 'parent' => $parent ) : array() );
				}
				if ( is_wp_error( $term ) ) {
					if ( isset( $term->error_data['term_exists'] ) ) {
						$parent = (int) $term->error_data['term_exists'];
					}
					continue;
				}
				$parent = (int) ( is_array( $term ) ? $term['term_id'] : $term );
			}
			if ( $parent ) {
				$ids[] = $parent;
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Usa sample-data/imagenes/<SKU>.(jpg|jpeg|png|webp) como imagen destacada si existe.
	 */
	private static function maybe_attach_image( $product_id, $sku, $images_col ) {
		if ( has_post_thumbnail( $product_id ) ) {
			return;
		}
		$candidates = array();
		foreach ( array( 'jpg', 'jpeg', 'png', 'webp' ) as $ext ) {
			$candidates[] = STS_DATA_DIR . 'imagenes/' . $sku . '.' . $ext;
		}
		if ( $images_col && ! preg_match( '#^https?://#i', $images_col ) ) {
			array_unshift( $candidates, STS_DATA_DIR . 'imagenes/' . basename( $images_col ) );
		}
		foreach ( $candidates as $path ) {
			if ( ! file_exists( $path ) ) {
				continue;
			}
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$tmp = wp_tempnam( basename( $path ) );
			copy( $path, $tmp );
			$att = media_handle_sideload(
				array(
					'name'     => basename( $path ),
					'tmp_name' => $tmp,
				),
				$product_id
			);
			if ( ! is_wp_error( $att ) ) {
				set_post_thumbnail( $product_id, $att );
			} elseif ( file_exists( $tmp ) ) {
				wp_delete_file( $tmp );
			}
			return;
		}
	}

	private static function sync_counts() {
		delete_transient( 'wc_term_counts' );
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients( 0 );
		}
	}
}
