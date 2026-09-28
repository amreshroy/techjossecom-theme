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
 * Available shipping options for a destination, used by the COD popup.
 *
 * @param int $product_id Optional product used to build the package contents.
 * @param int $quantity   Quantity of the product.
 * @return array List of array( id, method_id, instance_id, label, cost ).
 */
function techjossecom_get_shipping_options( $product_id = 0, $quantity = 1 ) {
	if ( ! class_exists( 'WooCommerce' ) || ! WC()->shipping ) {
		return array();
	}

	$base    = wc_get_base_location();
	$country = '';

	if ( WC()->customer ) {
		$country = WC()->customer->get_shipping_country();
	}

	if ( ! $country ) {
		$country = isset( $base['country'] ) ? $base['country'] : 'BD';
	}

	$package = array(
		'destination'     => array(
			'country'  => $country,
			'state'    => isset( $base['state'] ) ? $base['state'] : '',
			'postcode' => '',
		),
		'contents'        => array(),
		'contents_cost'   => 0,
		'cart_subtotal'   => 0,
		'applied_coupons' => array(),
		'user'            => array( 'ID' => get_current_user_id() ),
	);

	$quantity = max( 1, absint( $quantity ) );

	if ( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( $product ) {
			$line_total = (float) $product->get_price() * $quantity;

			$package['contents'][ $product_id ] = array(
				'product_id'        => $product->get_id(),
				'variation_id'      => 0,
				'variation'         => array(),
				'quantity'          => $quantity,
				'data'              => $product,
				'line_total'        => $line_total,
				'line_subtotal'     => $line_total,
				'line_tax'          => 0,
				'line_subtotal_tax' => 0,
			);

			$package['contents_cost'] = $line_total;
			$package['cart_subtotal'] = $line_total;
		}
	}

	$zone    = WC_Shipping_Zones::get_zone_matching_package( $package );
	$options = array();

	if ( ! $zone ) {
		return $options;
	}

	foreach ( $zone->get_shipping_methods( true ) as $method ) {
		if ( isset( $method->enabled ) && 'yes' !== $method->enabled ) {
			continue;
		}

		if ( ! method_exists( $method, 'calculate_shipping' ) ) {
			continue;
		}

		$method->calculate_shipping( $package );

		if ( empty( $method->rates ) || ! is_array( $method->rates ) ) {
			continue;
		}

		foreach ( $method->rates as $rate ) {
			if ( ! $rate instanceof WC_Shipping_Rate ) {
				continue;
			}

			$options[] = array(
				'id'          => $rate->get_id(),
				'method_id'   => $rate->get_method_id(),
				'instance_id' => method_exists( $rate, 'get_instance_id' ) ? (int) $rate->get_instance_id() : 0,
				'label'       => $rate->get_label(),
				'cost'        => (float) $rate->get_cost(),
			);
		}
	}

	/**
	 * Filter the shipping options offered in the COD popup.
	 *
	 * @param array $options    Shipping options.
	 * @param array $package    Package used for the calculation.
	 * @param int   $product_id Product ID.
	 */
	return apply_filters( 'techjossecom_shipping_options', $options, $package, $product_id );
}

/**
 * AJAX: return the shipping options for the COD popup.
 *
 * @return void
 */
function techjossecom_ajax_shipping_options() {
	check_ajax_referer( 'techjossecom_cod', 'nonce' );

	$product_id = isset( $_REQUEST['product_id'] ) ? absint( $_REQUEST['product_id'] ) : 0;
	$quantity   = isset( $_REQUEST['quantity'] ) ? absint( $_REQUEST['quantity'] ) : 1;

	$options = techjossecom_get_shipping_options( $product_id, $quantity );

	wp_send_json_success(
		array(
			'options' => $options,
			'notice'  => $options ? '' : __( 'Shipping cost will be confirmed over the phone.', 'techjossecom' ),
		)
	);
}
add_action( 'wp_ajax_techjossecom_shipping', 'techjossecom_ajax_shipping_options' );
add_action( 'wp_ajax_nopriv_techjossecom_shipping', 'techjossecom_ajax_shipping_options' );
/**
 * Create a WooCommerce order from the quick order popup.
 *
 * @param array $data Sanitized request data.
 * @return WC_Order|WP_Error
 */
