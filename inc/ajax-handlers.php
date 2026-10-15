<?php
/**
 * AJAX handlers: live product search, shipping options and the cash on
 * delivery quick order.
 *
 * @package TechJosse_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Live product search used by the header search box.
 *
 * @return void
 */
function techjossecom_ajax_live_search() {
	check_ajax_referer( 'techjossecom_search', 'nonce' );

	$term = isset( $_REQUEST['term'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['term'] ) ) : '';
	$term = trim( $term );

	if ( mb_strlen( $term ) < 2 ) {
		wp_send_json_success(
			array(
				'html'   => '',
				'count'  => 0,
				'notice' => __( 'Type at least two characters.', 'techjossecom' ),
			)
		);
	}

	$limit = 6;

	// Prefer WooCommerce products, fall back to any searchable post type.
	$query_args = array(
		'post_status'         => 'publish',
		'posts_per_page'      => $limit,
		's'                   => $term,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => false,
	);

	if ( class_exists( 'WooCommerce' ) ) {
		$query_args['post_type'] = array( 'product' );

		// Also match the SKU.
		$query_args['s'] = $term;
	} else {
		$query_args['post_type'] = array( 'post', 'page' );
	}

	/**
	 * Filter the live search query arguments.
	 *
	 * @param array  $query_args WP_Query arguments.
	 * @param string $term       Search term.
	 */
	$query_args = apply_filters( 'techjossecom_live_search_args', $query_args, $term );

	$search = new WP_Query( $query_args );
	$total  = (int) $search->found_posts;

	// A SKU search fallback, because WP_Query does not look at post meta.
	if ( class_exists( 'WooCommerce' ) && 0 === $total ) {
		$by_sku = wc_get_products(
			array(
				'limit'   => $limit,
				'status'  => 'publish',
				'sku'     => $term,
				'return'  => 'ids',
			)
		);

		if ( $by_sku ) {
			$search = new WP_Query(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'post__in'       => $by_sku,
					'posts_per_page' => $limit,
				)
			);
			$total  = count( $by_sku );
		}
	}

	$html = '';

	if ( $search->have_posts() ) {
		$html .= '<ul class="tj-live-search-list">';

		while ( $search->have_posts() ) {
			$search->the_post();
			$post_id    = get_the_ID();
			$thumbnail  = has_post_thumbnail( $post_id )
				? get_the_post_thumbnail( $post_id, 'thumbnail', array( 'loading' => 'lazy', 'alt' => get_the_title() ) )
				: '';
			$price_html = '';

			if ( class_exists( 'WooCommerce' ) ) {
				$product = wc_get_product( $post_id );

				if ( $product ) {
					$price_html = $product->get_price_html();
				}
			}

			// No discount badge here on purpose: the suggestion row is already
			// thumbnail, name and price, and a fourth corner badge crowds it.
			$html .= '<li class="tj-live-search-item">';
			$html .= '<a href="' . esc_url( get_permalink() ) . '">';
			$html .= '<span class="tj-live-search-thumb">';
			$html .= ( $thumbnail ? $thumbnail : '<span class="tj-live-search-noimg" aria-hidden="true">&#128230;</span>' );
			$html .= '</span>';
			$html .= '<span class="tj-live-search-text">';
			$html .= '<span class="tj-live-search-title">' . esc_html( get_the_title() ) . '</span>';

			if ( $price_html ) {
				$html .= '<span class="tj-live-search-price">' . wp_kses_post( $price_html ) . '</span>';
			}

			$html .= '</span>';
			$html .= '</a></li>';
		}

		$html .= '</ul>';

		$search_url = add_query_arg(
			array(
				's'         => rawurlencode( $term ),
				'post_type' => class_exists( 'WooCommerce' ) ? 'product' : 'post',
			),
			home_url( '/' )
		);

		$html .= sprintf(
			'<a class="tj-live-search-all" href="%1$s">%2$s%3$s &rarr;</a>',
			esc_url( $search_url ),
			esc_html__( 'View all results', 'techjossecom' ),
			$total > $limit ? ' (' . esc_html( number_format_i18n( $total ) ) . ')' : ''
		);
	} else {
		$html .= '<p class="tj-live-search-empty">' . esc_html__( 'No products found.', 'techjossecom' ) . '</p>';
	}

	wp_reset_postdata();

	/*
	 * Server-side Search. The suggestion panel fires the browser event
	 * and posts its event id here, so the pixel hit and this server hit
	 * dedupe into one. Only sent when there are results, so an incomplete
	 * keystroke never becomes a Search.
	 */
	if ( $total > 0 ) {
		$event_id = isset( $_POST['event_id'] ) ? sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked above.
		techjossecom_track_search_server( $term, $event_id );
	}

	wp_send_json_success(
		array(
			'html'  => $html,
			'count' => $total,
			'term'  => $term,
		)
	);
}
add_action( 'wp_ajax_techjossecom_live_search', 'techjossecom_ajax_live_search' );
add_action( 'wp_ajax_nopriv_techjossecom_live_search', 'techjossecom_ajax_live_search' );

