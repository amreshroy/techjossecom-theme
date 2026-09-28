<?php
/**
 * 404 template.
 *
 * @package TechJosse_Commerce
 */

get_header();
?>
<main id="tj-main" class="tj-main" role="main">
	<div class="tj-container">
		<div class="tj-404">
			<p class="tj-404-code" aria-hidden="true">404</p>
			<h1 class="tj-page-title"><?php esc_html_e( 'We could not find that page', 'techjossecom' ); ?></h1>
			<p class="tj-404-text"><?php esc_html_e( 'The page may have been moved. Try searching for the product you need, or browse the shop.', 'techjossecom' ); ?></p>

			<?php get_search_form(); ?>

			<div class="tj-404-actions">
				<a class="tj-btn tj-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'techjossecom' ); ?></a>
				<a class="tj-btn tj-btn--ghost" href="<?php echo esc_url( techjossecom_shop_url() ); ?>"><?php esc_html_e( 'Browse the shop', 'techjossecom' ); ?></a>
			</div>

			<?php
			$techjossecom_404_cats = techjossecom_product_categories( 8 );

			if ( $techjossecom_404_cats ) :
				?>
				<div class="tj-404-cats">
					<h2 class="tj-section-title"><?php esc_html_e( 'Popular categories', 'techjossecom' ); ?></h2>
					<ul class="tj-chip-list">
						<?php foreach ( $techjossecom_404_cats as $techjossecom_term ) : ?>
							<li>
								<a class="tj-chip" href="<?php echo esc_url( get_term_link( $techjossecom_term ) ); ?>">
									<?php echo esc_html( $techjossecom_term->name ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</div>
</main>
<?php
get_footer();
