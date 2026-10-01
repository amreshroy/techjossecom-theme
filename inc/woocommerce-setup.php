<?php
/**
 * WooCommerce integration.
 *
 * Everything in this file is safe to load when WooCommerce is inactive: hooks
 * are only registered when the plugin is present, and every callback starts by
 * verifying the WooCommerce functions it needs.
 *
 * @package TechJosse_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * True when WooCommerce is active and its cart is available.
 *
 * @return bool
 */
function techjossecom_has_woocommerce() {
	return class_exists( 'WooCommerce' ) && function_exists( 'WC' ) && WC()->cart;
}

/**
 * Shorter helper for the shop page permalink.
 *
 * @return string
 */
function techjossecom_shop_url() {
	if ( class_exists( 'WooCommerce' ) ) {
		$page_id = wc_get_page_id( 'shop' );

		if ( $page_id > 0 ) {
			return get_permalink( $page_id );
		}
	}

	return home_url( '/' );
}

/**
 * Fold cart lines that are genuinely the same item into one.
 *
 * WooCommerce keys a line on the product, the variation and the attribute
 * values, so an add that arrives with a slightly different payload - a
 * different attribute order, an extra piece of cart item data from a plugin -
 * produces a second line instead of raising the quantity on the first. The
 * shopper then sees the same product twice and no way to tell the rows apart.
 *
 * This walks the cart and sums any lines that resolve to the same signature
 * into the first of them, so the stored cart holds one line and the quantity
 * simply goes up. It matches WooCommerce's own definition of a line, so a
 * difference another plugin deliberately stored still keeps its own row.
 *
 * Only ever acts when there is something to merge, so the common case costs a
 * single count() and nothing is written.
 *
 * @return void
 */
function techjossecom_merge_duplicate_cart_items() {
	static $done = false;

	if ( $done || ! techjossecom_has_woocommerce() ) {
		return;
	}

	$done = true;

	$cart = WC()->cart;

	if ( ! $cart ) {
		return;
	}

	$contents = $cart->get_cart();

	if ( count( $contents ) < 2 ) {
		return;
	}

	$seen  = array();
	$merge = array();

	foreach ( $contents as $cart_item_key => $cart_item ) {
		$variation = isset( $cart_item['variation'] ) && is_array( $cart_item['variation'] ) ? $cart_item['variation'] : array();

		// Sorted, so the same attributes in a different order hash the same.
		ksort( $variation );

		$data = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		$sku  = $data && is_callable( array( $data, 'get_sku' ) ) ? $data->get_sku() : '';

		$signature = md5(
			wp_json_encode(
				array(
					(int) ( isset( $cart_item['product_id'] ) ? $cart_item['product_id'] : 0 ),
					(int) ( isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : 0 ),
					$variation,
					(string) $sku,
				)
			)
		);

		if ( ! isset( $seen[ $signature ] ) ) {
			$seen[ $signature ] = array(
				'key'      => $cart_item_key,
				'quantity' => (int) $cart_item['quantity'],
			);

			continue;
		}

		$seen[ $signature ]['quantity'] += (int) $cart_item['quantity'];
		$merge[] = $cart_item_key;
	}

	if ( ! $merge ) {
		return;
	}

	foreach ( $merge as $cart_item_key ) {
		$cart->remove_cart_item( $cart_item_key );
	}

	foreach ( $seen as $line ) {
		$item = $cart->get_cart_item( $line['key'] );

		if ( $item && (int) $item['quantity'] !== $line['quantity'] ) {
			$cart->set_quantity( $line['key'], $line['quantity'], false );
		}
	}

	$cart->calculate_totals();
}

/**
 * Render one part of the slide-in mini cart.
 *
 * Used both by the drawer template and by the WooCommerce cart fragments, so
 * the markup can never drift apart.
 *
 * @param string $part One of: count, body, footer.
 * @return string
 */
