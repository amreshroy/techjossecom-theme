<?php
/**
 * Product card used in every WooCommerce loop (shop, archives, shortcodes and
 * the homepage sections).
 *
 * @package TechJosse_Commerce
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'tj-product', $product ); ?>>
	<?php do_action( 'woocommerce_before_shop_loop_item' ); ?>

	<div class="tj-product-inner">
		<div class="tj-product-media">
			<a class="tj-product-thumb" href="<?php echo esc_url( $product->get_permalink() ); ?>" tabindex="-1" aria-hidden="true">
				<?php echo wp_kses_post( $product->get_image( 'techjossecom-card', array( 'loading' => 'lazy', 'alt' => $product->get_name() ) ) ); ?>
			</a>

			<?php
			// The discount badge, printed straight from the shared helper rather
			// than through woocommerce_show_product_loop_sale_flash(). Both
			// routes end at the same markup, but calling the helper here means
			// this card cannot drift from the single product page, the related
			// products grid or the mini cart if the loop filters are ever
			// reordered by a plugin.
			techjossecom_the_sale_badge( $product, 'loop' );
			?>

			<?php if ( ! $product->is_in_stock() ) : ?>
				<span class="tj-badge tj-badge--out"><?php esc_html_e( 'Out of stock', 'techjossecom' ); ?></span>
			<?php endif; ?>
		</div>

		<div class="tj-product-body">
			<?php
			$techjossecom_categories = wc_get_product_category_list( $product->get_id(), ', ' );

			if ( $techjossecom_categories ) {
				printf( '<span class="tj-product-cat">%s</span>', wp_kses_post( $techjossecom_categories ) );
			}

			do_action( 'woocommerce_shop_loop_item_title' );
			do_action( 'woocommerce_after_shop_loop_item_title' );

			// No Size / Color picker on the card, deliberately. The chips need
			// real estate that a product card does not have: on a two column
			// phone grid the size row wrapped onto a second line, the card grew
			// taller than its neighbours and the row stopped lining up. The
			// picker lives on the single product page instead, where it has the
			// room to read properly. A variable product card keeps the usual
			// "Select options" link to that page.
			?>
		</div>

		<div class="tj-product-actions">
			<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>
		</div>
	</div>
</li>