function techjossecom_create_cod_order( $data ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return new WP_Error( 'no_woocommerce', __( 'The shop is not available right now.', 'techjossecom' ) );
	}

	$product_id = isset( $data['product_id'] ) ? absint( $data['product_id'] ) : 0;
	$quantity   = isset( $data['quantity'] ) ? max( 1, absint( $data['quantity'] ) ) : 1;
	$product    = $product_id ? wc_get_product( $product_id ) : false;

	if ( ! $product || ! $product->is_purchasable() ) {
		return new WP_Error( 'invalid_product', __( 'This product is not available.', 'techjossecom' ) );
	}

	$item_product   = $product;
	$variation_data = array();

	if ( $product->is_type( 'variable' ) ) {
		$variation_id = isset( $data['variation_id'] ) ? absint( $data['variation_id'] ) : 0;
		$variation    = $variation_id ? wc_get_product( $variation_id ) : false;

		if ( ! $variation || (int) $variation->get_parent_id() !== (int) $product->get_id() ) {
			return new WP_Error( 'choose_variation', __( 'Please choose a product option.', 'techjossecom' ) );
		}

		if ( ! $variation->is_purchasable() || ! $variation->is_in_stock() ) {
			return new WP_Error( 'not_purchasable', __( 'This option is out of stock.', 'techjossecom' ) );
		}

		$item_product   = $variation;
		$variation_data = function_exists( 'wc_get_product_variation_attributes' )
			? wc_get_product_variation_attributes( $variation_id )
			: array();
	}

	if ( ! $item_product->is_in_stock() ) {
		return new WP_Error( 'out_of_stock', __( 'This product is out of stock.', 'techjossecom' ) );
	}

	if ( $item_product->managing_stock() && ! $item_product->has_enough_stock( $quantity ) ) {
		/* translators: %s: available quantity. */
		return new WP_Error(
			'no_stock',
			sprintf( __( 'Only %s left in stock.', 'techjossecom' ), wc_format_decimal( $item_product->get_stock_quantity() ) )
		);
	}

	$order = wc_create_order(
		array(
			'created_via' => 'techjossecom_cod',
			'status'      => 'pending',
		)
	);

	if ( is_wp_error( $order ) ) {
		return $order;
	}

	$item = $order->add_product( $item_product, $quantity, array( 'variation' => $variation_data ) );

	if ( ! $item ) {
		$order->delete( true );

		return new WP_Error( 'add_item_failed', __( 'The product could not be added to the order.', 'techjossecom' ) );
	}

	// Shipping line.
	$shipping_cost = isset( $data['shipping_cost'] ) ? (float) $data['shipping_cost'] : 0;
	$shipping_name = isset( $data['shipping_label'] ) ? (string) $data['shipping_label'] : '';

	if ( '' !== $shipping_name || $shipping_cost > 0 ) {
		if ( class_exists( 'WC_Order_Item_Shipping' ) ) {
			$shipping_item = new WC_Order_Item_Shipping();

			$shipping_item->set_method_title( $shipping_name ? $shipping_name : __( 'Shipping', 'techjossecom' ) );
			$shipping_item->set_method_id( ! empty( $data['shipping_method_id'] ) ? $data['shipping_method_id'] : 'flat_rate' );

			if ( ! empty( $data['shipping_instance_id'] ) ) {
				$shipping_item->set_instance_id( absint( $data['shipping_instance_id'] ) );
			}

			$shipping_item->set_total( $shipping_cost );
			$order->add_item( $shipping_item );
		}
	}

	// Customer details.
	$name    = isset( $data['name'] ) ? $data['name'] : '';
	$parts   = preg_split( '/\s+/', trim( $name ) );
	$parts   = is_array( $parts ) && $parts ? $parts : array( '' );
	$last    = count( $parts ) > 1 ? array_pop( $parts ) : '';
	$first   = implode( ' ', $parts );
	$base    = wc_get_base_location();
	$country = isset( $base['country'] ) && $base['country'] ? $base['country'] : 'BD';

	$fields = array(
		'first_name' => $first,
		'last_name'  => $last,
		'address_1'  => isset( $data['address'] ) ? $data['address'] : '',
		'city'       => isset( $data['city'] ) ? $data['city'] : '',
		'state'      => isset( $base['state'] ) ? $base['state'] : '',
		'country'    => $country,
		'phone'      => isset( $data['phone'] ) ? $data['phone'] : '',
	);

	if ( ! empty( $data['email'] ) ) {
		$fields['email'] = $data['email'];
	}

	$order->set_address( $fields, 'billing' );
	$order->set_address( $fields, 'shipping' );

	if ( ! empty( $data['note'] ) ) {
		$order->set_customer_note( $data['note'] );
	}

	// Payment method.
	$gateways = ( WC()->payment_gateways ) ? WC()->payment_gateways->get_available_payment_gateways() : array();

	if ( isset( $gateways['cod'] ) ) {
		$order->set_payment_method( $gateways['cod'] );
	} else {
		$order->set_payment_method( 'cod' );
		$order->set_payment_method_title( __( 'Cash on delivery', 'techjossecom' ) );
	}

	if ( is_user_logged_in() ) {
		$order->set_customer_id( get_current_user_id() );
	}

	$order->update_meta_data( '_tj_quick_order', 'yes' );

	$order->calculate_totals();
	$order->save();

	/**
	 * Filter the status given to a quick COD order.
	 *
	 * @param string   $status Order status without the "wc-" prefix.
	 * @param WC_Order $order  Order object.
	 */
	$status = apply_filters( 'techjossecom_cod_order_status', 'processing', $order );
	$status = str_replace( 'wc-', '', $status );

	$order->update_status( $status, __( 'Quick cash on delivery order placed from the product page.', 'techjossecom' ) );

	return $order;
}
/**
 * AJAX: place a cash on delivery order from the quick order popup.
 *
 * @return void
 */
