<?php
/**
 * Template Name: About Page
 * Template Post Type: page
 *
 * An "about us" page: a tinted hero, the company story, our values,
 * a highlight stats band and the reasons to trust the store.
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
					<p class="tj-page-hero-text"><?php esc_html_e( 'We are an online marketplace bringing genuine electrical, fire safety and industrial products to homes and businesses across Bangladesh — at honest prices, with delivery you can rely on.', 'techjossecom' ); ?></p>
				</div>

				<?php
				$tj_content = get_the_content();
				if ( trim( wp_strip_all_tags( $tj_content ) ) !== '' ) {
					echo '<div class="tj-entry-content">';
					the_content();
					echo '</div>';
				}
				?>

				<section class="tj-panel">
					<h2 class="tj-panel-title"><?php esc_html_e( 'Our story', 'techjossecom' ); ?></h2>
					<p><?php esc_html_e( 'We started with a simple idea: buying the right safety and electrical product should be easy, trustworthy and affordable. What began as a small shop has grown into a dependable online store serving thousands of customers, factories, contractors and workshops across the country.', 'techjossecom' ); ?></p>
					<p><?php esc_html_e( 'Every product on our shelves is chosen for quality and value, and every order is handled by a real person who cares that it reaches you on time.', 'techjossecom' ); ?></p>
				</section>

				<div class="tj-stats">
					<div class="tj-stat">
						<p class="tj-stat-value">10k+</p>
						<p class="tj-stat-label"><?php esc_html_e( 'Happy customers', 'techjossecom' ); ?></p>
					</div>
					<div class="tj-stat">
						<p class="tj-stat-value">5k+</p>
						<p class="tj-stat-label"><?php esc_html_e( 'Products available', 'techjossecom' ); ?></p>
					</div>
					<div class="tj-stat">
						<p class="tj-stat-value">64</p>
						<p class="tj-stat-label"><?php esc_html_e( 'Districts covered', 'techjossecom' ); ?></p>
					</div>
					<div class="tj-stat">
						<p class="tj-stat-value">100%</p>
						<p class="tj-stat-label"><?php esc_html_e( 'Genuine products', 'techjossecom' ); ?></p>
					</div>
				</div>

				<section class="tj-section">
					<div class="tj-section-head">
						<h2 class="tj-section-title"><?php esc_html_e( 'What we stand for', 'techjossecom' ); ?></h2>
					</div>

					<div class="tj-page-grid tj-page-grid--3">
						<div class="tj-info-card">
							<span class="tj-info-card-icon" aria-hidden="true"><?php echo techjossecom_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<div>
								<h3 class="tj-info-card-title"><?php esc_html_e( 'Quality first', 'techjossecom' ); ?></h3>
								<p class="tj-info-card-text"><?php esc_html_e( 'Only genuine, dependable products make it into our catalogue.', 'techjossecom' ); ?></p>
							</div>
						</div>

						<div class="tj-info-card">
							<span class="tj-info-card-icon" aria-hidden="true"><?php echo techjossecom_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<div>
								<h3 class="tj-info-card-title"><?php esc_html_e( 'Reliable delivery', 'techjossecom' ); ?></h3>
								<p class="tj-info-card-text"><?php esc_html_e( 'Fast, careful delivery to every corner of Bangladesh.', 'techjossecom' ); ?></p>
							</div>
						</div>

						<div class="tj-info-card">
							<span class="tj-info-card-icon" aria-hidden="true"><?php echo techjossecom_icon( 'chat' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<div>
								<h3 class="tj-info-card-title"><?php esc_html_e( 'Real support', 'techjossecom' ); ?></h3>
								<p class="tj-info-card-text"><?php esc_html_e( 'A friendly team on call to help before and after your order.', 'techjossecom' ); ?></p>
							</div>
						</div>
					</div>
				</section>

				<section class="tj-panel">
					<h2 class="tj-panel-title"><?php esc_html_e( 'Why shop with us', 'techjossecom' ); ?></h2>
					<ul class="tj-check-list">
						<li><?php esc_html_e( '100% genuine products, verified before they reach you.', 'techjossecom' ); ?></li>
						<li><?php esc_html_e( 'Cash on delivery available nationwide.', 'techjossecom' ); ?></li>
						<li><?php esc_html_e( 'Transparent pricing with no hidden charges.', 'techjossecom' ); ?></li>
						<li><?php esc_html_e( 'Easy returns and a team that resolves issues quickly.', 'techjossecom' ); ?></li>
					</ul>
				</section>

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
