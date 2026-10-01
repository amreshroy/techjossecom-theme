<?php
/**
 * Single variation cart button: quantity stepper, add to cart and Order Now.
 *
 * Overrides WooCommerce's template of the same name to put a -/+ stepper
 * around the count, matching the layout the reference shops use:
 * quantity, then "Add to cart", then "Order Now", all on one row.
 *
 * WooCommerce's own file is reproduced exactly except for the quantity block,
 * so a plugin hooking any of the hooks below keeps working. The hidden
 * add-to-cart, product_id and variation_id inputs are copied over verbatim:
 * add-to-cart-variation.js writes the variation id into the last of them,
 * and the form posts the first two, so dropping or renaming any of them would
 * break variable products outright.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package TechJosse_Commerce
 * @version 10.5.2
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<?php
	do_action( 'woocommerce_before_add_to_cart_quantity' );

	woocommerce_quantity_input(
		array(
			'min_value'   => $product->get_min_purchase_quantity(),
			'max_value'   => $product->get_max_purchase_quantity(),
			'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // phpcs:ignore WordPress.Security.NonceVerification.Missing -- the count is re-validated by WooCommerce on add.
			'class'       => 'tj-qty-input',
		)
	);

	do_action( 'woocommerce_after_add_to_cart_quantity' );
	?>

	<button type="submit" class="single_add_to_cart_button button alt<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

	<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>