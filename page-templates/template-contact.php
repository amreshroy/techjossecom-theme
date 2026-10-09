<?php
/**
 * Template Name: Contact Page
 * Template Post Type: page
 *
 * A contact page that matches the storefront: a tinted hero, the
 * shop's saved phone / email / address pulled from the customizer,
 * quick WhatsApp and Messenger buttons, and an opening-hours panel.
 *
 * @package TechJosse_Commerce
 */

get_header();

$tj_hotline   = techjossecom_mod( 'hotline_number' );
$tj_hotline_l = techjossecom_mod( 'hotline_label' );
$tj_email     = techjossecom_mod( 'contact_email' );
$tj_address   = techjossecom_mod( 'contact_address' );
?>
<main id="tj-main" class="tj-main" role="main">
	<div class="tj-container">
		<?php techjossecom_breadcrumbs(); ?>

		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'tj-entry' ); ?>>

				<div class="tj-page-hero">
					<h1 class="tj-page-hero-title"><?php the_title(); ?></h1>
					<p class="tj-page-hero-text"><?php esc_html_e( 'Have a question about an order, a product or delivery? Reach us whichever way suits you — we are always happy to help.', 'techjossecom' ); ?></p>
				</div>

				<?php
				// Any extra copy the page owner adds in the editor is printed here.
				$tj_content = get_the_content();
				if ( trim( wp_strip_all_tags( $tj_content ) ) !== '' ) {
					echo '<div class="tj-entry-content">';
					the_content();
					echo '</div>';
				}
				?>

				<div class="tj-page-grid tj-page-grid--2">

					<div class="tj-panel">
						<h2 class="tj-panel-title"><?php esc_html_e( 'Get in touch', 'techjossecom' ); ?></h2>

						<ul class="tj-contact-list">
							<?php if ( $tj_hotline ) : ?>
								<li>
									<span aria-hidden="true"><?php echo techjossecom_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span>
										<span class="tj-contact-label"><?php echo esc_html( $tj_hotline_l ? $tj_hotline_l : __( 'Hotline', 'techjossecom' ) ); ?></span>
										<span class="tj-contact-value"><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $tj_hotline ) ); ?>"><?php echo esc_html( $tj_hotline ); ?></a></span>
									</span>
								</li>
							<?php endif; ?>

							<?php if ( $tj_email ) : ?>
								<li>
									<span aria-hidden="true"><?php echo techjossecom_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span>
										<span class="tj-contact-label"><?php esc_html_e( 'Email', 'techjossecom' ); ?></span>
										<span class="tj-contact-value"><a href="mailto:<?php echo esc_attr( $tj_email ); ?>"><?php echo esc_html( $tj_email ); ?></a></span>
									</span>
								</li>
							<?php endif; ?>

							<?php if ( $tj_address ) : ?>
								<li>
									<span aria-hidden="true"><?php echo techjossecom_icon( 'store' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span>
										<span class="tj-contact-label"><?php esc_html_e( 'Address', 'techjossecom' ); ?></span>
										<span class="tj-contact-value"><?php echo esc_html( $tj_address ); ?></span>
									</span>
								</li>
							<?php endif; ?>
						</ul>

						<?php
						/*
						 * The WhatsApp and Messenger buttons only appear when those
						 * numbers are filled in under Appearance / Customize, so this
						 * panel never shows a dead button.
						 */
						techjossecom_contact_buttons( 'single' );
						?>
					</div>

					<div class="tj-panel">
						<h2 class="tj-panel-title"><?php esc_html_e( 'Opening hours', 'techjossecom' ); ?></h2>

						<table class="tj-hours">
							<tbody>
								<tr>
									<th scope="row"><?php esc_html_e( 'Saturday – Thursday', 'techjossecom' ); ?></th>
									<td><?php esc_html_e( '9:00 AM – 9:00 PM', 'techjossecom' ); ?></td>
								</tr>
								<tr>
									<th scope="row"><?php esc_html_e( 'Friday', 'techjossecom' ); ?></th>
									<td><?php esc_html_e( 'Closed (online orders open)', 'techjossecom' ); ?></td>
								</tr>
							</tbody>
						</table>

						<p class="tj-policy-note">
							<?php esc_html_e( 'Online orders can be placed at any time. Our team confirms every order by phone before it is dispatched.', 'techjossecom' ); ?>
						</p>
					</div>

				</div>

				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</article>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();
