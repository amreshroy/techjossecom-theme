<?php
/**
 * Post card used by the blog, archives and search results.
 *
 * @package TechJosse_Commerce
 */

$techjossecom_cats = get_the_category_list( ', ' );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'tj-card' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="tj-card-thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'medium_large' ); ?>
		</a>
	<?php endif; ?>

	<div class="tj-card-body">
		<?php if ( $techjossecom_cats ) : ?>
			<p class="tj-card-cats"><?php echo wp_kses_post( $techjossecom_cats ); ?></p>
		<?php endif; ?>

		<h2 class="tj-card-title">
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		</h2>

		<p class="tj-card-meta">
			<span><?php echo esc_html( get_the_date() ); ?></span>
		</p>

		<p class="tj-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>

		<a class="tj-card-more" href="<?php the_permalink(); ?>">
			<?php esc_html_e( 'Read more', 'techjossecom' ); ?> &rarr;
		</a>
	</div>
</article>
