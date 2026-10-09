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

	if ( '' === $pixel_id || is_user_logged_in() ) {
		return;
	}
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
	fbq('init', '<?php echo esc_js( $pixel_id ); ?>');
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

	if ( '' === $ga_id || is_user_logged_in() ) {
		return;
	}
	?>
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga_id ); ?>"></script>
	<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());
	gtag('config', '<?php echo esc_js( $ga_id ); ?>');
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
 * Print a script that sends one event to both Meta and GA, if set up.
 *
 * Both snippets are optional and independent: a shop with only the
 * pixel, only Analytics, or both gets exactly the events it can act
 * on, and a shop with neither prints nothing at all. The guard on
 * fbq / gtag also means a missing snippet can never throw.
 *
 * @param string $event    Shared event name, e.g. "ViewContent".
 * @param array  $meta     Meta-specific payload (contents, content_type, value, currency).
 * @param array  $ga_items GA4 items array. Each item needs id, name, price, quantity.
 * @return void
 */
function techjossecom_track_event( $event, $meta = array(), $ga_items = array() ) {
	$pixel_id = techjossecom_fb_pixel_id();
	$ga_id    = techjossecom_ga_id();

	if ( ( '' === $pixel_id && '' === $ga_id ) || is_user_logged_in() ) {
		return;
	}

	$meta_json = wp_json_encode( $meta );
	$ga_json   = wp_json_encode( $ga_items );
	?>
	<script>
	window.techjossecomTrack && window.techjossecomTrack( '<?php echo esc_js( $event ); ?>', <?php echo $meta_json ? $meta_json : '{}'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON built with wp_json_encode. ?>, <?php echo $ga_json ? $ga_json : '[]'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> );
	</script>
	<?php
}

/**
 * Print the JS bridge that turns a server event into pixel / GA calls.
 *
 * One small helper, window.techjossecomTrack(), maps the shared event
 * name to Meta's event API and GA4's ecommerce API. It is printed once
 * in the footer when either service is set up, so the server can emit
 * events without repeating the platform-specific calls in every
 * template.
 *
 * @return void
 */
function techjossecom_tracking_bridge() {
	if ( ( '' === techjossecom_fb_pixel_id() && '' === techjossecom_ga_id() ) || is_user_logged_in() ) {
		return;
	}
	?>
	<script>
	window.techjossecomTrack = function ( event, meta, gaItems ) {
		if ( window.fbq ) {
			try { window.fbq( 'track', event, meta ); } catch ( e ) {}
		}
		if ( window.gtag && Array.isArray( gaItems ) ) {
			try { window.gtag( 'event', event, { items: gaItems } ); } catch ( e ) {}
		}
	};
	</script>
	<?php
}
add_action( 'wp_footer', 'techjossecom_tracking_bridge', 5 );

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

	techjossecom_track_event(
		'ViewContent',
		array(
			'content_ids'  => array( $product['id'] ),
			'content_name' => $product['name'],
			'content_type' => 'product',
			'value'        => $product['price'],
			'currency'     => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
		),
		array(
			array(
				'item_id'   => $product['id'],
				'item_name' => $product['name'],
				'price'     => $product['price'],
				'quantity'  => 1,
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

	techjossecom_track_event(
		'InitiateCheckout',
		array(
			'num_items'    => $cart->get_cart_contents_count(),
			'value'        => (float) $cart->get_total( 'numeric' ),
			'currency'     => get_woocommerce_currency(),
			'content_type' => 'product',
		),
		array()
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

	$ga_items = array();

	foreach ( $order->get_items() as $item ) {
		$ga_items[] = array(
			'item_id'   => (string) $item->get_product_id(),
			'item_name' => $item->get_name(),
			'price'     => (float) $order->get_item_total( $item ),
			'quantity'  => (int) $item->get_quantity(),
		);
	}

	techjossecom_track_event(
		'Purchase',
		array(
			'content_ids'  => wp_list_pluck( $ga_items, 'item_id' ),
			'content_type' => 'product',
			'value'        => (float) $order->get_total(),
			'currency'     => $order->get_currency(),
		),
		$ga_items
	);
}
add_action( 'woocommerce_thankyou', 'techjossecom_track_purchase', 10, 1 );



