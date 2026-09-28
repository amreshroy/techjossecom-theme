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
			$techjossecom_description = get_the_archive_description();

			if ( $techjossecom_description ) {
				echo '<div class="tj-page-desc">' . wp_kses_post( $techjossecom_description ) . '</div>';
			}
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
			</div>

			<?php get_sidebar(); ?>
		</div>
	</div>
</main>
<?php
get_footer();
