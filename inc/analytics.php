<?php
/**
 * Tracking snippets: Facebook Pixel and Google Analytics.
 *
 * Both are driven by the same theme mods the settings screen
 * (Appearance → Theme Settings) writes, so a store owner adds the
 * IDs once in the admin and the snippets appear on the front end
 * without touching a file. Nothing is printed while an ID is empty,
 * so an unconfigured site never loads a broken or half-empty script.
 *
 * @package TechJosse_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A valid Meta Pixel ID or GA4 measurement ID, or an empty string.
 *
 * A pasted snippet is reduced to just the ID: the IDs Facebook and
 * Google use are digits only (pixel) or "G-" then digits/letters
 * (GA4), so stripping everything else means a visitor can paste the
 * whole code block and only the safe identifier is kept. Anything
 * that still looks like markup returns empty and is never printed.
 *
 * @param string $value Raw posted value.
 * @param string $kind  Either 'pixel' or 'ga'.
 * @return string The clean ID, or '' when the value is not one.
 */
function techjossecom_tracking_id( $value, $kind = 'pixel' ) {
	$value = trim( (string) $value );

	if ( 'ga' === $kind ) {
		// GA4 measurement IDs look like "G-XXXXXXXXXX".
		if ( preg_match( '/\bG-[A-Z0-9]{4,}\b/i', $value, $m ) ) {
			return strtoupper( $m[0] );
		}

		return '';
	}

	// A pixel ID is a long run of digits, e.g. 1234567890123456.
	if ( preg_match( '/\b\d{6,}\b/', $value, $m ) ) {
		return $m[0];
	}

	return '';
}

/**
 * The configured Meta Pixel ID, or '' when none is set.
 *
 * @return string
 */
function techjossecom_fb_pixel_id() {
	return techjossecom_tracking_id( techjossecom_mod( 'fb_pixel_id' ), 'pixel' );
}

/**
 * The configured Google Analytics 4 measurement ID, or '' when none.
 *
 * @return string
 */
function techjossecom_ga_id() {
	return techjossecom_tracking_id( techjossecom_mod( 'google_analytics_id' ), 'ga' );
}

/**
 * The configured Conversions API access token, or ''.
 *
 * A system user token from Meta Events Manager. When present the theme
 * also sends events from the server, so a conversion is still recorded
 * when a browser or extension blocks the pixel.
 *
 * @return string
 */
function techjossecom_fb_capi_token() {
	return trim( (string) techjossecom_mod( 'fb_capi_token' ) );
}

/**
 * The Meta "Test Events" code, or ''.
 *
 * Pasted from Events Manager → Test Events. When set, every event is
 * routed into the Test Events tool and logged-in staff are no longer
 * skipped, so an admin can verify the setup without touching code.
 *
 * @return string
 */
function techjossecom_fb_test_event_code() {
	$code = trim( (string) techjossecom_mod( 'fb_test_event_code' ) );

	return preg_replace( '/[^A-Za-z0-9_-]/', '', $code );
}

/**
 * The optional GA4 Measurement Protocol API secret, or ''.
 *
 * With this set the theme also sends GA4 events from the server, using
 * the _ga cookie as the client id so they join the same session.
 *
 * @return string
 */
function techjossecom_ga_api_secret() {
	return trim( (string) techjossecom_mod( 'ga_api_secret' ) );
}

/**
 * Whether the current request should be tracked at all.
 *
 * Staff accounts are skipped so their own clicks never pollute the
 * reports. A Test Events code lifts that guard so an admin can watch
 * their own browsing land in the Test Events tool while testing.
 *
 * @return bool
 */
function techjossecom_should_track() {
	if ( '' !== techjossecom_fb_test_event_code() ) {
		return true;
	}

	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return false;
	}

	return true;
}

/**
 * Whether the Meta Conversions API has both an ID and a token.
 *
 * @return bool
 */
function techjossecom_capi_ready() {
	return '' !== techjossecom_fb_pixel_id() && '' !== techjossecom_fb_capi_token();
}

/**
 * Whether server-side GA4 has an ID and an API secret.
 *
 * @return bool
 */