function techjossecom_mini_cart_part( $part ) {
	if ( ! techjossecom_has_woocommerce() ) {
		return '';
	}

	$cart  = WC()->cart;
	$count = $cart->get_cart_contents_count();

	if ( 'count' === $part ) {
		return sprintf(
			'<span id="tj-cart-count" class="tj-cart-count%2$s">%1$s</span>',
			esc_html( $count ),
			$count > 0 ? ' is-filled' : ''
		);
	}

	if ( 'body' === $part ) {
		ob_start();

		/*
		 * Tidy the stored cart before it is read, so what is drawn is what the
		 * cart page, the checkout and the order will all use. Without this a
		 * duplicate that slipped in at add time would be hidden here but still
		 * sitting in the cart as two lines.
		 */
		techjossecom_merge_duplicate_cart_items();

		echo '<div id="tj-mini-cart-body" class="tj-drawer-body">';

		if ( $cart->is_empty() ) {
			echo '<div class="tj-cart-empty">';
			echo '<p>' . esc_html__( 'Your cart is empty.', 'techjossecom' ) . '</p>';
			printf(
				'<a class="tj-btn tj-btn--primary" href="%s">%s</a>',
				esc_url( techjossecom_shop_url() ),
				esc_html__( 'Continue shopping', 'techjossecom' )
			);
			echo '</div>';
		} else {
			/*
			 * WooCommerce keys a cart line on the product, the variation and the
			 * chosen attributes, so the same product added more than once can be
			 * spread over several lines. A shopper reads this drawer as a list of
			 * products, never as a list of keys, so the lines are folded together
			 * here: one row per product and variation, carrying the summed
			 * quantity.
			 *
			 * The row keeps every cart key behind it in data-tj-cart-keys, so
			 * removing it or changing the count tidies up all of the copies in
			 * one request instead of leaving the strays behind.
			 */
			$lines = array();

			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				$product = isset( $cart_item['data'] ) ? $cart_item['data'] : false;

				if ( ! $product || ! $product->exists() || $cart_item['quantity'] <= 0 ) {
					continue;
				}

				$variation_id = ! empty( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
				$group        = $variation_id ? 'v' . $variation_id : 'p' . (int) $cart_item['product_id'];

				if ( ! isset( $lines[ $group ] ) ) {
					$lines[ $group ] = array(
						'product'   => $product,
						'keys'      => array(),
						'quantity'  => 0,
						'variation' => array(),
					);
				}

				$lines[ $group ]['keys'][]     = $cart_item_key;
				$lines[ $group ]['quantity'] += (int) $cart_item['quantity'];

				/*
				 * The attribute values the shopper actually chose, which is not
				 * the same thing as the variation's own attributes.
				 *
				 * A variation that leaves an attribute open - an "Any" value -
				 * stores an empty string for it, so reading the variation back
				 * would drop that option from the row and leave two lines
				 * looking identical. The cart records what was picked, so that
				 * is what the row is labelled with.
				 */
				if ( empty( $lines[ $group ]['variation'] ) && ! empty( $cart_item['variation'] ) && is_array( $cart_item['variation'] ) ) {
					$lines[ $group ]['variation'] = $cart_item['variation'];
				}
			}

			echo '<ul class="tj-cart-list">';

			foreach ( $lines as $line ) {
				$product   = $line['product'];
				$quantity  = (int) $line['quantity'];
				$keys      = implode( ',', $line['keys'] );
				$max       = (int) $product->get_max_purchase_quantity();
				$sku       = $product->get_sku();
				$thumbnail = $product->get_image( 'thumbnail', array( 'loading' => 'lazy' ) );

				/*
				 * What makes this row different from a lookalike. A variation
				 * whose title is inherited from its parent reads as the bare
				 * product name, so two lines for a size M and a size L end up
				 * with identical text and no way for the shopper to tell them
				 * apart. The chosen attributes say which one this is.
				 */
				$meta = array();

				if ( $sku ) {
					$meta[] = esc_html__( 'SKU:', 'techjossecom' ) . ' <span>' . esc_html( $sku ) . '</span>';
				}

				/*
				 * The options this copy was ordered with, as badges beside the
				 * title: Color Red, Size M, Weight 500 g.
				 *
				 * Read from the cart line rather than from the variation, and
				 * rendered one badge at a time rather than as one joined
				 * string. A variation that leaves an attribute open stores an
				 * empty value for it, which is exactly the option the shopper
				 * picked and the one most needs naming, so the cart's record of
				 * the choice is what is shown.
				 *
				 * The colon belongs to the label rather than to the CSS. The
				 * gap that separates the name from the value cannot stand in
				 * for punctuation: "Size M" beside "Color Green" leaves the
				 * shopper pairing the halves up by eye, while "Size: M" says
				 * on its own that the first word names the option.
				 */
				$attributes = array();

				if ( ! empty( $line['variation'] ) && is_array( $line['variation'] ) ) {
					foreach ( $line['variation'] as $name => $value ) {
						$taxonomy = str_replace( 'attribute_', '', $name );
						$value    = (string) $value;

						if ( '' === $value ) {
							continue;
						}

						// Turn the slug a shopper never sees, "m", into "M".
						if ( taxonomy_exists( $taxonomy ) ) {
							$term = get_term_by( 'slug', $value, $taxonomy );

							if ( ! is_wp_error( $term ) && $term && null !== $term->name && '' !== $term->name ) {
								$value = $term->name;
							}
						}

						$attributes[] = sprintf(
							'<span class="tj-cart-item-attr"><span class="tj-cart-item-attr-name">%1$s</span><span class="tj-cart-item-attr-value">%2$s</span></span>',
							esc_html( wc_attribute_label( $taxonomy, $product ) . ':' ),
							esc_html( rawurldecode( $value ) )
						);
					}
				}

				if ( ! $attributes && $product->is_type( 'variation' ) ) {
					// Added by a link rather than the form, so nothing was
					// recorded: fall back to whatever the variation itself holds.
					$formatted = wc_get_formatted_variation( $product, true, true, true );

					if ( $formatted ) {
						$attributes[] = '<span class="tj-cart-item-attr">' . esc_html( $formatted ) . '</span>';
					}
				}

				printf(
					'<li class="tj-cart-item" data-tj-cart-item data-tj-cart-keys="%1$s" data-tj-cart-qty="%2$d" data-tj-cart-max="%3$d">',
					esc_attr( $keys ),
					$quantity,
					$max
				);

				echo '<a class="tj-cart-item-thumb" href="' . esc_url( $product->get_permalink() ) . '">';
				echo techjossecom_sale_badge( $product, 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				echo '<div class="tj-cart-item-info">';

				/*
				 * Name and chosen options sit together on one line that wraps,
				 * so "Red" and "M" read as part of what was ordered rather than
				 * as a note underneath it. The badges come after the name, which
				 * is what a shopper looks at first.
				 */
				echo '<div class="tj-cart-item-heading">';
				printf(
					'<a class="tj-cart-item-title" href="%1$s">%2$s</a>',
					esc_url( $product->get_permalink() ),
					esc_html( $product->get_name() )
				);

				if ( $attributes ) {
					printf(
						'<span class="tj-cart-item-attrs">%s</span>',
						implode( '', $attributes ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part is escaped above.
					);
				}

				echo '</div>';

				// With nothing to tell it apart by, a row simply carries no meta
				// line rather than an empty label the shopper would puzzle over.
				if ( $meta ) {
					printf(
						'<p class="tj-cart-item-meta">%s</p>',
						implode( '<span class="tj-cart-item-meta-sep">&middot;</span>', $meta ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each part is escaped above.
					);
				}

				/*
				 * The stepper stops at one rather than removing the row: taking
				 * the last copy away is what the cross is for, and a control that
				 * silently deleted the product would be a trap. The same goes
				 * for the top of the range, which is the stock ceiling when the
				 * product is managed.
				 */
				printf(
					'<div class="tj-cart-qty">
						<button type="button" class="tj-cart-qty-btn" data-tj-cart-step="-1" aria-label="%1$s"%2$s>&minus;</button>
						<span class="tj-cart-qty-value" data-tj-cart-qty-value>%3$s</span>
						<button type="button" class="tj-cart-qty-btn" data-tj-cart-step="1" aria-label="%4$s"%5$s>+</button>
					</div>',
					esc_attr__( 'Decrease the quantity', 'techjossecom' ),
					$quantity <= 1 ? ' disabled' : '',
					esc_html( $quantity ),
					esc_attr__( 'Increase the quantity', 'techjossecom' ),
					$max > 0 && $quantity >= $max ? ' disabled' : ''
				);

				/*
					* The row reads "2 x 155": the multiplier and the price of a
					* single copy, so the number beside the "2" is the same
					* price the shopper was shown on the product page and does
					* not move when they change the count. The multiplied
					* amount is not lost - it is the Subtotal under the list,
					* and it moves with every row.
					*
					* This used to ask get_product_subtotal() for the price,
					* which multiplies by the quantity, so each press of the
					* "+" doubled the unit price too: a 155 shirt at two
					* copies was drawn as "2 x 310" and four copies as
					* "4 x 620", which reads as the product having become
					* dearer. Only the subtotal should carry the count.
					*
					* The copy is priced at one rather than taken from
					* get_price() directly so a store displaying prices with
					* tax keeps doing so, and the usual filters still run.
					*/
				printf(
					'<p class="tj-cart-item-total"><span class="tj-cart-item-multiple">%1$s&times;</span>%2$s</p>',
					esc_html( $quantity ),
					wp_kses_post( wc_price( wc_get_price_to_display( $product, array( 'qty' => 1 ) ) ) )
				);

				echo '</div>';

				/*
					* A button, not a link: the drawer is only reachable once the script has
					* opened it, so there is no no-script path to fall back to, and a
					* control that never navigates should not be a link.
					*/
				printf(
					'<button type="button" class="tj-cart-item-remove" data-tj-cart-remove data-tj-cart-keys="%1$s" aria-label="%2$s">&times;</button>',
					esc_attr( $keys ),
					esc_attr__( 'Remove this item', 'techjossecom' )
				);

				echo '</li>';
			}

			echo '</ul>';
		}

		echo '</div>';

		return ob_get_clean();
	}

	if ( 'total' === $part ) {
		$total = $cart->is_empty() ? wc_price( 0 ) : $cart->get_cart_subtotal();

		return sprintf(
			'<span id="tj-cart-total" class="tj-cart-total">%s</span>',
			wp_kses_post( $total )
		);
	}

	if ( 'footer' === $part ) {
		ob_start();

		echo '<div id="tj-mini-cart-footer" class="tj-drawer-footer">';

		if ( ! $cart->is_empty() ) {
			echo '<p class="tj-cart-subtotal"><span>' . esc_html__( 'Subtotal', 'techjossecom' ) . '</span>' . wp_kses_post( $cart->get_cart_subtotal() ) . '</p>';
			echo '<p class="tj-cart-note">' . esc_html__( 'Cash on delivery available all over Bangladesh.', 'techjossecom' ) . '</p>';
			echo '<div class="tj-cart-actions">';
			printf(
				'<a class="tj-btn tj-btn--ghost" href="%s">%s</a>',
				esc_url( wc_get_cart_url() ),
				esc_html__( 'View cart', 'techjossecom' )
			);
			printf(
				'<a class="tj-btn tj-btn--primary" href="%s">%s</a>',
				esc_url( wc_get_checkout_url() ),
				esc_html__( 'Checkout', 'techjossecom' )
			);
			echo '</div>';
		}

		echo '</div>';

		return ob_get_clean();
	}

	return '';
}

/**
 * Keep the mini cart in sync after every AJAX add / remove to cart.
 *
 * @param array $fragments Cart fragments keyed by CSS selector.
 * @return array
 */
function techjossecom_cart_fragments( $fragments ) {
	$fragments['#tj-cart-count']        = techjossecom_mini_cart_part( 'count' );
	$fragments['#tj-cart-total']        = techjossecom_mini_cart_part( 'total' );
	$fragments['#tj-mini-cart-body']    = techjossecom_mini_cart_part( 'body' );
	$fragments['#tj-mini-cart-footer']  = techjossecom_mini_cart_part( 'footer' );

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'techjossecom_cart_fragments' );

/**
 * Drop WooCommerce's own cart banners on the single product page.
 *
 * Adds and removals both go over AJAX and the mini cart drawer is opened
 * straight away, so the "… has been added to your cart. View cart" bar and the
 * "… removed. Undo?" bar are the same news told twice, and a removal made on
 * the cart page would otherwise still be waiting in the session to appear here.
 *
 * Errors are deliberately left alone. A rejected add, a sold out size or an
 * out of stock warning has to reach the shopper in writing, because the drawer
 * only opens on success and would otherwise swallow it silently.
 *
 * @return void
 */
function techjossecom_suppress_add_to_cart_notice() {
	if ( ! techjossecom_has_woocommerce() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$notices = WC()->session->get( 'wc_notices', array() );

	if ( empty( $notices ) ) {
		return;
	}

	/*
	 * A cart notice always carries a link out to the cart: "View cart" after an
	 * add, the undo link after a removal. Matching on those is what separates a
	 * cart banner from a notice about something else that happens to share the
	 * success type, and it survives translation.
	 */
	foreach ( array( 'success', 'notice' ) as $type ) {
		if ( empty( $notices[ $type ] ) ) {
			continue;
		}

		foreach ( $notices[ $type ] as $index => $notice ) {
			$message = is_array( $notice ) && isset( $notice['notice'] ) ? $notice['notice'] : $notice;

			if ( false !== strpos( $message, 'wc-forward' ) || false !== strpos( $message, 'restore-item' ) ) {
				unset( $notices[ $type ][ $index ] );
			}
		}

		if ( empty( $notices[ $type ] ) ) {
			unset( $notices[ $type ] );
		}
	}

	WC()->session->set( 'wc_notices', $notices );
}
add_action( 'woocommerce_before_single_product', 'techjossecom_suppress_add_to_cart_notice', 5 );
/**
 * Register the WooCommerce hooks.
 *
 * @return void
 */
function techjossecom_woocommerce_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	// The mini cart lives in the header, so the fragments script must always run.
	add_action(
		'wp_enqueue_scripts',
		function () {
			if ( wp_script_is( 'wc-cart-fragments', 'registered' ) ) {
				wp_enqueue_script( 'wc-cart-fragments' );
			}
		},
		30
	);

	// ---------------------------------------------------------------------
	// Layout: replace the default wrappers and breadcrumb with theme markup.
	// ---------------------------------------------------------------------
	remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
	remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

	add_action( 'woocommerce_before_main_content', 'techjossecom_woo_page_band', 5 );
	add_action( 'woocommerce_before_main_content', 'techjossecom_woo_wrapper_start', 10 );
	add_action( 'woocommerce_before_main_content', 'techjossecom_woo_breadcrumbs', 20 );
	add_action( 'woocommerce_after_main_content', 'techjossecom_woo_wrapper_end', 10 );
	add_filter( 'woocommerce_show_page_title', 'techjossecom_woo_hide_page_title' );
	add_action( 'woocommerce_before_shop_loop', 'techjossecom_shop_topbar_open', 15 );
	add_action( 'woocommerce_before_shop_loop', 'techjossecom_shop_topbar_close', 45 );

	// ---------------------------------------------------------------------
	// Shop loop.
	// The product card template builds its own links, so the default
	// "wrap everything in one <a>" behaviour is removed.
	// ---------------------------------------------------------------------
	remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );

	// Those two used to supply the anchor around the product title as well.
	// With them gone the title is inert text, so it is re-added scoped to the
	// title alone: the card keeps one link per control instead of one link
	// swallowing the whole card.
	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
	add_action( 'woocommerce_shop_loop_item_title', 'techjossecom_loop_product_title', 10 );

	add_filter( 'loop_shop_per_page', 'techjossecom_loop_per_page', 20 );
	add_filter( 'loop_shop_columns', 'techjossecom_loop_columns', 20 );
	add_filter( 'woocommerce_sale_flash', 'techjossecom_sale_flash', 10, 3 );
	add_action( 'woocommerce_after_shop_loop_item', 'techjossecom_loop_order_now', 20 );
	add_action( 'woocommerce_after_shop_loop_item_title', 'techjossecom_loop_stock_note', 15 );
	add_filter( 'woocommerce_product_add_to_cart_text', 'techjossecom_add_to_cart_text', 10, 2 );

	// ---------------------------------------------------------------------
	// The discount badge, site wide.

	//   One helper, techjossecom_sale_badge(), builds the markup. The
	//   woocommerce_sale_flash filter covers every WooCommerce loop for free:
	//   the shop archive, category pages, search results, the home page grids,
	//   related products, up-sells and cross-sells all call it.
	//
	//   The single product page is the exception. WooCommerce prints its sale
	//   flash on "woocommerce_before_single_product_summary" at priority 10,
	//   which is a *sibling* of the gallery rather than a child of it, so the
	//   badge lands in the gap above the photo and cannot be placed on the
	//   image with CSS.
	//
	//   It is removed and re-added on "woocommerce_product_thumbnails", which
	//   WooCommerce fires from inside the gallery wrapper, right after the
	//   product photo - the same nesting a card badge has, so one set of CSS
	//   positions both. That hook is unconditional in product-image.php, so a
	//   product with no gallery photos still gets the badge over its
	//   placeholder image, and no second copy is ever printed.
	// ---------------------------------------------------------------------
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
	add_action( 'woocommerce_product_thumbnails', 'techjossecom_single_sale_badge', 5 );

	// ---------------------------------------------------------------------
	// Single product.
	// ---------------------------------------------------------------------
	add_action( 'woocommerce_single_product_summary', 'techjossecom_single_categories', 4 );
	// Printed inside WooCommerce's variations form, immediately before the
	// <table class="variations"> that it replaces visually. The hook matters:
	// "woocommerce_before_add_to_cart_form" fires *outside* the <form> element,
	// so the chips would not be a sibling of the table and the CSS that hides
	// the table in favour of the chips would not match. The real <select>
	// elements stay in the markup; the script mirrors a chip click onto them.
	add_action( 'woocommerce_before_variations_form', 'techjossecom_single_variation_picker', 5 );

	/*
	 * "Order Now" belongs beside "Add to cart", not below it.
	 *
	 * It used to hang off woocommerce_single_product_summary, which fires
	 * after the add-to-cart form has closed, so the two controls could never
	 * share a row: the form laid out the quantity box and the add button,
	 * ended, and only then did the Order Now button appear underneath as a
	 * block of its own. That left two buttons of different widths stacked
	 * on top of each other - one tall strip each - for two actions a shopper
	 * weighs up side by side.
	 *
	 * woocommerce_after_add_to_cart_button fires inside the form, right
	 * after the add button, which puts both under one row of CSS. On a
	 * product with no add-to-cart form at all - an out of stock one, say -
	 * there is no such hook and no button either way, so nothing is lost.
	 */
	add_action( 'woocommerce_single_product_summary', 'techjossecom_single_order_now', 31 );
	add_action( 'woocommerce_after_add_to_cart_button', 'techjossecom_single_order_now' );
	add_action( 'woocommerce_single_product_summary', 'techjossecom_single_trust_badges', 35 );
	remove_action( 'woocommerce_share', 'woocommerce_share' );
	add_action( 'woocommerce_share', 'techjossecom_single_share' );
	add_filter( 'woocommerce_output_related_products_args', 'techjossecom_related_products_args', 20 );

	// ---------------------------------------------------------------------
	// Checkout: a checkout that works for cash on delivery customers.
	// ---------------------------------------------------------------------
	add_filter( 'woocommerce_checkout_fields', 'techjossecom_checkout_fields', 20 );
	add_action( 'woocommerce_review_order_before_payment', 'techjossecom_cod_notice' );
	add_filter( 'woocommerce_cross_sells_columns', 'techjossecom_cross_sells_columns' );

	// ---------------------------------------------------------------------
	// Cart and checkout order summaries: drop the "Add coupons" panel.
	// See techjossecom_remove_coupon_blocks() for the details.
	// ---------------------------------------------------------------------
	add_filter( 'render_block', 'techjossecom_remove_coupon_blocks', 10, 2 );
}
add_action( 'after_setup_theme', 'techjossecom_woocommerce_init', 20 );

/**
 * Full width title band shown above the shop and product category archives.
 *
 * Printed before `techjossecom_woo_wrapper_start()` so the band can span the
 * full width of the page, exactly like the design reference.
 *
 * @return void
 */
function techjossecom_woo_page_band() {
	if ( ! is_shop() && ! is_product_taxonomy() ) {
		return;
	}

	if ( is_shop() ) {
		$shop_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
		$title   = $shop_id > 0 ? get_the_title( $shop_id ) : __( 'Shop', 'techjossecom' );
	} else {
		$title = single_term_title( '', false );
	}

	echo '<div class="tj-page-band"><div class="tj-container">';
	printf( '<h1 class="tj-page-band-title">%s</h1>', esc_html( $title ) );
	techjossecom_breadcrumbs();
	echo '</div></div>';
}

/**
 * Hide the default WooCommerce archive title: the page band prints its own h1.
 *
 * @param bool $show Whether WooCommerce should print the title.
 * @return bool
 */
function techjossecom_woo_hide_page_title( $show ) {
	if ( is_shop() || is_product_taxonomy() ) {
		return false;
	}

	return $show;
}

/**
 * Open the result count / sorting bar of the shop archive.
 *
 * @return void
 */
function techjossecom_shop_topbar_open() {
	echo '<div class="tj-shop-topbar">';
}

/**
 * Close the result count / sorting bar of the shop archive.
 *
 * @return void
 */
function techjossecom_shop_topbar_close() {
	echo '</div>';
}

/**
 * Category links printed above the single product title.
 *
 * @return void
 */
function techjossecom_single_categories() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$list = wc_get_product_category_list( $product->get_id(), ', ' );

	if ( $list ) {
		printf( '<div class="tj-single-cats">%s</div>', wp_kses_post( $list ) );
	}
}

/**
 * Open the theme's shop wrapper markup.
 *
 * @return void
 */
function techjossecom_woo_wrapper_start() {
	echo '<main id="tj-main" class="tj-main tj-woo-main"><div class="tj-container">';

	if ( techjossecom_shop_has_sidebar() ) {
		echo '<div class="tj-layout tj-layout--sidebar"><div class="tj-layout-content">';
	}
}

/**
 * Close the theme's shop wrapper markup.
 *
 * @return void
 */
function techjossecom_woo_wrapper_end() {
	if ( techjossecom_shop_has_sidebar() ) {
		echo '</div>';
		echo '<aside class="tj-layout-sidebar tj-shop-sidebar">';
		dynamic_sidebar( 'shop-sidebar' );
		echo '</aside></div>';
	}

	echo '</div></main>';
}

/**
 * Whether the shop pages show the optional widget sidebar.
 *
 * @return bool
 */
function techjossecom_shop_has_sidebar() {
	if ( ! is_active_sidebar( 'shop-sidebar' ) ) {
		return false;
	}

	if ( class_exists( 'WooCommerce' ) && ( is_product() || is_cart() || is_checkout() || is_account_page() ) ) {
		return false;
	}

	return is_shop() || is_product_taxonomy();
}

/**
 * Theme breadcrumbs inside the shop wrapper.
 *
 * The shop and taxonomy archives print their trail inside the title band, so
 * they are skipped here. Every other WooCommerce view - the single product
 * page, the cart, the checkout and the account pages - prints it above the
 * content. The single product page used to be skipped as well, on the
 * assumption that a theme owned "single-product.php" template would render it
 * above the gallery. The theme ships no such override, so WooCommerce's own
 * template runs and the trail was never printed: the page advertised a
 * BreadcrumbList in its JSON-LD but showed no breadcrumb. Printing it from the
 * shared wrapper keeps the markup, the trail and the structured data in sync.
 *
 * @return void
 */
function techjossecom_woo_breadcrumbs() {
	if ( is_shop() || is_product_taxonomy() ) {
		return; // Printed inside the archive title band.
	}

	techjossecom_breadcrumbs();
}

/**
 * Products per page on the shop archive.
 *
 * @return int
 */
function techjossecom_loop_per_page() {
	return 12;
}

/**
 * Product columns on the shop archive.
 *
 * @return int
 */
function techjossecom_loop_columns() {
	return 4;
}

/**
 * Largest percentage discount available on a product, as a whole number.
 *
 * A simple product compares its own sale price against its regular price. A
 * variable product has no prices of its own, so every variation is checked and
 * the best (deepest) discount wins: that is the figure a shopper can actually
 * get, and it is what the badge on a card promises.
 *
 * @param WC_Product $product Product object.
 * @return int Percentage off, or 0 when the product has no usable discount.
 */
function techjossecom_discount_percent( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return 0;
	}

	$percents = array();

	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );

			if ( ! $child instanceof WC_Product ) {
				continue;
			}

			$percent = techjossecom_price_drop_percent( $child->get_regular_price(), $child->get_sale_price() );

			if ( $percent > 0 ) {
				$percents[] = $percent;
			}
		}
	} else {
		$percent = techjossecom_price_drop_percent( $product->get_regular_price(), $product->get_sale_price() );

		if ( $percent > 0 ) {
			$percents[] = $percent;
		}
	}

	return $percents ? max( $percents ) : 0;
}

