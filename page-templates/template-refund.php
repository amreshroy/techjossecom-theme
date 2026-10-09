<?php
/**
 * Template Name: Return & Refund Policy Page
 * Template Post Type: page
 *
 * A returns and refunds policy page, using the sticky table of
 * contents layout plus a numbered step list for the return process.
 *
 * @package TechJosse_Commerce
 */

get_header();
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
					<p class="tj-page-hero-text"><?php esc_html_e( 'We want you to be completely happy with your purchase. Here is how returns and refunds work.', 'techjossecom' ); ?></p>
				</div>

				<div class="tj-policy-layout">

					<nav class="tj-policy-toc" aria-label="<?php esc_attr_e( 'Table of contents', 'techjossecom' ); ?>">
						<h2 class="tj-policy-toc-title"><?php esc_html_e( 'On this page', 'techjossecom' ); ?></h2>
						<ol>
							<li><a href="#tj-refund-window"><?php esc_html_e( 'Return window', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-refund-eligible"><?php esc_html_e( 'What can be returned', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-refund-process"><?php esc_html_e( 'How to return', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-refund-money"><?php esc_html_e( 'Refunds', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-refund-exchange"><?php esc_html_e( 'Exchanges &amp; damaged items', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-refund-contact"><?php esc_html_e( 'Need help?', 'techjossecom' ); ?></a></li>
						</ol>
					</nav>

					<div class="tj-policy-body">
						<?php
						$tj_content = get_the_content();
						if ( trim( wp_strip_all_tags( $tj_content ) ) !== '' ) {
							the_content();
						}
						?>

						<section id="tj-refund-window">
							<h2><?php esc_html_e( '1. Return window', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'You may request a return within 7 days of receiving your order, as long as the item meets the conditions below.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-refund-eligible">
							<h2><?php esc_html_e( '2. What can be returned', 'techjossecom' ); ?></h2>
							<ul>
								<li><?php esc_html_e( 'Items that are unused, undamaged and in their original packaging.', 'techjossecom' ); ?></li>
								<li><?php esc_html_e( 'Products that arrived damaged, defective or incorrect.', 'techjossecom' ); ?></li>
							</ul>
							<p><?php esc_html_e( 'For safety and hygiene reasons, some items (such as certain personal protective equipment) cannot be returned once used.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-refund-process">
							<h2><?php esc_html_e( '3. How to return', 'techjossecom' ); ?></h2>
							<ol class="tj-steps">
								<li><span class="tj-steps-title"><?php esc_html_e( 'Contact our team', 'techjossecom' ); ?></span> <?php esc_html_e( 'Reach us through the contact page or hotline with your order number.', 'techjossecom' ); ?></li>
								<li><span class="tj-steps-title"><?php esc_html_e( 'Confirm eligibility', 'techjossecom' ); ?></span> <?php esc_html_e( 'We will confirm whether your item qualifies and arrange pickup or a drop-off.', 'techjossecom' ); ?></li>
								<li><span class="tj-steps-title"><?php esc_html_e( 'Send the item back', 'techjossecom' ); ?></span> <?php esc_html_e( 'Pack the item securely in its original packaging with all accessories.', 'techjossecom' ); ?></li>
								<li><span class="tj-steps-title"><?php esc_html_e( 'Get your refund', 'techjossecom' ); ?></span> <?php esc_html_e( 'Once we receive and check the item, we process your refund.', 'techjossecom' ); ?></li>
							</ol>
						</section>

						<section id="tj-refund-money">
							<h2><?php esc_html_e( '4. Refunds', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'Approved refunds are returned to your original payment method. If you paid cash on delivery, we will refund you through mobile banking or another agreed method. Refunds are usually completed within 7–10 working days of approval.', 'techjossecom' ); ?></p>
							<p class="tj-policy-note"><?php esc_html_e( 'Any delivery charges are non-refundable unless the return is due to our error or a damaged product.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-refund-exchange">
							<h2><?php esc_html_e( '5. Exchanges &amp; damaged items', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'If your item arrived damaged or incorrect, please contact us within 48 hours with photos so we can arrange a replacement or a full refund at no extra cost.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-refund-contact">
							<h2><?php esc_html_e( '6. Need help?', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'Our support team is happy to help with any return or refund question. Just reach out and we will guide you through it.', 'techjossecom' ); ?></p>
						</section>
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