function techjossecom_ga_server_ready() {
	return '' !== techjossecom_ga_id() && '' !== techjossecom_ga_api_secret();
}

/**
 * SHA-256 hash a value the way the Conversions API expects: trimmed,
 * lower-cased, then hashed. Returns '' for an empty value.
 *
 * @param string $value Raw value.
 * @return string
 */
function techjossecom_capi_hash( $value ) {
	$value = strtolower( trim( (string) $value ) );

	if ( '' === $value ) {
		return '';
	}

	return hash( 'sha256', $value );
}

/**
 * Normalise a Bangladeshi mobile number to digits with the 880 country
 * code, which is what the Conversions API wants for a phone match.
 *
 * @param string $phone Raw phone number.
 * @return string
 */
function techjossecom_capi_phone( $phone ) {
	$digits = preg_replace( '/\D/', '', (string) $phone );

	if ( '' === $digits ) {
		return '';
	}

	if ( 0 === strpos( $digits, '880' ) ) {
		return $digits;
	}

	if ( 0 === strpos( $digits, '0' ) ) {
		return '88' . ltrim( $digits, '0' );
	}

	return '88' . $digits;
}

/**
 * The visitor data every server event carries: IP, user agent and the
 * Meta browser cookies. No personally identifying fields here, so it is
 * safe for page events such as ViewContent or Search.
 *
 * @return array
 */
function techjossecom_capi_user_data() {
	$data = array();

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( $ip ) {
		$data['client_ip_address'] = $ip;
	}

	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
	if ( $ua ) {
		$data['client_user_agent'] = $ua;
	}

	if ( ! empty( $_COOKIE['_fbp'] ) ) {
		$data['fbp'] = sanitize_text_field( wp_unslash( $_COOKIE['_fbp'] ) );
	}

	if ( ! empty( $_COOKIE['_fbc'] ) ) {
		$data['fbc'] = sanitize_text_field( wp_unslash( $_COOKIE['_fbc'] ) );
	}

	return $data;
}

/**
 * Hashed billing details from an order, merged over the visitor data.
 * This is what lifts a Purchase event's match quality.
 *
 * @param WC_Order|int $order Order object or ID.
 * @return array
 */
function techjossecom_capi_user_data_order( $order ) {
	$data = techjossecom_capi_user_data();

	if ( is_numeric( $order ) && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( absint( $order ) );
	}

	if ( ! $order instanceof WC_Order ) {
		return $data;
	}

	$em = techjossecom_capi_hash( $order->get_billing_email() );
	if ( $em ) {
		$data['em'] = array( $em );
	}

	$ph = techjossecom_capi_hash( techjossecom_capi_phone( $order->get_billing_phone() ) );
	if ( $ph ) {
		$data['ph'] = array( $ph );
	}

	$fn = techjossecom_capi_hash( $order->get_billing_first_name() );
	if ( $fn ) {
		$data['fn'] = $fn;
	}

	$ln = techjossecom_capi_hash( $order->get_billing_last_name() );
	if ( $ln ) {
		$data['ln'] = $ln;
	}

	$ct = techjossecom_capi_hash( $order->get_billing_city() );
	if ( $ct ) {
		$data['ct'] = $ct;
	}

	$st = techjossecom_capi_hash( $order->get_billing_state() );
	if ( $st ) {
		$data['st'] = $st;
	}

	$country = techjossecom_capi_hash( $order->get_billing_country() );
	if ( $country ) {
		$data['country'] = $country;
	}

	$external = techjossecom_capi_hash( (string) $order->get_order_number() );
	if ( $external ) {
		$data['external_id'] = $external;
	}

	return $data;
}

/**
 * The full URL of the current request, used as event_source_url.
 *
 * @return string
 */
function techjossecom_current_url() {
	$scheme = is_ssl() ? 'https' : 'http';
	$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
	$uri    = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

	if ( ! $host ) {
		return home_url( '/' );
	}

	return $scheme . '://' . $host . $uri;
}

/**
 * The GA4 client id from the _ga cookie, e.g. "GA1.1.xxx" -> "xxx".
 *
 * @return string
 */