/**
 * Percentage saved between two prices.
 *
 * @param string|float $regular Regular price.
 * @param string|float $sale    Sale price.
 * @return int Percentage off, 0 when the prices cannot produce a discount.
 */
function techjossecom_price_drop_percent( $regular, $sale ) {
	$regular = (float) $regular;
	$sale    = (float) $sale;

	// A free product (sale price 0), a product with no regular price to
	// compare against, or a "sale" that is not actually cheaper all produce
	// no meaningful percentage, so the caller falls back to a plain "Sale".
	if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
		return 0;
	}

	return (int) round( 100 - ( $sale / $regular * 100 ) );
}

/**
 * The one sale badge used everywhere a product is shown.
 *
 * The shop cards, the single product image, the live search results and the
 * mini cart all print this exact markup, so a discounted product looks the
 * same wherever the shopper meets it. Only the context changes the extra
 * class, never the shape.
 *
 * @param WC_Product|null $product Product object, defaults to the loop product.
 * @param string          $context Where the badge is printed: 'loop',
 *                                 'single', 'search' or 'cart'.
 * @return string Badge markup, or an empty string when there is nothing to show.
 */
function techjossecom_sale_badge( $product = null, $context = 'loop' ) {
	if ( ! $product instanceof WC_Product ) {
		global $product;
	}

	if ( ! $product instanceof WC_Product || ! $product->is_on_sale() ) {
		return '';
	}

	$percent = techjossecom_discount_percent( $product );
	$label   = $percent > 0 ? '-' . $percent . '%' : __( 'Sale', 'techjossecom' );

	return sprintf(
		'<span class="onsale tj-sale-badge tj-sale-badge--%1$s">%2$s</span>',
		esc_attr( $context ),
		esc_html( $label )
	);
}

