<?php
/**
 * Default sidebar.
 *
 * @package TechJosse_Commerce
 */

if ( ! is_active_sidebar( 'sidebar-1' ) ) {
	return;
}
?>
<aside class="tj-layout-sidebar tj-widget-area" role="complementary">
	<?php dynamic_sidebar( 'sidebar-1' ); ?>
</aside>