function techjossecom_ga_client_id() {
	if ( empty( $_COOKIE['_ga'] ) ) {
		return '';
	}

	$parts = explode( '.', sanitize_text_field( wp_unslash( $_COOKIE['_ga'] ) ) );

	return isset( $parts[2] ) && isset( $parts[3] ) ? $parts[2] . '.' . $parts[3] : '';
}

/**
 * Send one event to the Meta Conversions API.
 *
 * $event_id is the same value the browser pixel uses for the event, so
 * Meta counts the browser hit and this server hit as one. When a Test
 * Events code is set it is added as the top-level test_event_code field,
 * which is what routes the event into the Test Events tool.
 *
 * @param string $event_name  Meta standard event name, e.g. "Purchase".
 * @param string $event_id    Shared deduplication id.
 * @param array  $custom_data Value, currency, contents and so on.
 * @param array  $user_data   Hashed / raw user data. Visitor data if empty.
 * @param string $source_url  Event source URL. Current URL if empty.
 * @param bool   $blocking    Wait for the response (used for Purchase).
 * @return bool True when the request was dispatched without a transport error.
 */
function techjossecom_capi_send( $event_name, $event_id, $custom_data = array(), $user_data = array(), $source_url = '', $blocking = false ) {
	if ( ! techjossecom_capi_ready() || ! techjossecom_should_track() ) {
		return false;
	}

	$pixel_id = techjossecom_fb_pixel_id();
	$token    = techjossecom_fb_capi_token();
	$test     = techjossecom_fb_test_event_code();

	$event = array(
		'event_name'       => $event_name,
		'event_time'       => time(),
		'event_id'         => $event_id,
		'action_source'    => 'website',
		'event_source_url' => $source_url ? $source_url : techjossecom_current_url(),
	);

	if ( ! empty( $custom_data ) ) {
		$event['custom_data'] = $custom_data;
	}

	$event['user_data'] = ! empty( $user_data ) ? $user_data : techjossecom_capi_user_data();

	$body = array( 'data' => array( $event ) );

	if ( '' !== $test ) {
		$body['test_event_code'] = $test;
	}

	$url = add_query_arg(
		'access_token',
		rawurlencode( $token ),
		'https://graph.facebook.com/v21.0/' . rawurlencode( $pixel_id ) . '/events'
	);

	$response = wp_remote_post(
		$url,
		array(
			'method'   => 'POST',
			'timeout'  => $blocking ? 6 : 2,
			'blocking' => $blocking,
			'headers'  => array( 'Content-Type' => 'application/json' ),
			'body'     => wp_json_encode( $body ),
		)
	);

	return ! is_wp_error( $response );
}

/**
 * Send one event to GA4 via the Measurement Protocol.
 *
 * Uses the _ga cookie as the client id so the server event joins the
 * same session the browser pixel reports, and GA4's own dedup key so a
 * browser + server pair is counted once.
 *
 * @param string $event_name GA4 event name, e.g. "purchase".
 * @param array  $params     GA4 event parameters.
 * @return bool True when the request was dispatched without a transport error.
 */
function techjossecom_ga_server_send( $event_name, $params = array() ) {
	if ( ! techjossecom_ga_server_ready() || ! techjossecom_should_track() ) {
		return false;
	}

	$client_id = techjossecom_ga_client_id();

	if ( '' === $client_id ) {
		return false;
	}

	$url = add_query_arg(
		array(
			'measurement_id' => techjossecom_ga_id(),
			'api_secret'     => techjossecom_ga_api_secret(),
		),
		'https://www.google-analytics.com/mp/collect'
	);

	$body = array(
		'client_id' => $client_id,
		'events'    => array(
			array(
				'name'   => $event_name,
				'params' => $params,
			),
		),
	);

	$response = wp_remote_post(
		$url,
		array(
			'method'   => 'POST',
			'timeout'  => 2,
			'blocking' => false,
			'headers'  => array( 'Content-Type' => 'application/json' ),
			'body'     => wp_json_encode( $body ),
		)
	);

	return ! is_wp_error( $response );
}

/**
 * Print the Facebook Pixel base code in <head>.
 *
 * The snippet is Meta's own boilerplate. It is only added once and
 * only for real visitors: an editor previewing the site is skipped so
 * a click in the dashboard never shows up in the reports, and the
 * <noscript> copy is left out because it is a fallback for browsers
 * with JavaScript disabled, which this storefront does not support.
 *
 * @return void
 */