/**
 * Print the sale badge, for hooks that expect output rather than a string.
 *
 * @param WC_Product|null $product Product object.
 * @param string          $context Where the badge is printed.
 * @return void
 */
function techjossecom_the_sale_badge( $product = null, $context = 'loop' ) {
	echo techjossecom_sale_badge( $product, $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Percentage sale badge.
 *
 * Wired to woocommerce_sale_flash so every WooCommerce loop - the shop
 * archive, category pages, search results, the home page grids, related
 * products, up-sells and cross-sells - picks the badge up automatically. The
 * work is done by techjossecom_sale_badge() so the single product page and the
 * mini cart render byte-identical markup.
 *
 * @param string     $html    Default badge markup.
 * @param WP_Post    $post    Product post.
 * @param WC_Product $product Product object.
 * @return string
 */
function techjossecom_sale_flash( $html, $post, $product ) {
	$badge = techjossecom_sale_badge( $product, 'loop' );

	return $badge ? $badge : $html;
}

/**
 * The discount badge over the main image on the single product page.
 *
 * WooCommerce prints its own sale flash on "woocommerce_before_single_product
 * _summary", which lands above the gallery instead of on it. That is removed
 * and the badge is printed inside the gallery wrapper instead, so it sits on
 * the product photo exactly like it does on a card.
 *
 * @return void
 */
function techjossecom_single_sale_badge() {
	global $product;

	techjossecom_the_sale_badge( $product instanceof WC_Product ? $product : null, 'single' );
}

/**
 * Render the "Order Now" (cash on delivery) button for a product.
 *
 * @param WC_Product|int|null $product Product object or ID.
 * @param string              $context Either 'loop' or 'single'.
 * @return void
 */
function techjossecom_order_now_button( $product = null, $context = 'loop' ) {
	if ( ! techjossecom_mod( 'enable_cod' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( ! $product instanceof WC_Product ) {
		global $product;
	}

	if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
		return;
	}

	$image_id   = $product->get_image_id();
	$image_url  = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
	$price      = $product->get_price();
	$variations = techjossecom_variation_data( $product );

	printf(
		'<button type="button" class="tj-btn tj-btn--cod tj-order-now tj-order-now--%6$s" data-product-id="%1$d" data-product-name="%2$s" data-product-price="%3$s" data-product-image="%4$s" data-product-type="%5$s" data-selected-variation=""%8$s>%7$s</button>',
		(int) $product->get_id(),
		esc_attr( $product->get_name() ),
		esc_attr( $price ? $price : '0' ),
		esc_url( $image_url ),
		esc_attr( $product->get_type() ),
		esc_attr( $context ),
		esc_html( techjossecom_mod( 'cod_button_text' ) ),
		$variations ? ' data-product-variations="' . esc_attr( wp_json_encode( $variations ) ) . '"' : ''
	);
}

/**
 * Map an attribute term slug onto a CSS colour, for the swatch buttons.
 *
 * Only the common colour names and literal hex values are resolved. Anything
 * else falls back to a neutral grey, which is still a readable swatch and never
 * invents a colour the shopkeeper did not set.
 *
 * @param string $slug Attribute term slug, for example "navy-blue".
 * @return string CSS colour.
 */
function techjossecom_swatch_color( $slug ) {
	$slug = strtolower( trim( (string) $slug ) );

	if ( '' === $slug ) {
		return '#9ca3af';
	}

	if ( preg_match( '/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', $slug, $match ) ) {
		$hex = $match[1];

		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return '#' . strtolower( $hex );
	}

	$named = array(
		'black'  => '#111111',
		'white'  => '#ffffff',
		'grey'   => '#8a8a8a',
		'gray'   => '#8a8a8a',
		'silver' => '#c0c0c0',
		'red'    => '#d92d20',
		'maroon' => '#7b1e28',
		'pink'   => '#ec4899',
		'rose'   => '#e11d48',
		'orange' => '#f97316',
		'gold'   => '#d4a017',
		'yellow' => '#facc15',
		'green'  => '#0a7d4f',
		'emerald'=> '#059669',
		'mint'   => '#98e2c6',
		'teal'   => '#0d9488',
		'navy'   => '#1e3a8a',
		'blue'   => '#2563eb',
		'sky'    => '#38bdf8',
		'purple' => '#7c3aed',
		'violet' => '#8b5cf6',
		'beige'  => '#e7dcc4',
		'cream'  => '#f5efdc',
		'brown'  => '#7b4b2a',
		'tan'    => '#c8a97e',
		'khaki'  => '#bdb76b',
	);

	// "navy-blue" and "light_green" both resolve through their last word.
	if ( isset( $named[ $slug ] ) ) {
		return $named[ $slug ];
	}

	$parts = preg_split( '/[\s_\-]+/', $slug );
	$last  = end( $parts );

	return isset( $named[ $last ] ) ? $named[ $last ] : '#9ca3af';
}

/**
 * Collect the purchasable variations of a variable product for the front end.
 *
 * Both the Size / Color chips and the cash on delivery button have to work out
 * which variation a shopper's choice points at, and they must never disagree
 * about it. Building the list once, here, is what guarantees that: the chips
 * carry it themselves, so they keep working when the cash on delivery button is
 * switched off, rather than reading it off a button that is not printed.
 *
 * @param WC_Product|null $product Product object.
 * @return array List of variations, empty for anything but a variable product.
 */
function techjossecom_variation_data( $product = null ) {
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) || ! method_exists( $product, 'get_available_variations' ) ) {
		return array();
	}

	$variations = array();

	foreach ( $product->get_available_variations() as $variation_data ) {
		$variation = wc_get_product( $variation_data['variation_id'] );

		if ( ! $variation instanceof WC_Product || ! $variation->is_purchasable() ) {
			continue;
		}

		// "attributes" maps "attribute_pa_size" => "l" for this variation.
		// The front end needs it to work out which variation a shopper's choice
		// points at, so it travels with the markup instead of being fetched
		// again on every click.
		$attributes = array();

		if ( ! empty( $variation_data['attributes'] ) && is_array( $variation_data['attributes'] ) ) {
			foreach ( $variation_data['attributes'] as $key => $value ) {
				$attributes[ (string) $key ] = (string) $value;
			}
		}

		$variations[] = array(
			'id'         => (int) $variation->get_id(),
			'label'      => wc_get_formatted_variation( $variation, true, true ),
			'price'      => (float) $variation->get_price(),
			'price_html' => wp_strip_all_tags( $variation->get_price_html() ),
			'in_stock'   => $variation->is_in_stock(),
			'attributes' => $attributes,
		);
	}

	return $variations;
}

/**
 * Render the Size / Color picker of a variable product.
 *
 * WooCommerce's own picker is a <table class="variations"> of <select> dropdowns,
 * which reads as a form rather than as a product option. This prints the same
 * choices as tappable chips and colour swatches instead, and is used on the
 * single product page only, inside WooCommerce's own variations form.
 *
 * The real <select> elements stay in the markup (they are what
 * add-to-cart-variation.js submits), the script mirrors a chip click onto them
 * and the table is hidden by CSS, so adding to the basket keeps working.
 *
 * Product cards deliberately do NOT get this picker: in a two column phone grid
 * the size row wrapped onto a second line and the cards stopped lining up. A
 * variable product card keeps the usual "Select options" link to the product
 * page, where the chips have room to read properly.
 *
 * @param WC_Product|null $product Product, defaults to the loop product.
 * @param string          $context Layout context, currently only 'single'.
 * @return void
 */
function techjossecom_variation_picker( $product = null, $context = 'single' ) {
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
		return;
	}

	$attributes = $product->get_variation_attributes();

	if ( empty( $attributes ) ) {
		return;
	}

	/*
	 * The list of variations rides along on the picker itself.
	 *
	 * The script used to read it off the cash on delivery button, which meant
	 * that switching cash on delivery off left the chips inert: no variation
	 * data, no selection, and an "Add to cart" that posted an empty form and
	 * reloaded the page. The chips are a product control in their own right, so
	 * they carry what they need.
	 */
	$variations = techjossecom_variation_data( $product );

	printf(
		'<div class="tj-var-picker tj-var-picker--%1$s" data-tj-variation-picker data-tj-variations="%2$s">',
		esc_attr( $context ),
		esc_attr( wp_json_encode( $variations ) )
	);

	foreach ( $attributes as $name => $values ) {
		if ( empty( $values ) || ! is_array( $values ) ) {
			continue;
		}

		$taxonomy = str_replace( 'attribute_', '', $name );
		$is_color = (bool) preg_match( '/colou?r/i', $taxonomy . ' ' . wc_attribute_label( $taxonomy ) );
		$label    = wc_attribute_label( $taxonomy );

		/*
		 * The full cart name of the attribute, "attribute_pa_color".
		 *
		 * get_variation_attributes() hands back the bare name, "pa_color", but
		 * both things the script has to line this group up with are keyed by
		 * the full name: the variation data carried on this same picker
		 * (WC_Product_Variation::get_variation_attributes() prefixes it) and
		 * the real <select name="attribute_pa_color"> that
		 * add-to-cart-variation.js reads.
		 *
		 * Printing the bare name made every one of those lookups miss. The
		 * script found no entry for the attribute on any variation, took
		 * that to mean "any value will do", and settled on the first
		 * variation in the list whatever the shopper had picked; the chips
		 * could not reach the hidden dropdowns either. The add then went out
		 * carrying that first variation's id alongside the colour actually
		 * chosen, and WooCommerce refused it with "Invalid value posted for
		 * Color" - every time, because the choice could never change. One
		 * name for all three is what keeps them in step.
		 */
		$cart_name = 0 === strpos( $name, 'attribute_' ) ? $name : 'attribute_' . $name;

		printf(
			'<div class="tj-var-attr" data-attribute="%1$s"><span class="tj-var-attr-name">%2$s</span><div class="tj-var-values" role="group" aria-label="%3$s">',
			esc_attr( $cart_name ),
			esc_html( $label ),
			esc_attr( $label )
		);

		foreach ( $values as $slug ) {
			$term  = taxonomy_exists( $taxonomy ) ? get_term_by( 'slug', $slug, $taxonomy ) : null;
			$text  = $term && ! is_wp_error( $term ) ? $term->name : $slug;

			if ( $is_color ) {
				printf(
					'<button type="button" class="tj-var-value tj-var-value--color" data-value="%1$s" style="--tj-swatch:%2$s" aria-pressed="false" title="%3$s"><span class="screen-reader-text">%3$s</span></button>',
					esc_attr( $slug ),
					esc_attr( techjossecom_swatch_color( $slug ) ),
					esc_attr( $text )
				);
			} else {
				printf(
					'<button type="button" class="tj-var-value" data-value="%1$s" aria-pressed="false">%2$s</button>',
					esc_attr( $slug ),
					esc_html( $text )
				);
			}
		}

		echo '</div></div>';
	}

	echo '</div>';
}

