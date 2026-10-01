<?php
/**
 * Product quantity input: a -/+ stepper around the number.
 *
 * WooCommerce ships a bare <input type="number">, which on a phone brings up
 * the numeric keypad but offers no way to change the count without typing.
 * A shopper buying three of something has to know they want three first, and
 * on a touch screen typing is the slowest possible way to say so.
 *
 * The stepper is two real buttons beside the input rather than a widget drawn
 * over it, so it works from the keyboard, is announced as two controls, and
 * needs no script to look right. The count stays an ordinary input named
 * "quantity": WooCommerce reads that field when the form is posted without
 * JavaScript and the theme's own add-to-cart handler reads it too, so a
 * number a shopper typed is never lost because a button was pressed.
 *
 * Everything else is WooCommerce's own template unchanged, hooks included, so
 * a plugin printing before or after the field still works.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package TechJosse_Commerce
 * @version 10.1.0
 */

defined( 'ABSPATH' ) || exit;

/* translators: %s: Quantity. */
$label = ! empty( $args['product_name'] ) ? sprintf( esc_html__( '%s quantity', 'woocommerce' ), wp_strip_all_tags( $args['product_name'] ) ) : esc_html__( 'Quantity', 'woocommerce' );

/*
 * A hidden input has nothing to step, and a read-only one belongs to a
 * product sold as a single unit. Either way the buttons would be inert, so
 * they are left out and only the hidden field is printed.
 */
$stepper = 'hidden' !== $type && ! $readonly;
?>
<div class="quantity">
	<?php
	/**
	 * Hook to output something before the quantity input field.
	 *
	 * @since 7.2.0
	 */
	do_action( 'woocommerce_before_quantity_input_field' );
	?>

	<?php if ( $stepper ) : ?>
		<button
			type="button"
			class="tj-qty-btn tj-qty-btn--minus"
			data-tj-qty-step="-1"
			aria-label="<?php esc_attr_e( 'Decrease the quantity', 'techjossecom' ); ?>"
		>&minus;</button>
	<?php endif; ?>

	<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $label ); ?></label>
	<input
		type="<?php echo esc_attr( $type ); ?>"
		<?php echo $readonly ? 'readonly="readonly"' : ''; ?>
		id="<?php echo esc_attr( $input_id ); ?>"
		class="<?php echo esc_attr( join( ' ', (array) $classes ) ); ?>"
		name="<?php echo esc_attr( $input_name ); ?>"
		value="<?php echo esc_attr( $input_value ); ?>"
		aria-label="<?php esc_attr_e( 'Product quantity', 'woocommerce' ); ?>"
		<?php if ( in_array( $type, array( 'text', 'search', 'tel', 'url', 'email', 'password' ), true ) ) : ?>
			size="4"
		<?php endif; ?>
		min="<?php echo esc_attr( $min_value ); ?>"
		<?php if ( 0 < $max_value ) : ?>
			max="<?php echo esc_attr( $max_value ); ?>"
		<?php endif; ?>
		<?php if ( ! $readonly ) : ?>
			step="<?php echo esc_attr( $step ); ?>"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			inputmode="<?php echo esc_attr( $inputmode ); ?>"
			autocomplete="<?php echo esc_attr( isset( $autocomplete ) ? $autocomplete : 'on' ); ?>"
		<?php endif; ?>
	/>

	<?php if ( $stepper ) : ?>
		<button
			type="button"
			class="tj-qty-btn tj-qty-btn--plus"
			data-tj-qty-step="1"
			aria-label="<?php esc_attr_e( 'Increase the quantity', 'techjossecom' ); ?>"
		>&plus;</button>
	<?php endif; ?>

	<?php
	/**
	 * Hook to output something after quantity input field.
	 *
	 * @since 3.6.0
	 */
	do_action( 'woocommerce_after_quantity_input_field' );
	?>
</div>