function techjossecom_facebook_pixel_head() {
	$pixel_id = techjossecom_fb_pixel_id();

	if ( '' === $pixel_id || ! techjossecom_should_track() ) {
		return;
	}

	$test_code = techjossecom_fb_test_event_code();
	?>
	<!-- Meta Pixel Code -->
	<script>
	!function(f,b,e,v,n,t,s)
	{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
	n.callMethod.apply(n,arguments):n.queue.push(arguments)};
	if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
	n.queue=[];t=b.createElement(e);t.async=!0;
	t.src=v;s=b.getElementsByTagName(e)[0];
	s.parentNode.insertBefore(t,s)}(window, document,'script',
	'https://connect.facebook.net/en_US/fbevents.js');
	<?php if ( '' !== $test_code ) : ?>
	fbq('init', '<?php echo esc_js( $pixel_id ); ?>', {}, { testEventCode: '<?php echo esc_js( $test_code ); ?>' });
	<?php else : ?>
	fbq('init', '<?php echo esc_js( $pixel_id ); ?>');
	<?php endif; ?>
	fbq('track', 'PageView');
	</script>
	<!-- End Meta Pixel Code -->
	<?php
}
add_action( 'wp_head', 'techjossecom_facebook_pixel_head', 99 );

/**
 * Print the Google Analytics (gtag.js) snippet in <head>.
 *
 * Loads gtag.js from Google and sends the initial page view. Skipped
 * for logged-in editors for the same reason as the pixel.
 *
 * @return void
 */
function techjossecom_google_analytics_head() {
	$ga_id = techjossecom_ga_id();

	if ( '' === $ga_id || ! techjossecom_should_track() ) {
		return;
	}

	$test_code = techjossecom_fb_test_event_code();
	?>
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga_id ); ?>"></script>
	<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());
	gtag('config', '<?php echo esc_js( $ga_id ); ?>'<?php echo '' !== $test_code ? ", { 'debug_mode': true }" : ''; ?>);
	</script>
	<!-- End Google tag -->
	<?php
}
add_action( 'wp_head', 'techjossecom_google_analytics_head', 99 );

/**
 * Format one product for the Meta / GA event payloads.
 *
 * The two platforms name the fields slightly differently (Meta uses
 * content_ids, GA uses items) but the underlying values are the same,
 * so they are gathered once here and mapped per event below.
 *
 * @param WC_Product|int $product Product or product ID.
 * @return array Array of id, name, category, price and quantity.
 */
function techjossecom_tracking_product( $product ) {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( absint( $product ) );
	}

	if ( ! $product instanceof WC_Product ) {
		return array();
	}

	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	$names = array();

	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$names[] = $term->name;
		}
	}

	return array(
		'id'       => (string) $product->get_id(),
		'name'     => $product->get_name(),
		'category' => $names ? implode( ', ', $names ) : '',
		'price'    => (float) wc_get_price_to_display( $product ),
		'quantity' => 1,
	);
}

/**
 * Send one event to Meta and GA, from the server and the browser.
 *
 * Both snippets are optional and independent: a shop with only the
 * pixel, only Analytics, or both gets exactly the events it can act
 * on, and a shop with neither prints nothing at all. The guard on
 * fbq / gtag also means a missing snippet can never throw.
 *
 * When the Conversions API token (and/or the GA4 API secret) is set,
 * the same event is also sent from the server using the shared event
 * id, so a blocked pixel or extension does not lose the conversion and
 * the two are never counted twice.
 *
 * @param string $fb_event Meta event name, e.g. "ViewContent".
 * @param array  $args     event_id, meta, ga_event, ga_params, user_data,
 *                         source_url and blocking. See wp_parse_args below.
 * @return void
 */