/**
 * "Order Now" button under every product card in the shop loop.
 *
 * @return void
 */
function techjossecom_loop_order_now() {
	global $product;

	techjossecom_order_now_button( $product, 'loop' );
}

/**
 * Print the Size / Color chips inside the single product add-to-cart form.
 *
 * @return void
 */
function techjossecom_single_variation_picker() {
	global $product;

	techjossecom_variation_picker( $product instanceof WC_Product ? $product : null, 'single' );
}

/**
 * Product title in the loop, linked to the product page.
 *
 * WooCommerce's own woocommerce_template_loop_product_title() prints the
 * <h2> as bare text. Older releases relied on woocommerce_template_loop_
 * product_link_open / _close, fired either side of the title, to wrap it in
 * an <a>; this theme removes those two (see techjossecom_woocommerce_init)
 * because they wrapped the entire card, image and buttons included, in a
 * single link - which made the Add to Cart and Order Now controls unusable
 * and duplicated the link for screen readers.
 *
 * So the anchor is rebuilt here, around the title text only. That keeps a card
 * with exactly one link to the product in its body (the image link is already
 * tabindex="-1" aria-hidden="true", so the title is the only tab stop) and
 * leaves the buttons clickable.
 *
 * The woocommerce_product_loop_title_classes filter is preserved so plugins
 * that add a class to the heading keep working.
 *
 * @return void
 */
