<?php
/**
 * Site footer: link columns, socials, mini cart drawer and the COD popup.
 *
 * @package TechJosse_Commerce
 */

$techjossecom_socials = techjossecom_social_links();
$techjossecom_email   = techjossecom_mod( 'contact_email' );
$techjossecom_copyright = techjossecom_mod( 'footer_copyright' );
?>
	<footer id="colophon" class="tj-footer" role="contentinfo">
		<div class="tj-container tj-footer-top">
			<div class="tj-footer-col tj-footer-about">
				<?php
				if ( is_active_sidebar( 'footer-about' ) ) {
					dynamic_sidebar( 'footer-about' );
				} else {
					techjossecom_site_branding();

					$about = techjossecom_mod( 'footer_about' );
					if ( $about ) {
						printf( '<p class="tj-footer-about-text">%s</p>', esc_html( $about ) );
					}
				}

				if ( $techjossecom_socials ) {
					echo '<ul class="tj-socials">';
					foreach ( $techjossecom_socials as $slug => $social ) {
						printf(
							'<li><a class="tj-social tj-social--%1$s" href="%2$s" target="_blank" rel="noopener nofollow" aria-label="%3$s">%4$s</a></li>',
							esc_attr( $slug ),
							esc_url( $social['url'] ),
							esc_attr( $social['label'] ),
							esc_html( $social['icon'] )
						);
					}
					echo '</ul>';
				}
				?>
			</div>

			<?php
			techjossecom_footer_column( 'footer_1', __( 'Top Categories', 'techjossecom' ) );
			techjossecom_footer_column( 'footer_2', __( 'Our Services', 'techjossecom' ) );
			techjossecom_footer_column( 'footer_3', __( 'Information', 'techjossecom' ) );
			?>

			<div class="tj-footer-col tj-footer-contact">
				<h3 class="tj-footer-title"><?php esc_html_e( 'Contact Us', 'techjossecom' ); ?></h3>
				<ul class="tj-footer-contact-list">
					<?php if ( techjossecom_mod( 'hotline_number' ) ) : ?>
						<li><span aria-hidden="true"><?php echo techjossecom_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span> <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', techjossecom_mod( 'hotline_number' ) ) ); ?>"><?php echo esc_html( techjossecom_mod( 'hotline_number' ) ); ?></a></li>
					<?php endif; ?>

					<?php if ( $techjossecom_email ) : ?>
						<li><span aria-hidden="true"><?php echo techjossecom_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span> <a href="mailto:<?php echo esc_attr( $techjossecom_email ); ?>"><?php echo esc_html( $techjossecom_email ); ?></a></li>
					<?php endif; ?>

					<?php if ( techjossecom_mod( 'contact_address' ) ) : ?>
						<li><span aria-hidden="true"><?php echo techjossecom_icon( 'store' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span> <?php echo esc_html( techjossecom_mod( 'contact_address' ) ); ?></li>
					<?php endif; ?>
				</ul>
				<?php techjossecom_contact_buttons( 'footer' ); ?>
			</div>
		</div>

		<div class="tj-footer-bottom">
			<div class="tj-container tj-footer-bottom-inner">
				<p class="tj-copyright">
					<?php
					if ( $techjossecom_copyright ) {
						echo esc_html( $techjossecom_copyright );
					} else {
						printf(
							/* translators: 1: current year, 2: site name. */
							esc_html__( '© %1$s %2$s. All rights reserved.', 'techjossecom' ),
							esc_html( gmdate( 'Y' ) ),
							esc_html( get_bloginfo( 'name' ) )
						);
					}
					?>
				</p>
				<?php if ( techjossecom_mod( 'footer_payments' ) ) : ?>
					<p class="tj-payments"><?php echo esc_html( techjossecom_mod( 'footer_payments' ) ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</footer>

	<?php get_template_part( 'template-parts/mini-cart' ); ?>
	<?php get_template_part( 'template-parts/cod-modal' ); ?>

	<div class="tj-overlay" data-tj-overlay hidden></div>

	<nav class="tj-bottom-bar" aria-label="<?php esc_attr_e( 'Quick links', 'techjossecom' ); ?>">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><span aria-hidden="true"><?php echo techjossecom_icon( 'home' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php esc_html_e( 'Home', 'techjossecom' ); ?></a>
		<a href="<?php echo esc_url( techjossecom_shop_url() ); ?>"><span aria-hidden="true"><?php echo techjossecom_icon( 'store' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php esc_html_e( 'Shop', 'techjossecom' ); ?></a>
		<button type="button" data-tj-search-focus data-tj-toggle="tj-mobile-search" aria-expanded="false" aria-controls="tj-mobile-search"><span aria-hidden="true"><?php echo techjossecom_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php esc_html_e( 'Search', 'techjossecom' ); ?></button>
		<?php if ( techjossecom_has_woocommerce() ) : ?>
			<button type="button" data-tj-open-cart><span aria-hidden="true"><?php echo techjossecom_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php esc_html_e( 'Cart', 'techjossecom' ); ?></button>
		<?php endif; ?>
		<?php if ( techjossecom_mod( 'hotline_number' ) ) : ?>
			<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', techjossecom_mod( 'hotline_number' ) ) ); ?>"><span aria-hidden="true"><?php echo techjossecom_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><?php esc_html_e( 'Call', 'techjossecom' ); ?></a>
		<?php endif; ?>
	</nav>
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>