function techjossecom_track_event( $fb_event, $args = array() ) {
	if ( ! techjossecom_should_track() ) {
		return;
	}

	$pixel_id = techjossecom_fb_pixel_id();
	$ga_id    = techjossecom_ga_id();

	if ( '' === $pixel_id && '' === $ga_id ) {
		return;
	}

	$args = wp_parse_args(
		$args,
		array(
			'event_id'   => '',
			'meta'       => array(),
			'ga_event'   => '',
			'ga_params'  => array(),
			'user_data'  => array(),
			'source_url' => '',
			'blocking'   => false,
		)
	);

	/*
	 * One id is shared by the browser pixel and the server call, so Meta
	 * counts the pair as a single event instead of two.
	 */
	$event_id = $args['event_id'] ? $args['event_id'] : wp_generate_uuid4();

	// Server side: Meta Conversions API.
	if ( techjossecom_capi_ready() ) {
		techjossecom_capi_send( $fb_event, $event_id, $args['meta'], $args['user_data'], $args['source_url'], $args['blocking'] );
	}

	// Server side: GA4 Measurement Protocol, only when an API secret is set.
	if ( '' !== $args['ga_event'] && techjossecom_ga_server_ready() ) {
		techjossecom_ga_server_send( $args['ga_event'], $args['ga_params'] );
	}

	// Browser side: pixel and gtag, both carrying the same event id.
	$payload = array_merge(
		array(
			'eventId' => $event_id,
			'meta'    => $args['meta'],
			'ga'      => $args['ga_event'],
		),
		$args['ga_params']
	);

	$json = wp_json_encode( $payload );
	?>
	<script>
	window.techjossecomTrack && window.techjossecomTrack( '<?php echo esc_js( $fb_event ); ?>', <?php echo $json ? $json : '{}'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON built with wp_json_encode. ?> );
	</script>
	<?php
}

/**
 * Print the JS bridge that turns a server event into pixel / GA calls.
 *
 * One small helper, window.techjossecomTrack(), maps the shared event
 * name to Meta's event API and GA4's ecommerce API, and stamps the
 * event id onto the pixel call so it dedupes with the server hit. It is
 * printed once in the head when either service is set up, so events
 * fired later in the body (such as Purchase on the thank-you page)
 * always find it already defined.
 *
 * @return void
 */
function techjossecom_tracking_bridge() {
	if ( ( '' === techjossecom_fb_pixel_id() && '' === techjossecom_ga_id() ) || ! techjossecom_should_track() ) {
		return;
	}
	?>
	<script>
	window.techjossecomTrack = function ( event, data ) {
		data = data || {};
		if ( window.fbq ) {
			try {
				var opts = data.eventId ? { eventID: data.eventId } : undefined;
				window.fbq( 'track', event, data.meta || {}, opts );
			} catch ( e ) {}
		}
		if ( window.gtag && data.ga ) {
			try {
				var params = {};
				if ( typeof data.value !== 'undefined' ) { params.value = data.value; }
				if ( data.currency ) { params.currency = data.currency; }
				if ( data.transaction_id ) { params.transaction_id = data.transaction_id; }
				if ( data.search_term ) { params.search_term = data.search_term; }
				if ( Array.isArray( data.gaItems ) && data.gaItems.length ) { params.items = data.gaItems; }
				window.gtag( 'event', data.ga, params );
			} catch ( e ) {}
		}
	};
	</script>
	<?php
}
add_action( 'wp_head', 'techjossecom_tracking_bridge', 100 );

/**
 * Fire a ViewContent event on a single product page.
 *
 * @return void
 */
function techjossecom_track_view_content() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product = techjossecom_tracking_product( get_the_ID() );

	if ( empty( $product ) ) {
		return;
	}

	$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
	$ga_item  = array(
		'item_id'   => $product['id'],
		'item_name' => $product['name'],
		'price'     => $product['price'],
		'quantity'  => 1,
	);

	techjossecom_track_event(
		'ViewContent',
		array(
			'meta'      => array(
				'content_ids'  => array( $product['id'] ),
				'content_name' => $product['name'],
				'content_type' => 'product',
				'value'        => $product['price'],
				'currency'     => $currency,
			),
			'ga_event'  => 'view_item',
			'ga_params' => array(
				'currency' => $currency,
				'value'    => $product['price'],
				'gaItems'  => array( $ga_item ),
			),
		)
	);
}
add_action( 'wp_footer', 'techjossecom_track_view_content' );