function techjossecom_loop_product_title() {
	$techjossecom_title_classes = apply_filters( 'woocommerce_product_loop_title_classes', 'woocommerce-loop-product__title' );

	printf(
		'<h2 class="%1$s"><a href="%2$s">%3$s</a></h2>',
		esc_attr( $techjossecom_title_classes ),
		esc_url( get_permalink() ),
		esc_html( get_the_title() )
	);
}

/**
 * Stock hint under the product titles in the loop.
 *
 * Only out of stock products get a line. The "Cash on delivery available"
 * note that used to be printed here duplicated the trust badges shown on the
 * single product page and unbalanced the card, so a card is now category +
 * title + price only.
 *
 * @return void
 */
function techjossecom_loop_stock_note() {
	global $product;

	if ( ! $product instanceof WC_Product ) {
		return;
	}

	if ( ! $product->is_in_stock() ) {
		echo '<p class="tj-stock-note tj-stock-note--out">' . esc_html__( 'Out of stock', 'techjossecom' ) . '</p>';
	}
}

/**
 * Friendlier add-to-cart labels in the loop.
 *
 * @param string     $text    Button text.
 * @param WC_Product $product Product object.
 * @return string
 */
function techjossecom_add_to_cart_text( $text, $product ) {
	if ( $product instanceof WC_Product && $product->is_type( 'simple' ) && $product->is_in_stock() ) {
		return __( 'Add to Cart', 'techjossecom' );
	}

	return $text;
}

