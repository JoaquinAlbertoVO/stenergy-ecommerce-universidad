<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conexión del chatbot con WordPress: endpoint REST, widget del front-end,
 * límite de uso y estadísticas anónimas de las consultas.
 */
class STS_Chatbot_Plugin {

	const STATS_OPTION = 'sts_chatbot_stats';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'container' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 20 );
	}

	public static function register_routes() {
		register_rest_route(
			'stenergy-chatbot/v1',
			'/message',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'message' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'context' => array(
						'type'     => 'object',
						'required' => false,
					),
				),
			)
		);
	}

	private static function rate_limited() {
		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0';
		$key = 'sts_bot_rl_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= 60 ) {
			return true;
		}
		set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );
		return false;
	}

	public static function handle( WP_REST_Request $request ) {
		if ( self::rate_limited() ) {
			return new WP_REST_Response(
				array(
					'messages' => array( array( 'type' => 'text', 'text' => 'Recibimos muchas consultas seguidas. Espera unos minutos o escríbenos por WhatsApp.' ) ),
					'context'  => new stdClass(),
				),
				429
			);
		}

		$context = $request->get_param( 'context' );
		$context = is_array( $context ) ? $context : array();

		$engine = new STS_Chatbot_Engine();
		$result = $engine->reply( (string) $request->get_param( 'message' ), $context );

		self::track( $result['intent'], $result['logged_text'] );

		return rest_ensure_response(
			array(
				'messages' => $result['messages'],
				'context'  => $result['context'] ? $result['context'] : new stdClass(),
			)
		);
	}

	/**
	 * Estadística anónima: solo el tipo de consulta; de las no entendidas se guarda el
	 * texto sin correos ni números largos.
	 */
	private static function track( $intent, $text ) {
		$stats = wp_parse_args(
			(array) get_option( self::STATS_OPTION, array() ),
			array(
				'total'      => 0,
				'intents'    => array(),
				'unanswered' => array(),
			)
		);
		$stats['total']++;
		$stats['intents'][ $intent ] = ( $stats['intents'][ $intent ] ?? 0 ) + 1;

		if ( 'fallback' === $intent && $text ) {
			$text = preg_replace( '/[\w.+-]+@[\w-]+\.[\w.-]+/', '[correo]', $text );
			$text = preg_replace( '/\d{4,}/', '[n.º]', $text );
			array_unshift( $stats['unanswered'], array( current_time( 'mysql' ), mb_substr( $text, 0, 160 ) ) );
			$stats['unanswered'] = array_slice( $stats['unanswered'], 0, 30 );
		}
		update_option( self::STATS_OPTION, $stats, false );
	}

	public static function assets() {
		wp_enqueue_style( 'sts-chatbot', STS_BOT_URL . 'assets/chatbot.css', array(), filemtime( STS_BOT_DIR . 'assets/chatbot.css' ) );
		wp_enqueue_script( 'sts-chatbot', STS_BOT_URL . 'assets/chatbot.js', array(), filemtime( STS_BOT_DIR . 'assets/chatbot.js' ), true );
		wp_localize_script(
			'sts-chatbot',
			'STS_CHATBOT',
			array(
				'endpoint' => esc_url_raw( rest_url( 'stenergy-chatbot/v1/message' ) ),
				'name'     => 'Asistente ST Store',
				'greeting' => '¡Hola! Soy el asistente de ST Store. Puedo decirte si hay stock de un producto, su precio, cómo son los envíos y medios de pago, o ayudarte a revisar el estado de tu pedido.',
				'chips'    => array(
					array( 'label' => 'Ver categorías', 'message' => '¿Qué categorías tienen?' ),
					array( 'label' => '¿Hay stock?', 'message' => 'Quiero consultar el stock de un producto' ),
					array( 'label' => 'Envíos', 'message' => '¿Cómo son los envíos?' ),
					array( 'label' => 'Medios de pago', 'message' => '¿Qué medios de pago aceptan?' ),
					array( 'label' => 'Estado de mi pedido', 'message' => 'Quiero ver el estado de mi pedido' ),
					array( 'label' => 'Hablar con un asesor', 'message' => 'Quiero hablar con un asesor' ),
				),
			)
		);
	}

	public static function container() {
		echo '<div id="sts-chatbot" class="sts-chatbot" data-ready="0"></div>';
	}

	public static function admin_menu() {
		add_submenu_page( 'sts-store', 'Chatbot', 'Chatbot', 'manage_options', 'sts-chatbot', array( __CLASS__, 'admin_page' ) );
	}

	public static function admin_page() {
		$stats = wp_parse_args(
			(array) get_option( self::STATS_OPTION, array() ),
			array(
				'total'      => 0,
				'intents'    => array(),
				'unanswered' => array(),
			)
		);
		$labels = STS_Chatbot_Engine::intent_labels();
		arsort( $stats['intents'] );

		echo '<div class="wrap"><h1>Chatbot · estadísticas</h1>';
		echo '<p>Cuántas consultas recibe el asistente y de qué tipo. No se guardan los mensajes completos: solo el tipo de consulta y, de las que el bot no entendió, el texto sin correos ni números largos. Sirve para decidir qué respuestas o productos agregar.</p>';
		printf( '<p><strong>Mensajes atendidos:</strong> %d</p>', (int) $stats['total'] );

		if ( $stats['intents'] ) {
			echo '<table class="widefat striped" style="max-width:520px"><thead><tr><th>Tipo de consulta</th><th>Veces</th></tr></thead><tbody>';
			foreach ( $stats['intents'] as $intent => $n ) {
				printf( '<tr><td>%s</td><td>%d</td></tr>', esc_html( $labels[ $intent ] ?? $intent ), (int) $n );
			}
			echo '</tbody></table>';
		}
		if ( $stats['unanswered'] ) {
			echo '<h2>Consultas que el bot no entendió</h2><table class="widefat striped" style="max-width:720px"><tbody>';
			foreach ( $stats['unanswered'] as $row ) {
				printf( '<tr><td style="width:150px">%s</td><td>%s</td></tr>', esc_html( $row[0] ), esc_html( $row[1] ) );
			}
			echo '</tbody></table>';
		}
		echo '</div>';
	}
}
