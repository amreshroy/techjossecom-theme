<?php
/**
 * Static page template.
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
				<header class="tj-entry-head">
					<h1 class="tj-page-title"><?php the_title(); ?></h1>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<div class="tj-entry-thumb">
						<?php the_post_thumbnail( 'large', array( 'fetchpriority' => 'high' ) ); ?>
					</div>
				<?php endif; ?>

				<div class="tj-entry-content">
					<?php
					the_content();

					wp_link_pages(
						array(
							'before' => '<nav class="tj-page-links">' . esc_html__( 'Pages:', 'techjossecom' ),
							'after'  => '</nav>',
						)
					);
					?>
				</div>
			</article>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();