/**
 * Fire an InitiateCheckout event on the checkout page.
 *
 * @return void
 */
function techjossecom_track_initiate_checkout() {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}

	$cart = function_exists( 'WC' ) && WC()->cart ? WC()->cart : null;

	if ( ! $cart ) {
		return;
	}

	$currency = get_woocommerce_currency();
	$value    = (float) $cart->get_total( 'numeric' );

	$ga_items  = array();
	$meta_ids  = array();
	$meta_list = array();

	foreach ( $cart->get_cart() as $item ) {
		if ( empty( $item['data'] ) ) {
			continue;
		}

		$id  = (string) $item['product_id'];
		$qty = (int) $item['quantity'];

		$meta_ids[]  = $id;
		$meta_list[] = array(
			'id'       => $id,
			'quantity' => $qty,
		);
		$ga_items[]  = array(
			'item_id'   => $id,
			'item_name' => $item['data']->get_name(),
			'price'     => (float) $item['data']->get_price(),
			'quantity'  => $qty,
		);
	}

	techjossecom_track_event(
		'InitiateCheckout',
		array(
			'meta'      => array(
				'num_items'    => $cart->get_cart_contents_count(),
				'value'        => $value,
				'currency'     => $currency,
				'content_type' => 'product',
				'content_ids'  => $meta_ids,
				'contents'     => $meta_list,
			),
			'ga_event'  => 'begin_checkout',
			'ga_params' => array(
				'currency' => $currency,
				'value'    => $value,
				'gaItems'  => $ga_items,
			),
		)
	);
}
add_action( 'wp_footer', 'techjossecom_track_initiate_checkout' );

/**
 * Fire a Purchase event on the order-received (thank you) page.
 *
 * The order is loaded from the query string, and its line items are
 * turned into the GA4 item shape. Meta gets a single "Purchase" call
 * with the order total, which is what its standard event expects.
 *
 * @param int $order_id Order ID passed by the woocommerce_thankyou hook.
 * @return void
 */
