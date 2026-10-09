<?php
/**
 * Template Name: Privacy Policy Page
 * Template Post Type: page
 *
 * A privacy policy page using the same sticky table of contents
 * layout as the terms page.
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
					<p class="tj-page-hero-text"><?php esc_html_e( 'Your privacy matters to us. This policy explains what information we collect and how we use it.', 'techjossecom' ); ?></p>
				</div>

				<div class="tj-policy-layout">

					<nav class="tj-policy-toc" aria-label="<?php esc_attr_e( 'Table of contents', 'techjossecom' ); ?>">
						<h2 class="tj-policy-toc-title"><?php esc_html_e( 'On this page', 'techjossecom' ); ?></h2>
						<ol>
							<li><a href="#tj-privacy-collect"><?php esc_html_e( 'Information we collect', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-privacy-use"><?php esc_html_e( 'How we use it', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-privacy-share"><?php esc_html_e( 'Sharing your information', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-privacy-cookies"><?php esc_html_e( 'Cookies', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-privacy-security"><?php esc_html_e( 'Keeping your data safe', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-privacy-rights"><?php esc_html_e( 'Your rights', 'techjossecom' ); ?></a></li>
							<li><a href="#tj-privacy-contact"><?php esc_html_e( 'Contact us', 'techjossecom' ); ?></a></li>
						</ol>
					</nav>

					<div class="tj-policy-body">
						<?php
						$tj_content = get_the_content();
						if ( trim( wp_strip_all_tags( $tj_content ) ) !== '' ) {
							the_content();
						}
						?>

						<section id="tj-privacy-collect">
							<h2><?php esc_html_e( '1. Information we collect', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'We collect the details you give us when you place an order or contact us, such as your name, phone number, delivery address and email. We also collect basic technical information about how you use our website to help us improve it.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-privacy-use">
							<h2><?php esc_html_e( '2. How we use it', 'techjossecom' ); ?></h2>
							<ul>
								<li><?php esc_html_e( 'To process and deliver your orders.', 'techjossecom' ); ?></li>
								<li><?php esc_html_e( 'To provide customer support and respond to your questions.', 'techjossecom' ); ?></li>
								<li><?php esc_html_e( 'To improve our products, services and website experience.', 'techjossecom' ); ?></li>
							</ul>
						</section>

						<section id="tj-privacy-share">
							<h2><?php esc_html_e( '3. Sharing your information', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'We never sell your personal information. We only share it with the delivery partners and payment services needed to complete your order, or where the law requires us to.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-privacy-cookies">
							<h2><?php esc_html_e( '4. Cookies', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'Our website uses cookies to remember your cart and preferences and to understand how visitors use the site. You can control cookies through your browser settings at any time.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-privacy-security">
							<h2><?php esc_html_e( '5. Keeping your data safe', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'We take reasonable technical and organisational measures to protect your information against loss, misuse or unauthorised access.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-privacy-rights">
							<h2><?php esc_html_e( '6. Your rights', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'You can ask us to see, correct or delete the personal information we hold about you. To make a request, simply contact us and we will respond as quickly as we can.', 'techjossecom' ); ?></p>
						</section>

						<section id="tj-privacy-contact">
							<h2><?php esc_html_e( '7. Contact us', 'techjossecom' ); ?></h2>
							<p><?php esc_html_e( 'If you have any questions about this privacy policy, please reach out through our contact page and our team will be glad to help.', 'techjossecom' ); ?></p>
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
