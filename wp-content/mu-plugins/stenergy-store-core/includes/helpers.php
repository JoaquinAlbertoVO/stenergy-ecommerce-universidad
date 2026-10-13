<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Datos de contacto de la tienda (editables en ST Store > Ajustes).
 * Todos son datos de ejemplo del proyecto universitario.
 */
function sts_contact_defaults() {
	return array(
		'whatsapp'       => '51999999999',
		'whatsapp_label' => '+51 999 999 999',
		'email'          => 'ventas@ststore.test',
		'phone'          => '(01) 555-0100',
		'address'        => 'Av. Ejemplo 123, Lima, Perú (dirección referencial)',
		'hours'          => 'Lunes a viernes 8:00 a 18:00 · Sábados 9:00 a 13:00',
	);
}

function sts_contact( $key = null ) {
	$data = wp_parse_args( (array) get_option( 'sts_contact', array() ), sts_contact_defaults() );
	if ( null === $key ) {
		return $data;
	}
	return isset( $data[ $key ] ) ? $data[ $key ] : '';
}

/**
 * URL de una página por su slug (funciona con cualquier tipo de enlace permanente).
 */
function sts_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

/**
 * Link de WhatsApp con mensaje inicial opcional.
 */
function sts_whatsapp_url( $message = '' ) {
	$url = 'https://wa.me/' . preg_replace( '/\D+/', '', sts_contact( 'whatsapp' ) );
	if ( $message ) {
		$url .= '?text=' . rawurlencode( $message );
	}
	return $url;
}

/**
 * Precio en texto plano (sin HTML), p. ej. "S/ 189.90".
 */
function sts_plain_price( $amount ) {
	if ( ! function_exists( 'wc_price' ) ) {
		return (string) $amount;
	}
	$text = html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	return trim( str_replace( "\xC2\xA0", ' ', $text ) ); // sin espacios "duros" (NBSP)
}

/**
 * Valida un RUC peruano (11 dígitos, prefijo válido y dígito verificador módulo 11).
 */
function sts_valid_ruc( $ruc ) {
	if ( ! preg_match( '/^\d{11}$/', $ruc ) ) {
		return false;
	}
	if ( ! in_array( substr( $ruc, 0, 2 ), array( '10', '15', '16', '17', '20' ), true ) ) {
		return false;
	}
	$weights = array( 5, 4, 3, 2, 7, 6, 5, 4, 3, 2 );
	$sum     = 0;
	for ( $i = 0; $i < 10; $i++ ) {
		$sum += (int) $ruc[ $i ] * $weights[ $i ];
	}
	$check = 11 - ( $sum % 11 );
	if ( 10 === $check ) {
		$check = 0;
	} elseif ( 11 === $check ) {
		$check = 1;
	}
	return (int) $ruc[10] === $check;
}
