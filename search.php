<?php
/**
 * Search results template.
 *
 * @package TechJosse_Commerce
 */

get_header();
?>
<main id="tj-main" class="tj-main" role="main">
	<div class="tj-container">
		<?php techjossecom_breadcrumbs(); ?>

		<header class="tj-page-head">
			<h1 class="tj-page-title">
				<?php
				printf(
					/* translators: %s: search query. */
					esc_html__( 'Search results for "%s"', 'techjossecom' ),
					esc_html( get_search_query() )
				);
				?>
			</h1>
			<p class="tj-page-desc">
				<?php
				printf(
					/* translators: %s: number of results. */
					esc_html( _n( '%s result found.', '%s results found.', (int) $GLOBALS['wp_query']->found_posts, 'techjossecom' ) ),
					esc_html( number_format_i18n( (int) $GLOBALS['wp_query']->found_posts ) )
				);
				?>
			</p>
		</header>

		<div class="tj-layout tj-layout--sidebar">
			<div class="tj-layout-content">
				<?php if ( have_posts() ) : ?>
					<div class="tj-post-grid">
						<?php
						while ( have_posts() ) :
							the_post();
							get_template_part( 'template-parts/content', 'card' );
						endwhile;
						?>
					</div>

					<?php techjossecom_pagination(); ?>
				<?php else : ?>
					<?php get_template_part( 'template-parts/content', 'none' ); ?>
				<?php endif; ?>
			</div>

			<?php get_sidebar(); ?>
		</div>
	</div>
</main>
<?php
get_footer();
