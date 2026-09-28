<?php
/**
 * Top bar with the welcome message, top links and contact shortcuts.
 *
 * @package TechJosse_Commerce
 */

if ( ! techjossecom_mod( 'show_topbar' ) ) {
	return;
}
?>
<div class="tj-topbar">
	<div class="tj-container tj-topbar-inner">
		<p class="tj-topbar-text">
			<span aria-hidden="true"><?php echo techjossecom_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php echo esc_html( techjossecom_mod( 'topbar_text' ) ); ?>
		</p>

		<div class="tj-topbar-links">
			<?php
			if ( techjossecom_has_menu( 'topbar' ) ) {
				techjossecom_nav_menu(
					array(
						'theme_location' => 'topbar',
						'menu_class'     => 'tj-topbar-menu',
						'depth'          => 1,
					)
				);
			}

			if ( techjossecom_has_woocommerce() ) {
				printf(
					'<a class="tj-topbar-link" href="%1$s">%2$s</a>',
					esc_url( wc_get_page_permalink( 'myaccount' ) ),
					esc_html__( 'My Account', 'techjossecom' )
				);

				printf(
					'<a class="tj-topbar-link" href="%1$s">%2$s</a>',
					esc_url( wc_get_cart_url() ),
					esc_html__( 'Track Order', 'techjossecom' )
				);
			}

			$techjossecom_topbar_whatsapp = techjossecom_mod( 'whatsapp_number' );

			if ( $techjossecom_topbar_whatsapp ) {
				printf(
					'<a class="tj-topbar-link tj-topbar-link--whatsapp" href="%1$s" target="_blank" rel="noopener nofollow">%2$s</a>',
					esc_url( 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $techjossecom_topbar_whatsapp ) ),
					esc_html__( 'WhatsApp', 'techjossecom' )
				);
			}
			?>
		</div>
	</div>
</div>
