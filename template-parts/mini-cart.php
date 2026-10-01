<?php
/**
 * Slide-in mini cart.
 *
 * @package TechJosse_Commerce
 */

if ( ! techjossecom_has_woocommerce() ) {
	return;
}
?>
<aside id="tj-mini-cart" class="tj-drawer tj-drawer--cart" hidden aria-hidden="true" aria-labelledby="tj-mini-cart-title">
	<div class="tj-drawer-panel" role="dialog" aria-modal="true">
		<div class="tj-drawer-head">
			<h2 id="tj-mini-cart-title" class="tj-drawer-title"><?php esc_html_e( 'Your Cart', 'techjossecom' ); ?></h2>
			<?php // The word next to the cross gives the close control a name a shopper can aim at, not just a small target to find. ?>
			<button type="button" class="tj-icon-btn tj-drawer-close" data-tj-close="tj-mini-cart">
				<span class="tj-drawer-close-label"><?php esc_html_e( 'Close', 'techjossecom' ); ?></span>
				<span class="tj-drawer-close-mark" aria-hidden="true">&times;</span>
				<span class="screen-reader-text"><?php esc_html_e( 'Close the cart', 'techjossecom' ); ?></span>
			</button>
		</div>

		<?php
		echo techjossecom_mini_cart_part( 'body' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo techjossecom_mini_cart_part( 'footer' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</aside>