/**
 * The first error WooCommerce queued, as plain text.
 *
 * WooCommerce refuses an add by calling wc_add_notice() and returning false,
 * so the reason lives in the notice queue rather than in a return value. The
 * drawer is the only feedback this request has, so the notice is read here and
 * handed back to the script, which shows it beside the button. The queue is
 * emptied afterwards: the same reason is about to be shown on the page, and
 * leaving it behind would print it a second time in a banner.
 *
 * @return string The message, or a generic one when WooCommerce said nothing.
 */
function techjossecom_cart_error_message() {
	$notices = function_exists( 'wc_get_notices' ) ? wc_get_notices( 'error' ) : array();
	$message = '';

	foreach ( (array) $notices as $notice ) {
		$text = is_array( $notice ) && isset( $notice['notice'] ) ? $notice['notice'] : $notice;

		// WooCommerce mixes HTML links into these, e.g. a "View cart" button on
		// the stock messages. The markup has nowhere to go in a one line notice.
		if ( is_string( $text ) && '' !== trim( $text ) ) {
			$message = trim( wp_strip_all_tags( $text ) );
			break;
		}
	}

	wc_clear_notices();

	return $message ? $message : __( 'Sorry, this product could not be added to your cart.', 'techjossecom' );
}

/**
 * AJAX: add a product to the cart, simple or variable.
 *
 * WooCommerce's own ?wc-ajax=add_to_cart endpoint cannot be used here. It
 * accepts a product id and a quantity and nothing else: when handed a
 * variation id it rebuilds the attribute list from the variation's stored
 * attributes rather than from what the shopper picked (class-wc-ajax.php).
 *
 * That breaks the moment a variation leaves an attribute open, which is what
 * an "Any" value means in WooCommerce. The empty value is filtered out before
 * validation, no value was posted to replace it, and the add is refused with
 * "Size is a required field" - see WC_Cart::add_to_cart(). That is why a
 * variable product could be chosen, priced and clicked, and still refused.
 *
 * This endpoint takes the whole selection instead, the same way the non
 * JavaScript form post does in WC_Form_Handler::add_to_cart_handler_variable(),
 * so a variation with an open attribute keeps working.
 *
 * @return void
 */