/**
 * "Order Now" button on the single product page.
 *
 * @return void
 */
function techjossecom_single_order_now() {
	global $product;

	if ( ! $product instanceof WC_Product || ! $product->is_in_stock() ) {
		return;
	}

	/*
	 * Printed once, even though it is hooked into two places.
	 *
	 * The summary hook is the fallback for a product whose template prints no
	 * add-to-cart form, and the in-form hook is where it belongs when there
	 * is one. Both fire on an ordinary purchasable product, so without this
	 * the shopper would be offered the same button twice, the lower one
	 * pushed away down the page by the block above it.
	 */
	static $printed = false;

	if ( $printed ) {
		return;
	}

	$printed = true;

	techjossecom_order_now_button( $product, 'single' );
}

/**
 * Trust badges under the single product add-to-cart form.
 *
 * @return void
 */
function techjossecom_single_trust_badges() {
	$badges = array(
		array( techjossecom_icon( 'shield' ), __( '100% genuine products', 'techjossecom' ) ),
		array( techjossecom_icon( 'truck' ), __( 'Cash on delivery available', 'techjossecom' ) ),
		array( techjossecom_icon( 'refresh' ), __( 'Easy return within 7 days', 'techjossecom' ) ),
	);

	echo '<ul class="tj-trust-badges">';

	foreach ( $badges as $badge ) {
		printf(
			'<li><span class="tj-trust-icon" aria-hidden="true">%1$s</span> %2$s</li>',
			$badge[0], // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( $badge[1] )
		);
	}

	$hotline = techjossecom_mod( 'hotline_number' );

	if ( $hotline ) {
		printf(
			'<li class="tj-trust-hotline"><span class="tj-trust-icon" aria-hidden="true">%1$s</span> %2$s: <a href="tel:%3$s">%4$s</a></li>',
			techjossecom_icon( 'phone' ),
			esc_html( techjossecom_mod( 'hotline_label' ) ),
			esc_attr( preg_replace( '/[^0-9+]/', '', $hotline ) ),
			esc_html( $hotline )
		);
	}

	echo '</ul>';
}

/**
 * Social share links on the single product page.
 *
 * @return void
 */
function techjossecom_single_share() {
	if ( ! techjossecom_mod( 'show_single_share' ) ) {
		return;
	}

	$url   = rawurlencode( get_permalink() );
	$title = rawurlencode( get_the_title() );

	$networks = array(
		'facebook'  => array( 'https://www.facebook.com/sharer/sharer.php?u=' . $url, __( 'Share on Facebook', 'techjossecom' ), techjossecom_icon( 'facebook' ) ),
		'whatsapp'  => array( 'https://api.whatsapp.com/send?text=' . $title . '%20' . $url, __( 'Share on WhatsApp', 'techjossecom' ), techjossecom_icon( 'whatsapp' ) ),
		'messenger' => array( 'https://www.facebook.com/dialog/send?link=' . $url . '&app_id=291494419107518&redirect_uri=' . $url, __( 'Share on Messenger', 'techjossecom' ), techjossecom_icon( 'messenger' ) ),
		'x'         => array( 'https://twitter.com/intent/tweet?text=' . $title . '&url=' . $url, __( 'Share on X', 'techjossecom' ), techjossecom_icon( 'x' ) ),
		'pinterest' => array( 'https://pinterest.com/pin/create/button/?url=' . $url . '&description=' . $title, __( 'Save on Pinterest', 'techjossecom' ), techjossecom_icon( 'pinterest' ) ),
	);

	echo '<div class="tj-share"><span class="tj-share-label">' . esc_html__( 'Share:', 'techjossecom' ) . '</span>';

	foreach ( $networks as $slug => $network ) {
		printf(
			'<a class="tj-share-link tj-share-link--%1$s" href="%2$s" target="_blank" rel="noopener nofollow" aria-label="%3$s">%4$s</a>',
			esc_attr( $slug ),
			esc_url( $network[0] ),
			esc_attr( $network[1] ),
			$network[2] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	echo '</div>';
}

/**
 * Drop the repeated heading from the description tab.
 *
 * WooCommerce's description panel opens with an <h2> reading "Description",
 * which is the label on the tab the shopper has just clicked. The word then
 * appears twice within a couple of centimetres, and the copy itself is pushed
 * down by a heading that adds nothing. Returning an empty string is the
 * filter's own way of saying "no heading" - the template checks it before
 * printing - so nothing is emitted and the panel starts with the text.
 *
 * The panel keeps its padding either way, so this only removes the duplicate
 * line; it does not change the spacing the panel was designed with.
 *
 * @param string $heading Heading WooCommerce is about to print.
 * @return string Empty to suppress it.
 */
function techjossecom_description_tab_heading( $heading ) {
	return '';
}
add_filter( 'woocommerce_product_description_heading', 'techjossecom_description_tab_heading' );

/**
 * Fewer, larger related products.
 *
 * @param array $args Related product query arguments.
 * @return array
 */
function techjossecom_related_products_args( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;

	return $args;
}

/**
 * Trim the checkout fields down to what a cash on delivery order needs.
 *
 * @param array $fields Checkout fields.
 * @return array
 */
function techjossecom_checkout_fields( $fields ) {
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['required']    = true;
		$fields['billing']['billing_phone']['priority']    = 25;
		$fields['billing']['billing_phone']['label']       = __( 'Mobile number', 'techjossecom' );
		$fields['billing']['billing_phone']['placeholder'] = __( 'e.g. 01700000000', 'techjossecom' );
	}

	if ( isset( $fields['billing']['billing_email'] ) ) {
		$fields['billing']['billing_email']['required'] = false;
	}

	if ( isset( $fields['billing']['billing_address_1'] ) ) {
		$fields['billing']['billing_address_1']['required']    = false;
		$fields['billing']['billing_address_1']['label']       = __( 'Full address', 'techjossecom' );
		$fields['billing']['billing_address_1']['placeholder'] = __( 'House, road, area, district', 'techjossecom' );
	}

	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['placeholder'] = __( 'Delivery instructions (optional)', 'techjossecom' );
	}

	return $fields;
}