function techjossecom_ajax_cod_order() {
	check_ajax_referer( 'techjossecom_cod', 'nonce' );

	if ( ! techjossecom_mod( 'enable_cod' ) ) {
		wp_send_json_error( array( 'message' => __( 'Cash on delivery ordering is disabled.', 'techjossecom' ) ) );
	}

	// Honeypot: bots happily fill in hidden inputs.
	if ( ! empty( $_POST['tj_hp'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Something went wrong. Please try again.', 'techjossecom' ) ) );
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'The shop is not available right now.', 'techjossecom' ) ) );
	}

	// Simple flood protection: one order every 20 seconds per visitor.
	$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$throttle = 'tj_cod_' . md5( $ip . '|' . get_current_user_id() );

	if ( get_transient( $throttle ) ) {
		wp_send_json_error( array( 'message' => __( 'Please wait a moment before placing another order.', 'techjossecom' ) ) );
	}

	$data = array(
		'product_id'         => isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0,
		'variation_id'       => isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0,
		'quantity'           => isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1,
		'name'               => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
		'phone'              => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
		'address'            => isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '',
		'city'               => isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '',
		'note'               => isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '',
		'email'              => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'shipping_label'     => isset( $_POST['shipping_label'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_label'] ) ) : '',
		'shipping_cost'      => isset( $_POST['shipping_cost'] ) ? (float) $_POST['shipping_cost'] : 0,
		'shipping_method_id' => isset( $_POST['shipping_method_id'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_method_id'] ) ) : '',
		'shipping_instance_id' => isset( $_POST['shipping_instance_id'] ) ? absint( $_POST['shipping_instance_id'] ) : 0,
	);

	if ( '' === $data['name'] || '' === $data['phone'] || '' === $data['address'] ) {
		wp_send_json_error( array( 'message' => __( 'Please fill in your name, mobile number and address.', 'techjossecom' ) ) );
	}

	$digits = preg_replace( '/[^0-9]/', '', $data['phone'] );

	if ( strlen( $digits ) < 6 ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid mobile number.', 'techjossecom' ) ) );
	}

	/**
	 * Filter the sanitized quick order data before the order is created.
	 *
	 * @param array $data Request data.
	 */
	$data = apply_filters( 'techjossecom_cod_order_data', $data );

	$order = techjossecom_create_cod_order( $data );

	if ( is_wp_error( $order ) ) {
		wp_send_json_error( array( 'message' => $order->get_error_message() ) );
	}

	set_transient( $throttle, 1, 20 );

	$message = techjossecom_mod( 'cod_success_message' );

	if ( ! $message ) {
		$message = __( 'Thank you! Your order has been received.', 'techjossecom' );
	}

	wp_send_json_success(
		array(
			'order_id'  => $order->get_id(),
			'order_key' => $order->get_order_key(),
			'message'   => $message,
			'total'     => wp_strip_all_tags( $order->get_formatted_order_total() ),
			'redirect'  => $order->get_checkout_order_received_url(),
		)
	);
}
add_action( 'wp_ajax_techjossecom_cod_order', 'techjossecom_ajax_cod_order' );
add_action( 'wp_ajax_nopriv_techjossecom_cod_order', 'techjossecom_ajax_cod_order' );

/**
 * Shorten the WooCommerce thank you message for quick orders.
 *
 * @param string   $message Default text.
 * @param WC_Order $order   Order object.
 * @return string
 */
function techjossecom_thankyou_message( $message, $order ) {
	if ( $order instanceof WC_Order && 'yes' === $order->get_meta( '_tj_quick_order' ) ) {
		$custom = techjossecom_mod( 'cod_success_message' );

		if ( $custom ) {
			return $custom . ' ' . __( 'Our team will call you shortly to confirm the delivery.', 'techjossecom' );
		}
	}

	return $message;
}
add_filter( 'woocommerce_thankyou_order_received_text', 'techjossecom_thankyou_message', 10, 2 );



