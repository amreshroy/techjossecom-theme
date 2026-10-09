<?php
/**
 * Template Name: Terms and Conditions Page
 * Template Post Type: page
 *
 * A legal policy page: a sticky table of contents beside a clean,
 * numbered article, matching the storefront typography and colours.
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
					<p class="tj-page-hero-text"><?php esc_html_e( 'Please read these terms carefully before using our website or placing an order.', 'techjossecom' ); ?></p>
				</div>

				<div class="tj-policy-layout">

					<nav class="tj-policy-toc" aria-label="<?php esc_attr_e( 'Table of contents', 'techjossecom' ); ?>">
						<h2 class="tj-policy-toc-title"><?php esc_html_e( 'On this page', 'techjossecom' ); ?></h2>
						<ol>
							<li><a href="#tj-terms-intro"><?php esc_html_e( 'Introduction', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-terms-account"><?php esc_html_e( 'Accounts &amp; eligibility', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-terms-orders"><?php esc_html_e( 'Orders &amp; pricing', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-terms-payment"><?php esc_html_e( 'Payment', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-terms-delivery"><?php esc_html_e( 'Delivery', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-terms-liability"><?php esc_html_e( 'Liability', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-terms-changes"><?php esc_html_e( 'Changes to these terms', 'techjossecom' ); ?></a></li>
						</ol>
					</nav>

					<div class="tj-policy-body">
						<?php
						$tj_content = get_the_content();
						if ( trim( wp_strip_all_tags( $tj_content ) ) !== '' ) {
							the_content();
						}
						?>

						<section id="tj-terms-intro">
							<h2><?php esc_html_e( '1. Introduction', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'Welcome to our store. By accessing this website or placing an order, you agree to be bound by these terms and conditions. If you do not agree with any part of these terms, please do not use our website.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-terms-account">
							<h2><?php esc_html_e( '2. Accounts &amp; eligibility', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'You must provide accurate and complete information when creating an account or placing an order. You are responsible for keeping your account details confidential and for all activity that happens under your account.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-terms-orders">
							<h2><?php esc_html_e( '3. Orders &amp; pricing', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'All orders are subject to availability and confirmation. We strive to display accurate prices and product information, but minor errors may occur. If a price or description is incorrect, we may cancel the order and inform you.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-terms-payment">
							<h2><?php esc_html_e( '4. Payment', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'We accept the payment methods shown at checkout, including cash on delivery where available. Full payment is due before dispatch unless cash on delivery is selected.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-terms-delivery">
							<h2><?php esc_html_e( '5. Delivery', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'Delivery times are estimates and may vary by location. We are not responsible for delays caused by events outside our control, but we will always keep you updated on your order.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-terms-liability">
							<h2><?php esc_html_e( '6. Liability', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'Our liability is limited to the value of the products you purchase. We are not liable for indirect or consequential loss arising from the use of our website or products beyond what the law allows.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-terms-changes">
							<h2><?php esc_html_e( '7. Changes to these terms', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'We may update these terms from time to time. Any changes will be posted on this page, and continued use of the website means you accept the revised terms.', 'techjossecom' ); ?></p>
							<p class="tj-policy-note"><?php esc_html_e( 'If you have any questions about these terms, please contact us through our contact page.', 'techjossecom' ); ?></p>
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