function techjossecom_ajax_add_to_cart() {
	/*
	 * The nonce is checked without dying so that a stale one still comes back
	 * as readable JSON.
	 *
	 * check_ajax_referer() ends the request with a bare "-1" by default, which
	 * is not JSON: the script cannot parse it, falls through to its generic
	 * "Something went wrong", and the real reason - a page cache holding on to
	 * an expired token - never reaches the shopper.
	 */
	if ( ! check_ajax_referer( 'techjossecom_cart', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session has expired. Please refresh the page and try again.', 'techjossecom' ) ) );
	}

	if ( ! techjossecom_has_woocommerce() ) {
		wp_send_json_error( array( 'message' => __( 'The cart is not available.', 'techjossecom' ) ) );
	}

	// The parent product, always: a variation id is passed separately below.
	$product_id   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
	$quantity     = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 1; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- run through wc_stock_amount().
	$quantity     = max( 1, (int) $quantity );

	/*
	 * What the shopper chose, as attribute_pa_size => 'm'.
	 *
	 * Keys are sanitised to a slug because WooCommerce matches them against
	 * sanitize_title( attribute name ) when it validates the add, so an
	 * unsanitised key would simply be ignored.
	 */
	$raw       = isset( $_POST['variation'] ) ? wp_unslash( $_POST['variation'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised below.
	$variation = array();

	if ( is_array( $raw ) ) {
		foreach ( $raw as $key => $value ) {
			$key = sanitize_title( (string) $key );

			if ( '' === $key || ! is_scalar( $value ) ) {
				continue;
			}

			$variation[ $key ] = sanitize_text_field( (string) $value );
		}
	}

	$product = $product_id ? wc_get_product( $product_id ) : false;

	if ( ! $product instanceof WC_Product ) {
		wp_send_json_error( array( 'message' => __( 'This product is not available.', 'techjossecom' ) ) );
	}

	// A variable product cannot be added as itself; a variation has to be named.
	if ( $product->is_type( 'variable' ) && ! $variation_id ) {
		wp_send_json_error( array( 'message' => __( 'Please choose the product options first.', 'techjossecom' ) ) );
	}

	/*
	 * A variation only counts when it really belongs to the product it was
	 * sent with, so that a crafted id cannot pull another product's option in.
	 *
	 * The loaded variation is kept in its own variable on purpose. $variation
	 * is the shopper's attribute array and WC_Cart::add_to_cart() indexes it
	 * directly, so overwriting it with a product object would turn every
	 * variable product add into a fatal "cannot use object as array" error
	 * rather than a plain refusal.
	 */
	if ( $variation_id ) {
		$variation_product = wc_get_product( $variation_id );

		if ( ! $variation_product instanceof WC_Product || (int) $variation_product->get_parent_id() !== (int) $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Please choose a valid product option.', 'techjossecom' ) ) );
		}
	}

	/*
	 * Validation and the add itself, with anything unexpected treated as a
	 * failed add instead of being allowed to escape.
	 *
	 * A PHP error here would take the whole endpoint down and answer with a
	 * blank 500 page, which the script can only report as "Something went
	 * wrong" - the shopper is told nothing and cannot tell a sold out option
	 * from a broken shop. Catching Throwable keeps the answer in the shape
	 * the script expects, with WooCommerce's own notice where there is one.
	 */
	$added = false;

	try {
		// The same hook WooCommerce runs, so plugins that block a product still block it.
		if ( apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variation ) ) {
			$added = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );
		}
	} catch ( Throwable $e ) {
		$added = false;
	}

	if ( false === $added ) {
		wp_send_json_error( array( 'message' => techjossecom_cart_error_message() ) );
	}

	/*
	 * The add worked, so the "added to your cart" bar is the same news the
	 * drawer is about to tell. Dropping it here stops it resurfacing as a
	 * banner on whichever page the shopper visits next.
	 */
	wc_clear_notices();

	/*
	 * Server-side AddToCart. The event id the browser pixel used is sent
	 * with the request, so the pixel hit and this server hit dedupe into
	 * one conversion in Events Manager.
	 */
	$event_id = isset( $_POST['event_id'] ) ? sanitize_text_field( wp_unslash( $_POST['event_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked above.
	techjossecom_track_add_to_cart_server( $product_id, $quantity, $event_id );

	wp_send_json_success(
		array(
			'fragments' => techjossecom_cart_fragments( array() ),
			'quantity'  => WC()->cart->get_cart_contents_count(),
		)
	);
}
add_action( 'wp_ajax_techjossecom_add_to_cart', 'techjossecom_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_techjossecom_add_to_cart', 'techjossecom_ajax_add_to_cart' );

/**
 * AJAX: change a line in the mini cart over AJAX: remove it, or set a new count.
 *
 * WooCommerce has endpoints for adding to the cart and for removing a single
 * line, but none for changing the quantity of a line in place, so the drawer
 * needs one of its own to offer a stepper. It also lets a single request clean
 * up every cart key behind one row, which matters because the drawer folds
 * repeat adds of a product into a single row.
 *
 * The response carries the same cart fragments the header count is built from,
 * so the badge, the drawer body and the totals all refresh together.
 *
 * @return void
 */
function techjossecom_ajax_cart_update() {
	check_ajax_referer( 'techjossecom_cart', 'nonce' );

	if ( ! techjossecom_has_woocommerce() ) {
		wp_send_json_error( array( 'message' => __( 'The cart is not available.', 'techjossecom' ) ) );
	}

	$raw_keys = isset( $_POST['cart_item_keys'] ) ? wp_unslash( $_POST['cart_item_keys'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised below.
	$raw_keys = is_array( $raw_keys ) ? $raw_keys : explode( ',', (string) $raw_keys );

	$keys = array();

	foreach ( $raw_keys as $raw_key ) {
		$key = sanitize_text_field( (string) $raw_key );

		if ( '' !== $key ) {
			$keys[] = $key;
		}
	}

	$cart     = WC()->cart;
	$quantity = isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : 0; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- run through wc_stock_amount().
	$quantity = max( 0, (int) $quantity );

	/*
	 * Keep only keys that are still in the cart. Two tabs on one session, or a
	 * double click, can leave a key behind, and set_quantity() writes straight
	 * into the cart contents array, so acting on a stale key would create a
	 * broken line.
	 */
	$keys = array_values(
		array_filter(
			$keys,
			static function ( $key ) use ( $cart ) {
				return (bool) $cart->get_cart_item( $key );
			}
		)
	);

	if ( ! $keys ) {
		wp_send_json_error( array( 'message' => __( 'That item is no longer in the cart.', 'techjossecom' ) ) );
	}

	/*
	 * A row can stand for several cart keys, so all of them are acted on. On a
	 * count change the requested total is applied to the first key and the rest
	 * are dropped, which is what collapses the duplicates back into one line
	 * rather than leaving the strays in the session.
	 */
	$primary = array_shift( $keys );

	foreach ( $keys as $key ) {
		$cart->remove_cart_item( $key );
	}

	if ( $quantity > 0 ) {
		$cart->set_quantity( $primary, $quantity, true );
	} else {
		$cart->remove_cart_item( $primary );
	}

	$cart->calculate_totals();

	/*
	 * The drawer is the feedback for this action, so anything WooCommerce
	 * queued on the way through is dropped instead of being left to surface as
	 * a banner on the next page the shopper visits.
	 */
	wc_clear_notices();

	wp_send_json_success(
		array(
			'fragments' => techjossecom_cart_fragments( array() ),
			'quantity'  => $cart->get_cart_contents_count(),
		)
	);
}
add_action( 'wp_ajax_techjossecom_cart_update', 'techjossecom_ajax_cart_update' );
add_action( 'wp_ajax_nopriv_techjossecom_cart_update', 'techjossecom_ajax_cart_update' );
