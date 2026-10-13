<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Motor del chatbot (basado en reglas, sin IA externa).
 *
 * Detecta la intención del mensaje y responde con datos reales de WooCommerce:
 * stock y precio de cada producto, zonas de envío, pasarelas activas, cupones
 * vigentes y estado de un pedido (solo si coincide número de pedido + correo).
 */
class STS_Chatbot_Engine {

	public static function intent_labels() {
		return array(
			'greeting'     => 'Saludo',
			'thanks'       => 'Agradecimiento',
			'bye'          => 'Despedida',
			'human'        => 'Hablar con un asesor',
			'hours'        => 'Horario y ubicación',
			'invoice'      => 'Boleta / factura / RUC',
			'shipping'     => 'Envíos',
			'payment'      => 'Medios de pago',
			'warranty'     => 'Garantía y devoluciones',
			'coupon'       => 'Cupones y ofertas',
			'how_to_buy'   => 'Cómo comprar',
			'order_status' => 'Estado de pedido',
			'categories'   => 'Categorías',
			'recommend'    => 'Recomendaciones',
			'bulk'         => 'Compras por volumen',
			'help'         => 'Ayuda',
			'product'      => 'Producto (stock / precio)',
			'ask_product'  => 'Consulta de stock sin producto',
			'fallback'     => 'No entendido',
		);
	}

	/* ------------------------------------------------------------------ */

