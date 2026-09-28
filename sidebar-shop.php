<?php
/**
 * Shop sidebar (used by the WooCommerce wrapper when widgets are assigned).
 *
 * @package TechJosse_Commerce
 */

if ( ! is_active_sidebar( 'shop-sidebar' ) ) {
	return;
}
?>
<aside class="tj-layout-sidebar tj-shop-sidebar tj-widget-area" role="complementary">
	<?php dynamic_sidebar( 'shop-sidebar' ); ?>
</aside>
