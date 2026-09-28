<?php
/**
 * Single post template.
 *
 * @package TechJosse_Commerce
 */

get_header();
?>
<main id="tj-main" class="tj-main" role="main">
	<div class="tj-container">
		<?php techjossecom_breadcrumbs(); ?>

		<div class="tj-layout tj-layout--sidebar">
			<div class="tj-layout-content">
				<?php
				while ( have_posts() ) :
					the_post();

					$techjossecom_cats = get_the_category_list( ', ' );
					$techjossecom_tags = get_the_tag_list( '', ', ' );
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'tj-entry tj-entry--single' ); ?>>
						<header class="tj-entry-head">
							<?php if ( $techjossecom_cats ) : ?>
								<p class="tj-entry-cats"><?php echo wp_kses_post( $techjossecom_cats ); ?></p>
							<?php endif; ?>

							<h1 class="tj-page-title"><?php the_title(); ?></h1>

							<p class="tj-entry-meta">
								<span class="tj-entry-meta-item"><?php echo esc_html( get_the_date() ); ?></span>
								<span class="tj-entry-meta-item"><?php echo esc_html( get_the_author() ); ?></span>
								<span class="tj-entry-meta-item"><?php echo esc_html( sprintf( /* translators: %s: comment count. */ _n( '%s comment', '%s comments', get_comments_number(), 'techjossecom' ), number_format_i18n( get_comments_number() ) ) ); ?></span>
							</p>

							<div class="tj-entry-share">
								<?php if ( techjossecom_mod( 'show_single_share' ) ) : ?>
									<?php
									$techjossecom_url   = rawurlencode( get_permalink() );
									$techjossecom_title = rawurlencode( get_the_title() );
									?>
									<a class="tj-share-link tj-share-link--facebook" href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . $techjossecom_url ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php esc_attr_e( 'Share on Facebook', 'techjossecom' ); ?>"><?php echo techjossecom_icon( 'facebook' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
									<a class="tj-share-link tj-share-link--whatsapp" href="<?php echo esc_url( 'https://api.whatsapp.com/send?text=' . $techjossecom_title . '%20' . $techjossecom_url ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php esc_attr_e( 'Share on WhatsApp', 'techjossecom' ); ?>"><?php echo techjossecom_icon( 'whatsapp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
									<a class="tj-share-link tj-share-link--x" href="<?php echo esc_url( 'https://twitter.com/intent/tweet?text=' . $techjossecom_title . '&url=' . $techjossecom_url ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php esc_attr_e( 'Share on X', 'techjossecom' ); ?>"><?php echo techjossecom_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
								<?php endif; ?>
							</div>
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

						<?php if ( $techjossecom_tags ) : ?>
							<footer class="tj-entry-foot">
								<p class="tj-entry-tags"><?php echo wp_kses_post( $techjossecom_tags ); ?></p>
							</footer>
						<?php endif; ?>
					</article>

					<nav class="tj-post-nav">
						<?php
						previous_post_link( '<span class="tj-post-nav-prev">%link</span>', '&larr; %title' );
						next_post_link( '<span class="tj-post-nav-next">%link</span>', '%title &rarr;' );
						?>
					</nav>

					<?php
					if ( comments_open() || get_comments_number() ) {
						comments_template();
					}
				endwhile;
				?>
			</div>

			<?php get_sidebar(); ?>
		</div>
	</div>
</main>
<?php
get_footer();