	public function reply( $raw, $context = array() ) {
		$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $raw ) ) );
		$text = mb_substr( $text, 0, 300 );
		$norm = $this->normalize( $text );

		if ( '' === $norm ) {
			return $this->out( 'fallback', array( $this->text( 'Escribe tu consulta y te ayudo. Por ejemplo: «¿hay stock de multímetros?»' ), $this->chips( $this->main_chips() ) ) );
		}

		// Flujo de consulta de pedido (pide número + correo).
		$awaiting = isset( $context['await'] ) && 'order' === $context['await'];
		if ( $awaiting || $this->looks_like_order_lookup( $text ) ) {
			return $this->order_flow( $text, $context );
		}

		$words = count( explode( ' ', $norm ) );

		if ( $words <= 4 && preg_match( '/\b(hola|holi|buenas|buenos dias|buen dia|buenas tardes|buenas noches|hey|ola|saludos)\b/', $norm ) ) {
			return $this->out( 'greeting', array( $this->text( '¡Hola! ¿En qué te puedo ayudar?' ), $this->chips( $this->main_chips() ) ) );
		}
		if ( $words <= 6 && preg_match( '/\b(gracias|muchas gracias|thanks|te agradezco)\b/', $norm ) ) {
			return $this->out( 'thanks', array( $this->text( '¡Con gusto! Si necesitas algo más, aquí estoy.' ) ) );
		}
		if ( $words <= 4 && preg_match( '/\b(chau|chao|adios|hasta luego|nos vemos|bye)\b/', $norm ) ) {
			return $this->out( 'bye', array( $this->text( '¡Hasta pronto! Que tengas un buen día.' ) ) );
		}

		$rules = array(
			'human'        => '/\b(asesor|asesora|humano|persona real|ejecutivo|vendedor|whatsapp|llamar|llamada|telefono|celular|contactar|contacto|hablar con)\b/',
			'hours'        => '/\b(horario|horarios|hora de atencion|atienden|atencion al cliente|abren|cierran|a que hora|donde estan|ubicacion|direccion|tienda fisica|local)\b/',
			'invoice'      => '/\b(factura|facturas|boleta|boletas|ruc|comprobante|comprobantes|razon social|igv)\b/',
			'shipping'     => '/\b(envio|envios|delivery|despacho|despachan|entrega|entregan|llega|llegan|cuanto demora|demora|courier|provincia|provincias|recojo|recoger|enviar|enviarlo)\b/',
			'payment'      => '/\b(pago|pagos|pagar|tarjeta|tarjetas|yape|plin|transferencia|contra entrega|contraentrega|efectivo|medios de pago|formas de pago|deposito|visa|mastercard)\b/',
			'warranty'     => '/\b(garantia|garantias|devolucion|devoluciones|devolver|cambio|cambios|reembolso|defectuoso|falla|fallado|malogrado)\b/',
			'coupon'       => '/\b(cupon|cupones|descuento|descuentos|promocion|promociones|promo|oferta|ofertas|rebaja)\b/',
			'how_to_buy'   => '/\b(como compro|como comprar|como hago mi pedido|como hago una compra|como realizo|pasos para comprar|proceso de compra|como funciona la compra)\b/',
			'order_status' => '/\b(pedido|pedidos|mi orden|seguimiento|rastrear|rastreo|donde esta mi|estado de mi compra)\b/',
			'categories'   => '/\b(categoria|categorias|catalogo|que venden|que productos|que tienen|que ofrecen|que lineas|lineas de productos)\b/',
			'recommend'    => '/\b(recomiend\w*|recomend\w*|mas vendid\w*|destacad\w*|popular\w*|sugerencia\w*|sugieres)\b/',
			'bulk'         => '/\b(mayorista|por mayor|volumen|cotizacion|cotizar|constructora|licitacion|obra grande|compra corporativa)\b/',
			'help'         => '/\b(ayuda|opciones|menu|que puedes hacer|que haces|como funcionas)\b/',
		);
		foreach ( $rules as $intent => $regex ) {
			if ( preg_match( $regex, $norm ) ) {
				return $this->{'answer_' . $intent}( $norm );
			}
		}

		// Producto: busca por nombre, SKU, categoría o etiqueta.
		$found = $this->search_products( $norm );
		if ( $found ) {
			return $this->answer_product( $found, $text );
		}

		$asks_stock = preg_match( '/\b(stock|disponible|disponibles|disponibilidad|tienen|tiene|hay|precio|precios|cuesta|cuestan|cuanto|vale)\b/', $norm );
		if ( $asks_stock && ! $this->query_tokens( $norm ) ) {
			return $this->out(
				'ask_product',
				array(
					$this->text( '¿De qué producto quieres saber? Escribe su nombre, marca o SKU (por ejemplo «multímetro Fluke» o «UPS 10kVA») y te digo cuántas unidades hay y su precio.' ),
					$this->chips( array( array( 'label' => 'Ver categorías', 'message' => '¿Qué categorías tienen?' ) ) ),
				)
			);
		}
		if ( $asks_stock || $this->query_tokens( $norm ) ) {
			return $this->out(
				'fallback',
				array(
					$this->text( 'No encontré productos que coincidan con «' . $text . '». Prueba con otra palabra (por ejemplo el nombre o la marca) o mira las categorías disponibles.' ),
					$this->chips( $this->main_chips() ),
				),
				array(),
				$text
			);
		}
		return $this->out( 'fallback', array( $this->text( 'No estoy seguro de haber entendido. Puedo ayudarte con stock y precios, envíos, medios de pago, comprobantes o el estado de un pedido.' ), $this->chips( $this->main_chips() ) ), array(), $text );
	}

	/* ------------------------------------------------------------------ */
	/* Respuestas por intención                                            */
	/* ------------------------------------------------------------------ */

	private function answer_human() {
		$c = sts_contact();
		return $this->out(
			'human',
			array(
				$this->text( 'Con gusto te atiende un asesor. WhatsApp: ' . $c['whatsapp_label'] . ' · Correo: ' . $c['email'] . ' · Teléfono: ' . $c['phone'] . '. Horario: ' . $c['hours'] . '.' ),
				$this->links(
					array(
						array( 'label' => 'Abrir WhatsApp', 'url' => sts_whatsapp_url( 'Hola, necesito ayuda con una compra en ST Store.' ) ),
						array( 'label' => 'Página de contacto', 'url' => sts_page_url( 'contacto' ) ),
					)
				),
			)
		);
	}

	private function answer_hours() {
		$c = sts_contact();
		return $this->out( 'hours', array( $this->text( 'Horario de atención: ' . $c['hours'] . '. Dirección: ' . $c['address'] . '. Las compras en la tienda web están disponibles las 24 horas.' ) ) );
	}

	private function answer_invoice() {
		return $this->out(
			'invoice',
			array(
				$this->text( 'En el checkout eliges boleta (con DNI de 8 dígitos) o factura (con RUC de 11 dígitos y razón social). Validamos el RUC antes de cobrar y todos los precios incluyen IGV. Para compras de tu empresa por volumen podemos preparar una cotización.' ),
				$this->chips( array( array( 'label' => 'Cotizar por volumen', 'message' => 'Quiero cotizar una compra por volumen' ) ) ),
			)
		);
	}

	private function answer_shipping() {
		$lines = array();
		foreach ( WC_Shipping_Zones::get_zones() as $zone ) {
			$parts = array();
			foreach ( $zone['shipping_methods'] as $method ) {
				if ( 'yes' !== $method->enabled ) {
					continue;
				}
				$cost = $method->get_option( 'cost' );
				if ( 'free_shipping' === $method->id ) {
					$parts[] = $method->get_title();
				} elseif ( '' !== (string) $cost && is_numeric( $cost ) ) {
					if ( (float) $cost > 0 ) {
						$parts[] = $method->get_title() . ': ' . sts_plain_price( (float) $cost );
					} else {
						$parts[] = $method->get_title() . ( false === stripos( $method->get_title(), 'gratis' ) ? ': gratis' : '' );
					}
				} else {
					$parts[] = $method->get_title();
				}
			}
			if ( $parts ) {
				$lines[] = '• ' . $zone['zone_name'] . ' — ' . implode( ' · ', $parts );
			}
		}
		$text = $lines ? "Estos son nuestros envíos:\n" . implode( "\n", $lines ) : 'Coordinamos el envío al finalizar tu compra.';
		return $this->out(
			'shipping',
			array(
				$this->text( $text . "\nCuando tu pedido sale de almacén te enviamos el número de seguimiento por correo." ),
				$this->links( array( array( 'label' => 'Envíos y devoluciones', 'url' => sts_page_url( 'envios-y-devoluciones' ) ) ) ),
			)
		);
	}

	private function answer_payment() {
		$names = array();
		foreach ( WC()->payment_gateways()->payment_gateways() as $gw ) {
			if ( 'yes' === $gw->enabled ) {
				$names[] = $gw->get_title();
			}
		}
		$text = $names ? 'Aceptamos: ' . implode( '; ', $names ) . '.' : 'Los medios de pago se muestran al finalizar la compra.';
		return $this->out( 'payment', array( $this->text( $text . ' El pago contra entrega aplica solo para Lima y Callao. Los pagos por Yape/Plin y transferencia se validan manualmente antes de despachar.' ) ) );
	}

	private function answer_warranty() {
		return $this->out(
			'warranty',
			array(
				$this->text( 'Puedes solicitar el cambio de un producto sin uso y en su empaque original dentro de 7 días calendario. Los equipos tienen garantía de fábrica de 12 meses (la cobertura depende de cada marca). Si recibes un producto con falla, escríbenos con tu número de pedido.' ),
				$this->links( array( array( 'label' => 'Envíos y devoluciones', 'url' => sts_page_url( 'envios-y-devoluciones' ) ) ) ),
			)
		);
	}

	private function answer_coupon() {
		$coupons = get_posts(
			array(
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'posts_per_page' => 3,
			)
		);
		$lines = array();
		foreach ( $coupons as $post ) {
			$c = new WC_Coupon( $post->ID );
			if ( $c->get_date_expires() && $c->get_date_expires()->getTimestamp() < time() ) {
				continue;
			}
			$lines[] = '• ' . strtoupper( $c->get_code() ) . ( $post->post_excerpt ? ': ' . $post->post_excerpt : '' );
		}
		$text = $lines ? "Cupones vigentes:\n" . implode( "\n", $lines ) . "\nIngresa el código en el carrito o en el checkout." : 'Por ahora no tenemos cupones activos. Revisa la tienda: puede haber productos en oferta.';
		return $this->out( 'coupon', array( $this->text( $text ) ) );
	}

	private function answer_how_to_buy() {
		return $this->out(
			'how_to_buy',
			array(
				$this->text( "Comprar es simple:\n1) Elige tu producto y revisa cuántas unidades hay.\n2) Agrégalo al carrito y ajusta cantidades (puedes usar un cupón).\n3) En el checkout elige boleta o factura, tu dirección y el medio de pago.\n4) Confirma: el stock se descuenta y recibes el correo de confirmación." ),
				$this->links(
					array(
						array( 'label' => 'Ir a la tienda', 'url' => wc_get_page_permalink( 'shop' ) ),
						array( 'label' => 'Ver mi carrito', 'url' => wc_get_cart_url() ),
					)
				),
			)
		);
	}

	private function answer_order_status() {
		return $this->out(
			'order_status',
			array( $this->text( 'Para revisar tu pedido necesito el número de pedido y el correo con el que compraste. Escríbelos juntos, por ejemplo: 1234 micorreo@ejemplo.com' ) ),
			array( 'await' => 'order' )
		);
	}

	private function answer_categories() {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
			)
		);
		if ( is_wp_error( $terms ) || ! $terms ) {
			return $this->out( 'categories', array( $this->text( 'Aún no hay productos publicados en la tienda.' ) ) );
		}
		$lines = array();
		$chips = array();
		foreach ( $terms as $t ) {
			$lines[] = '• ' . $t->name . ' (' . (int) $t->count . ')';
			if ( count( $chips ) < 5 ) {
				$chips[] = array( 'label' => $t->name, 'message' => 'productos de ' . $t->name );
			}
		}
		return $this->out( 'categories', array( $this->text( "Tenemos estas categorías:\n" . implode( "\n", $lines ) ), $this->chips( $chips ) ) );
	}

	private function answer_recommend() {
		$products = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => 4,
				'featured' => true,
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);
		if ( ! $products ) {
			$products = wc_get_products(
				array(
					'status'       => 'publish',
					'limit'        => 4,
					'stock_status' => 'instock',
					'orderby'      => 'popularity',
					'order'        => 'DESC',
				)
			);
		}
		if ( ! $products ) {
			return $this->out( 'recommend', array( $this->text( 'Aún no hay productos publicados.' ) ) );
		}
		return $this->out( 'recommend', array( $this->text( 'Te recomiendo estos productos disponibles:' ), $this->product_cards( $products ) ) );
	}

	private function answer_bulk() {
		return $this->out(
			'bulk',
			array(
				$this->text( 'Para compras por volumen o de empresa te preparamos una cotización con factura (RUC). Cuéntanos qué productos y cantidades necesitas.' ),
				$this->links( array( array( 'label' => 'Cotizar por WhatsApp', 'url' => sts_whatsapp_url( 'Hola, quiero cotizar una compra por volumen para mi empresa.' ) ) ) ),
			)
		);
	}

	private function answer_help() {
		return $this->out( 'help', array( $this->text( 'Puedo ayudarte con: stock y precio de un producto, categorías, envíos, medios de pago, boleta/factura, cupones, garantía y el estado de tu pedido. También puedo derivarte con un asesor.' ), $this->chips( $this->main_chips() ) ) );
	}

	private function answer_product( $products, $original ) {
		$count = count( $products );
		if ( 1 === $count ) {
			$p = $products[0];
			list( $stock_text, $level ) = $this->stock_info( $p );
			$text = sprintf( '%s — %s. Precio: %s (IGV incluido).', $p->get_name(), $stock_text, sts_plain_price( (float) $p->get_price() ) );
			if ( 'out' === $level ) {
				$text .= ' Escríbenos y te avisamos cuando lo repongamos.';
			}
			$msgs = array( $this->text( $text ), $this->product_cards( $products ) );
		} else {
			$msgs = array( $this->text( 'Encontré ' . $count . ' productos que coinciden:' ), $this->product_cards( $products ) );
		}
		$msgs[] = $this->links( array( array( 'label' => 'Ver más en la tienda', 'url' => add_query_arg( array( 's' => $original, 'post_type' => 'product' ), home_url( '/' ) ) ) ) );
		return $this->out( 'product', $msgs );
	}

	/* ------------------------------------------------------------------ */
	/* Estado de pedido                                                    */
	/* ------------------------------------------------------------------ */

	private function looks_like_order_lookup( $text ) {
		return (bool) preg_match( '/[\w.+-]+@[\w-]+\.[\w.-]+/', $text ) && (bool) preg_match( '/\b\d{1,9}\b/', $text );
	}

	private function order_flow( $text, $context ) {
		preg_match( '/[\w.+-]+@[\w-]+\.[\w.-]+/', $text, $m );
		$email = isset( $m[0] ) ? strtolower( $m[0] ) : ( isset( $context['email'] ) ? sanitize_email( $context['email'] ) : '' );
		$rest  = preg_replace( '/[\w.+-]+@[\w-]+\.[\w.-]+/', ' ', $text );
		preg_match( '/\b(\d{1,9})\b/', $rest, $n );
		$number = isset( $n[1] ) ? $n[1] : ( isset( $context['order'] ) ? preg_replace( '/\D+/', '', (string) $context['order'] ) : '' );

		$ctx = array( 'await' => 'order' );
		if ( $number ) {
			$ctx['order'] = $number;
		}
		if ( $email ) {
			$ctx['email'] = $email;
		}

		if ( ! $number && ! $email ) {
			return $this->out( 'order_status', array( $this->text( 'Necesito el número de pedido y el correo de la compra. Escríbelos juntos, por ejemplo: 1234 micorreo@ejemplo.com' ) ), $ctx );
		}
		if ( ! $number ) {
			return $this->out( 'order_status', array( $this->text( 'Gracias. Ahora dime el número de pedido (solo los dígitos).' ) ), $ctx );
		}
		if ( ! $email || ! is_email( $email ) ) {
			return $this->out( 'order_status', array( $this->text( 'Gracias. Ahora escribe el correo con el que hiciste la compra.' ) ), $ctx );
		}

		// Límite de intentos para evitar adivinar pedidos.
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
		$key = 'sts_bot_ord_' . md5( $ip );
		$try = (int) get_transient( $key );
		if ( $try >= 8 ) {
			return $this->out( 'order_status', array( $this->text( 'Hiciste varias consultas seguidas. Inténtalo de nuevo en unos minutos o escríbenos por WhatsApp.' ) ) );
		}
		set_transient( $key, $try + 1, 15 * MINUTE_IN_SECONDS );

		$order = wc_get_order( (int) $number );
		if ( ! $order || strtolower( trim( $order->get_billing_email() ) ) !== $email ) {
			return $this->out( 'order_status', array( $this->text( 'No encontré un pedido con esos datos. Revisa el número y el correo, o escríbenos para ayudarte.' ), $this->chips( array( array( 'label' => 'Hablar con un asesor', 'message' => 'Quiero hablar con un asesor' ) ) ) ) );
		}

		$lines = array(
			'Pedido #' . $order->get_order_number() . ': ' . wc_get_order_status_name( $order->get_status() ) . '.',
			'Total: ' . sts_plain_price( (float) $order->get_total() ) . ' · ' . $order->get_item_count() . ' artículo(s).',
		);
		if ( $order->get_meta( '_sts_tracking' ) ) {
			$lines[] = 'Seguimiento: ' . trim( $order->get_meta( '_sts_carrier' ) . ' ' . $order->get_meta( '_sts_tracking' ) );
		}
		if ( in_array( $order->get_status(), array( 'pending', 'on-hold' ), true ) ) {
			$lines[] = 'Estamos validando tu pago; te avisaremos por correo.';
		}
		return $this->out(
			'order_status',
			array(
				$this->text( implode( "\n", $lines ) ),
				$this->links( array( array( 'label' => 'Ver detalle del pedido', 'url' => $order->get_checkout_order_received_url() ) ) ),
			)
		);
	}

	/* ------------------------------------------------------------------ */
	/* Búsqueda de productos                                               */
	/* ------------------------------------------------------------------ */

	private function stopwords() {
		return array(
			'hay', 'tienen', 'tiene', 'tienes', 'stock', 'disponible', 'disponibles', 'disponibilidad', 'precio', 'precios', 'cuesta', 'cuestan', 'cuanto', 'cuantos',
			'cual', 'cuales', 'que', 'del', 'los', 'las', 'una', 'unos', 'unas', 'por', 'para', 'con', 'sin', 'mis', 'tus', 'sus', 'quiero', 'quisiera', 'necesito',
			'busco', 'buscando', 'saber', 'consultar', 'ver', 'muestrame', 'muestra', 'dame', 'venden', 'vende', 'comprar', 'compro', 'unidades', 'unidad', 'favor',
			'hola', 'buenas', 'tengo', 'algun', 'alguna', 'algo', 'existe', 'esta', 'estan', 'queda', 'quedan', 'cuando', 'donde', 'como', 'costo', 'vale', 'valor',
			'soles', 'producto', 'productos', 'equipo', 'equipos', 'informacion', 'info', 'sobre', 'acerca', 'estoy', 'interesado', 'interesada',
		);
	}

	private function query_tokens( $norm ) {
		$stop   = $this->stopwords();
		$tokens = array();
		foreach ( explode( ' ', $norm ) as $t ) {
			if ( strlen( $t ) >= 3 && ! in_array( $t, $stop, true ) && ! ctype_digit( $t ) ) {
				$tokens[] = $t;
			}
		}
		return array_values( array_unique( $tokens ) );
	}

	private function singular( $t ) {
		if ( strlen( $t ) > 5 && substr( $t, -2 ) === 'es' ) {
			return substr( $t, 0, -2 );
		}
		if ( strlen( $t ) > 3 && substr( $t, -1 ) === 's' ) {
			return substr( $t, 0, -1 );
		}
		return $t;
	}

	/**
	 * Coincidencia en PHP (independiente de la collation de MySQL): nombre > SKU > categoría/etiquetas > descripción.
	 */
	private function search_products( $norm ) {
		$tokens = $this->query_tokens( $norm );
		if ( ! $tokens ) {
			return array();
		}
		$products = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => 400,
			)
		);

		$scored = array();
		foreach ( $products as $p ) {
			$name = $this->normalize( $p->get_name() );
			$sku  = $this->normalize( $p->get_sku() );
			$meta = $this->normalize( implode( ' ', wp_get_post_terms( $p->get_id(), array( 'product_cat', 'product_tag' ), array( 'fields' => 'names' ) ) ) );
			$desc = $this->normalize( $p->get_short_description() . ' ' . $p->get_description() );

			$score   = 0;
			$matched = 0;
			foreach ( $tokens as $t ) {
				$best = 0;
				foreach ( array_unique( array( $t, $this->singular( $t ) ) ) as $v ) {
					if ( false !== strpos( $name, $v ) ) {
						$best = max( $best, 4 );
					} elseif ( '' !== $sku && false !== strpos( $sku, $v ) ) {
						$best = max( $best, 3 );
					} elseif ( false !== strpos( $meta, $v ) ) {
						$best = max( $best, 2 );
					} elseif ( false !== strpos( $desc, $v ) ) {
						$best = max( $best, 1 );
					}
				}
				if ( $best ) {
					$matched++;
					$score += $best;
				}
			}
			if ( $matched >= max( 1, (int) ceil( count( $tokens ) / 2 ) ) ) {
				$scored[] = array( $score + ( $p->is_in_stock() ? 0.5 : 0 ), $p, $matched );
			}
		}
		if ( ! $scored ) {
			return array();
		}
		// Solo los que coinciden con la mayor cantidad de palabras (así «detector de tensión» devuelve ese producto y no los que solo mencionan «tensión»).
		$top = max( array_column( $scored, 2 ) );
		$scored = array_values(
			array_filter(
				$scored,
				function ( $row ) use ( $top ) {
					return $row[2] === $top;
				}
			)
		);
		usort(
			$scored,
			function ( $a, $b ) {
				return $b[0] <=> $a[0];
			}
		);
		return array_map(
			function ( $row ) {
				return $row[1];
			},
			array_slice( $scored, 0, 4 )
		);
	}

	private function stock_info( $p ) {
		if ( $p->managing_stock() ) {
			$q = (int) $p->get_stock_quantity();
			if ( $q <= 0 && ! $p->backorders_allowed() ) {
				return array( 'agotado por ahora', 'out' );
			}
			$low = (int) ( $p->get_low_stock_amount() ?: get_option( 'woocommerce_notify_low_stock_amount', 5 ) );
			return $q <= $low ? array( 'últimas ' . $q . ' unidades', 'low' ) : array( $q . ' unidades en stock', 'ok' );
		}
		return $p->is_in_stock() ? array( 'disponible', 'ok' ) : array( 'agotado por ahora', 'out' );
	}

	/* ------------------------------------------------------------------ */
	/* Constructores de mensajes                                           */
	/* ------------------------------------------------------------------ */

	private function normalize( $s ) {
		$s = strtolower( remove_accents( (string) $s ) );
		$s = preg_replace( '/[^a-z0-9@._\-\s]/', ' ', $s );
		return trim( preg_replace( '/\s+/', ' ', $s ) );
	}

	private function main_chips() {
		return array(
			array( 'label' => 'Ver categorías', 'message' => '¿Qué categorías tienen?' ),
			array( 'label' => 'Envíos', 'message' => '¿Cómo son los envíos?' ),
			array( 'label' => 'Medios de pago', 'message' => '¿Qué medios de pago aceptan?' ),
			array( 'label' => 'Boleta o factura', 'message' => '¿Emiten factura con RUC?' ),
			array( 'label' => 'Estado de mi pedido', 'message' => 'Quiero ver el estado de mi pedido' ),
			array( 'label' => 'Hablar con un asesor', 'message' => 'Quiero hablar con un asesor' ),
		);
	}

	private function text( $t ) {
		return array( 'type' => 'text', 'text' => $t );
	}

	private function chips( $items ) {
		return array( 'type' => 'chips', 'items' => $items );
	}

	private function links( $items ) {
		return array(
			'type'  => 'links',
			'items' => array_map(
				function ( $i ) {
					return array( 'label' => $i['label'], 'url' => esc_url_raw( $i['url'] ) );
				},
				$items
			),
		);
	}

	private function product_cards( $products ) {
		$items = array();
		foreach ( $products as $p ) {
			list( $stock_text, $level ) = $this->stock_info( $p );
			$items[] = array(
				'name'    => $p->get_name(),
				'sku'     => $p->get_sku(),
				'price'   => sts_plain_price( (float) $p->get_price() ),
				'stock'   => ucfirst( $stock_text ),
				'level'   => $level,
				'url'     => esc_url_raw( get_permalink( $p->get_id() ) ),
				'add_url' => 'out' === $level ? '' : esc_url_raw( add_query_arg( 'add-to-cart', $p->get_id(), wc_get_cart_url() ) ),
				'image'   => $p->get_image_id() ? esc_url_raw( (string) wp_get_attachment_image_url( $p->get_image_id(), 'woocommerce_thumbnail' ) ) : '',
			);
		}
		return array( 'type' => 'products', 'items' => $items );
	}

	private function out( $intent, $messages, $context = array(), $logged = '' ) {
		return array(
			'intent'      => $intent,
			'messages'    => $messages,
			'context'     => $context,
			'logged_text' => $logged,
		);
	}
}