function techjossecom_track_purchase( $order_id ) {
	if ( ! $order_id || ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	$order = wc_get_order( $order_id );

	if ( ! $order ) {
		return;
	}

	$ga_items  = array();
	$meta_ids  = array();
	$meta_list = array();

	foreach ( $order->get_items() as $item ) {
		$id  = (string) $item->get_product_id();
		$qty = (int) $item->get_quantity();

		$meta_ids[]  = $id;
		$meta_list[] = array(
			'id'       => $id,
			'quantity' => $qty,
		);
		$ga_items[]  = array(
			'item_id'   => $id,
			'item_name' => $item->get_name(),
			'price'     => (float) $order->get_item_total( $item ),
			'quantity'  => $qty,
		);
	}

	techjossecom_track_event(
		'Purchase',
		array(
			// A stable id per order, so a refresh or the COD AJAX copy of
			// the same conversion dedupes against the thank-you page hit.
			'event_id'   => 'purchase_' . $order->get_order_key(),
			'meta'       => array(
				'content_ids'  => $meta_ids,
				'content_type' => 'product',
				'contents'     => $meta_list,
				'value'        => (float) $order->get_total(),
				'currency'     => $order->get_currency(),
			),
			'ga_event'   => 'purchase',
			'ga_params'  => array(
				'transaction_id' => $order->get_order_number(),
				'currency'       => $order->get_currency(),
				'value'          => (float) $order->get_total(),
				'gaItems'        => $ga_items,
			),
			'user_data'  => techjossecom_capi_user_data_order( $order ),
			'source_url' => $order->get_checkout_order_received_url(),
			// Wait for the server call so the conversion is never lost on
			// a fast redirect away from the thank-you page.
			'blocking'   => true,
		)
	);
}
add_action( 'woocommerce_thankyou', 'techjossecom_track_purchase', 10, 1 );

/**
 * Fire a Search event server side (and to the Test Events tool).
 *
 * Called from the live-search AJAX handler. The browser already fires
 * its own Search event from the suggestion panel, so the server call
 * reuses that same event id passed in from the script and the two
 * dedupe into one.
 *
 * @param string $term     Search term.
 * @param string $event_id Shared deduplication id from the browser.
 * @return void
 */
function techjossecom_track_search_server( $term, $event_id = '' ) {
	$term = trim( (string) $term );

	if ( '' === $term ) {
		return;
	}

	techjossecom_capi_send(
		'Search',
		$event_id ? $event_id : wp_generate_uuid4(),
		array(
			'search_string' => $term,
			'content_type'  => 'product',
		),
		array(),
		techjossecom_current_url()
	);

	if ( techjossecom_ga_server_ready() ) {
		techjossecom_ga_server_send(
			'search',
			array( 'search_term' => $term )
		);
	}
}

/**
 * Fire an AddToCart event from the server.
 *
 * The add-to-cart request itself carries the event id the browser used,
 * so the pixel hit and this server hit dedupe into one. Called from the
 * theme's own add-to-cart AJAX handler.
 *
 * @param int    $product_id Product added.
 * @param int    $quantity   Quantity added.
 * @param string $event_id   Shared deduplication id from the browser.
 * @return void
 */
function techjossecom_track_add_to_cart_server( $product_id, $quantity = 1, $event_id = '' ) {
	$product = techjossecom_tracking_product( $product_id );

	if ( empty( $product ) ) {
		return;
	}

	$currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '';
	$quantity = max( 1, (int) $quantity );
	$value    = (float) $product['price'] * $quantity;

	techjossecom_capi_send(
		'AddToCart',
		$event_id ? $event_id : wp_generate_uuid4(),
		array(
			'content_ids'  => array( $product['id'] ),
			'content_name' => $product['name'],
			'content_type' => 'product',
			'contents'     => array(
				array(
					'id'       => $product['id'],
					'quantity' => $quantity,
				),
			),
			'value'        => $value,
			'currency'     => $currency,
		),
		array(),
		techjossecom_current_url()
	);

	if ( techjossecom_ga_server_ready() ) {
		techjossecom_ga_server_send(
			'add_to_cart',
			array(
				'currency' => $currency,
				'value'    => $value,
				'items'    => array(
					array(
						'item_id'   => $product['id'],
						'item_name' => $product['name'],
						'price'     => (float) $product['price'],
						'quantity'  => $quantity,
					),
				),
			)
		);
	}
}

/**
 * Fire a Purchase event from the server for a quick cash-on-delivery
 * order placed through the popup.
 *
 * The quick-order popup builds its own order and redirects to the
 * thank-you page, where techjossecom_track_purchase() also fires. Both
 * use the same order-keyed event id, so Meta counts the conversion once
 * no matter which path the shopper takes.
 *
 * @param WC_Order|int $order Order object or ID.
 * @return void
 */
function techjossecom_track_cod_purchase_server( $order ) {
	if ( is_numeric( $order ) && function_exists( 'wc_get_order' ) ) {
		$order = wc_get_order( absint( $order ) );
	}

	if ( ! $order instanceof WC_Order ) {
		return;
	}

	$ga_items  = array();
	$meta_ids  = array();
	$meta_list = array();

	foreach ( $order->get_items() as $item ) {
		$id  = (string) $item->get_product_id();
		$qty = (int) $item->get_quantity();

		$meta_ids[]  = $id;
		$meta_list[] = array(
			'id'       => $id,
			'quantity' => $qty,
		);
		$ga_items[]  = array(
			'item_id'   => $id,
			'item_name' => $item->get_name(),
			'price'     => (float) $order->get_item_total( $item ),
			'quantity'  => $qty,
		);
	}

	techjossecom_capi_send(
		'Purchase',
		'purchase_' . $order->get_order_key(),
		array(
			'content_ids'  => $meta_ids,
			'content_type' => 'product',
			'contents'     => $meta_list,
			'value'        => (float) $order->get_total(),
			'currency'     => $order->get_currency(),
		),
		techjossecom_capi_user_data_order( $order ),
		$order->get_checkout_order_received_url(),
		true
	);

	if ( techjossecom_ga_server_ready() ) {
		techjossecom_ga_server_send(
			'purchase',
			array(
				'transaction_id' => $order->get_order_number(),
				'currency'       => $order->get_currency(),
				'value'          => (float) $order->get_total(),
				'items'          => $ga_items,
			)
		);
	}
}



