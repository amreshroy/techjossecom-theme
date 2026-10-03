<?php
/**
 * Archive template (categories, tags, authors, dates, post type archives).
 *
 * @package TechJosse_Commerce
 */

get_header();
?>
<main id="tj-main" class="tj-main" role="main">
	<div class="tj-container">
		<?php techjossecom_breadcrumbs(); ?>

		<header class="tj-page-head">
			<h1 class="tj-page-title"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
			<?php
			/*
			 * The archive description is no longer printed here. It now appears
			 * once, under the pagination, in the collapsible block that every
			 * archive shares: the same words twice on one page would be
			 * duplicate content, and the block is the only copy.
			 */
			?>
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

				<?php techjossecom_the_description_block(); ?>
			</div>

			<?php get_sidebar(); ?>
		</div>
	</div>
</main>
<?php
get_footer();
