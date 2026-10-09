<?php
/**
 * Template Name: Career Page
 * Template Post Type: page
 *
 * A careers page: a tinted hero, current openings as accessible
 * <details> accordions, the benefits of working here and how to apply.
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
					<p class="tj-page-hero-text"><?php esc_html_e( 'Join a team that is building a friendlier way to shop online in Bangladesh. We are always looking for people who care.', 'techjossecom' ); ?></p>
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
					<h2 class="tj-panel-title"><?php esc_html_e( 'Why work with us', 'techjossecom' ); ?></h2>
					<ul class="tj-check-list">
						<li><?php esc_html_e( 'A supportive team that grows together.', 'techjossecom' ); ?></li>
						<li><?php esc_html_e( 'Competitive salary and festival bonuses.', 'techjossecom' ); ?></li>
						<li><?php esc_html_e( 'Room to learn new skills and take on real responsibility.', 'techjossecom' ); ?></li>
						<li><?php esc_html_e( 'A friendly, respectful workplace culture.', 'techjossecom' ); ?></li>
					</ul>
				</section>

				<section class="tj-section">
					<div class="tj-section-head">
						<h2 class="tj-section-title"><?php esc_html_e( 'Current openings', 'techjossecom' ); ?></h2>
					</div>

					<details class="tj-opening">
						<summary>
							<span><?php esc_html_e( 'Customer Support Executive', 'techjossecom' ); ?><span class="tj-opening-meta"><?php esc_html_e( 'Full-time · Dhaka', 'techjossecom' ); ?></span></span>
						</summary>
						<div class="tj-opening-body">
							<p><?php esc_html_e( 'Help our customers by phone and chat before and after their orders.', 'techjossecom' ); ?></p>
							<ul>
								<li><?php esc_html_e( 'Good communication skills in Bangla and English.', 'techjossecom' ); ?></li>
								<li><?php esc_html_e( 'Comfortable using a computer and handling customer queries.', 'techjossecom' ); ?></li>
							</ul>
						</div>
					</details>

					<details class="tj-opening">
						<summary>
							<span><?php esc_html_e( 'Delivery &amp; Logistics Assistant', 'techjossecom' ); ?><span class="tj-opening-meta"><?php esc_html_e( 'Full-time · Dhaka', 'techjossecom' ); ?></span></span>
						</summary>
						<div class="tj-opening-body">
							<p><?php esc_html_e( 'Coordinate daily deliveries and keep our customers updated.', 'techjossecom' ); ?></p>
							<ul>
								<li><?php esc_html_e( 'Organised and good at planning routes.', 'techjossecom' ); ?></li>
								<li><?php esc_html_e( 'A valid driving licence is a plus.', 'techjossecom' ); ?></li>
							</ul>
						</div>
					</details>

					<details class="tj-opening">
						<summary>
							<span><?php esc_html_e( 'Digital Marketing Associate', 'techjossecom' ); ?><span class="tj-opening-meta"><?php esc_html_e( 'Full-time · Remote-friendly', 'techjossecom' ); ?></span></span>
						</summary>
						<div class="tj-opening-body">
							<p><?php esc_html_e( 'Grow our online presence through social media and campaigns.', 'techjossecom' ); ?></p>
							<ul>
								<li><?php esc_html_e( 'Familiar with Facebook, Instagram and basic analytics.', 'techjossecom' ); ?></li>
								<li><?php esc_html_e( 'Creative, with an eye for good content.', 'techjossecom' ); ?></li>
							</ul>
						</div>
					</details>
				</section>

				<section class="tj-panel">
					<h2 class="tj-panel-title"><?php esc_html_e( 'How to apply', 'techjossecom' ); ?></h2>
					<p><?php esc_html_e( 'Send your CV to the email below with the role you are applying for in the subject line. We review every application and will be in touch if there is a match.', 'techjossecom' ); ?></p>
					<ol class="tj-steps">
						<li><span class="tj-steps-title"><?php esc_html_e( 'Pick a role', 'techjossecom' ); ?></span> <?php esc_html_e( 'Choose an opening above that fits you.', 'techjossecom' ); ?></li>
						<li><span class="tj-steps-title"><?php esc_html_e( 'Send your CV', 'techjossecom' ); ?></span> <?php esc_html_e( 'Email it to us with the role in the subject line.', 'techjossecom' ); ?></li>
						<li><span class="tj-steps-title"><?php esc_html_e( 'Meet the team', 'techjossecom' ); ?></span> <?php esc_html_e( 'Shortlisted candidates are invited for a friendly chat.', 'techjossecom' ); ?></li>
					</ol>

					<?php $tj_career_email = techjossecom_mod( 'contact_email' ); ?>
					<?php if ( $tj_career_email ) : ?>
						<p class="tj-policy-note">
							<?php
							printf(
								/* translators: %s: contact email address. */
								esc_html__( 'Apply by emailing your CV to %s.', 'techjossecom' ),
								'<a href="mailto:' . esc_attr( $tj_career_email ) . '">' . esc_html( $tj_career_email ) . '</a>'
							);
							?>
						</p>
					<?php endif; ?>
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