/**
 * Remove the "Add coupons" panel from the cart and checkout order summaries.
 *
 * Both pages are built from WooCommerce Blocks, and each order summary embeds a
 * coupon form block as a server rendered placeholder:
 *
 *     <div data-block-name="woocommerce/cart-order-summary-coupon-form-block">
 *
 * WooCommerce's own script then finds that div and fills it with the "Add
 * coupons" accordion and its code field. Returning an empty string here removes
 * the placeholder, so the script has nothing to mount and the panel never
 * appears. This is a supported core filter, so no plugin file is touched and an
 * update cannot overwrite it.
 *
 * Coupons that are already in the cart are not lost: the applied discount is
 * still listed as its own row in the totals, only the way to add a new code
 * is gone.
 *
 * @param string $content Rendered block markup.
 * @param array  $block   Parsed block.
 * @return string
 */
function techjossecom_remove_coupon_blocks( $content, $block ) {
	$coupon_blocks = array(
		'woocommerce/cart-order-summary-coupon-form-block',
		'woocommerce/checkout-order-summary-coupon-form-block',
	);

	if ( isset( $block['blockName'] ) && in_array( $block['blockName'], $coupon_blocks, true ) ) {
		return '';
	}

	return $content;
}

/**
 * Remind the customer that cash on delivery is available.
 *
 * @return void
 */
function techjossecom_cod_notice() {
	echo '<p class="tj-cod-notice">' . esc_html__( 'Cash on delivery: pay the courier when your order arrives.', 'techjossecom' ) . '</p>';
}

/**
 * Two cross-sell columns instead of four.
 *
 * @return int
 */
function techjossecom_cross_sells_columns() {
	return 2;
}

/**
 * Products currently on sale, used by the homepage deals grid.
 *
 * @param int $limit Maximum number of products.
 * @return WP_Query|null
 */
function techjossecom_on_sale_products( $limit = 8 ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return null;
	}

	$ids = wc_get_product_ids_on_sale();

	if ( empty( $ids ) ) {
		return null;
	}

	return new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => absint( $limit ),
			'post__in'            => $ids,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		)
	);
}

/**
 * Top level product categories ordered by product count.
 *
 * @param int $limit Maximum number of categories. Pass 0 for no limit.
 * @return array Array of WP_Term objects.
 */
function techjossecom_product_categories( $limit = 12 ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			// WP_Term_Query reads "number" => 0 as "return everything", which is
			// exactly what the mobile drawer asks for.
			'number'     => absint( $limit ),
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	return $terms;
}

/**
 * Products in a category, used by the homepage "top picks" grid.
 *
 * @param int $limit Maximum number of products.
 * @return WP_Query
 */
function techjossecom_latest_products( $limit = 8 ) {
	return new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => absint( $limit ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		)
	);
}
/**
 * Render a WP_Query of products with the theme's product card template.
 *
 * @param WP_Query|null $query Product query.
 * @param int           $columns Number of columns to declare to WooCommerce.
 * @return void
 */
function techjossecom_product_grid( $query, $columns = 4 ) {
	if ( ! $query instanceof WP_Query || ! $query->have_posts() || ! function_exists( 'woocommerce_product_loop_start' ) ) {
		return;
	}

	wc_set_loop_prop( 'columns', absint( $columns ) );
	wc_set_loop_prop( 'is_ajax_add_to_cart', true );

	woocommerce_product_loop_start();

	while ( $query->have_posts() ) {
		$query->the_post();

		wc_get_template_part( 'content', 'product' );
	}

	woocommerce_product_loop_end();

	wp_reset_postdata();
	wc_reset_loop();
}

/**
 * Print the previous / next arrows for a scrollable product row.
 *
 * Every section that shows a product carousel calls this, so the buttons, their
 * labels and their icons are written once instead of being copied into each
 * template. A new homepage section therefore needs nothing but a .tj-carousel
 * wrapper, a [data-tj-scroller] track and one call to this function: the script
 * in assets/js/main.js finds the arrows from the section that owns the track,
 * and the stylesheet styles them the same way, so no new CSS and no new
 * JavaScript are ever written for a new row.
 *
 * Call it inside the section's .tj-section-tools, which puts the arrows in the
 * heading on the left of the "View All" link, the arrangement the homepage
 * design uses. The section is what ties a button to its track: the script looks
 * the scroller up from the section around the button, so the two can sit in
 * different parts of the markup.
 *
 * The icons are the theme's own inline SVGs, which means they inherit the
 * button colour and stay sharp on every screen, unlike a text character.
 *
 * @return void
 */
function techjossecom_scroll_buttons() {
	printf(
		'<div class="tj-scroll-buttons" data-tj-scroll-buttons><button type="button" class="tj-scroll-btn" data-tj-scroll="prev" aria-label="%1$s">%3$s</button><button type="button" class="tj-scroll-btn" data-tj-scroll="next" aria-label="%2$s">%4$s</button></div>',
		esc_attr__( 'Previous products', 'techjossecom' ),
		esc_attr__( 'Next products', 'techjossecom' ),
		techjossecom_icon( 'arrow-left' ),
		techjossecom_icon( 'arrow-right' )
	);
}



