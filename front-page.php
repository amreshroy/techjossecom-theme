<?php
/**
 * Front page: hero banners, service highlights, category grid, deals and
 * latest products.
 *
 * @package TechJosse_Commerce
 */

get_header();

$techjossecom_hero_button_url = techjossecom_mod( 'hero1_button_url' );
$techjossecom_hero_button_url = $techjossecom_hero_button_url ? $techjossecom_hero_button_url : techjossecom_shop_url();
$techjossecom_has_woo        = class_exists( 'WooCommerce' );
?>
<main id="tj-main" class="tj-main tj-front-page" role="main">

	<?php if ( techjossecom_mod( 'show_front_heading' ) && techjossecom_mod( 'front_heading' ) ) : ?>
		<div class="tj-front-head">
			<div class="tj-container">
				<h1 class="tj-front-heading"><?php echo esc_html( techjossecom_mod( 'front_heading' ) ); ?></h1>
			</div>
		</div>
	<?php endif; ?>

	<section class="tj-hero">
		<div class="tj-container tj-hero-grid">
			<div class="tj-hero-main">
				<?php
				$techjossecom_hero1 = techjossecom_mod( 'hero1_image' );

				if ( $techjossecom_hero1 ) {
					echo techjossecom_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$techjossecom_hero1,
						'full',
						array(
							'class'         => 'tj-hero-img',
							'loading'       => 'eager',
							'fetchpriority' => 'high',
							'decoding'      => 'sync',
						)
					);
				} else {
					echo techjossecom_placeholder( 'tj-hero-img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>

				<div class="tj-hero-content">
					<?php if ( techjossecom_mod( 'hero1_title' ) ) : ?>
						<h2 class="tj-hero-title"><?php echo esc_html( techjossecom_mod( 'hero1_title' ) ); ?></h2>
					<?php endif; ?>

					<?php if ( techjossecom_mod( 'hero1_subtitle' ) ) : ?>
						<p class="tj-hero-subtitle"><?php echo esc_html( techjossecom_mod( 'hero1_subtitle' ) ); ?></p>
					<?php endif; ?>

					<?php if ( techjossecom_mod( 'hero1_button_text' ) ) : ?>
						<a class="tj-btn tj-btn--accent tj-btn--lg" href="<?php echo esc_url( $techjossecom_hero_button_url ); ?>">
							<?php echo esc_html( techjossecom_mod( 'hero1_button_text' ) ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<div class="tj-hero-side">
				<?php
				foreach ( array( 2, 3 ) as $techjossecom_index ) {
					$techjossecom_image_id = techjossecom_mod( 'hero' . $techjossecom_index . '_image' );
					$techjossecom_url      = techjossecom_mod( 'hero' . $techjossecom_index . '_url' );
					$techjossecom_url      = $techjossecom_url ? $techjossecom_url : techjossecom_shop_url();
					$techjossecom_badge    = techjossecom_mod( 'hero' . $techjossecom_index . '_badge' );
					$techjossecom_title    = techjossecom_mod( 'hero' . $techjossecom_index . '_title' );

					if ( ! $techjossecom_image_id && ! $techjossecom_title ) {
						continue;
					}
					?>
					<a class="tj-hero-card" href="<?php echo esc_url( $techjossecom_url ); ?>">
						<?php
						if ( $techjossecom_image_id ) {
							echo techjossecom_image( $techjossecom_image_id, 'large', array( 'class' => 'tj-hero-card-img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>

						<span class="tj-hero-card-text">
							<?php if ( $techjossecom_badge ) : ?>
								<span class="tj-hero-card-badge"><?php echo esc_html( $techjossecom_badge ); ?></span>
							<?php endif; ?>

							<?php if ( $techjossecom_title ) : ?>
								<span class="tj-hero-card-title"><?php echo esc_html( $techjossecom_title ); ?></span>
							<?php endif; ?>
						</span>
					</a>
					<?php
				}
				?>
			</div>
		</div>
	</section>

	<?php if ( techjossecom_mod( 'show_features' ) ) : ?>
		<section class="tj-features">
			<div class="tj-container tj-features-grid">
				<?php
				$techjossecom_icons = array( techjossecom_icon( 'check' ), techjossecom_icon( 'truck' ), techjossecom_icon( 'phone' ), techjossecom_icon( 'tag' ) );

				for ( $techjossecom_i = 1; $techjossecom_i <= 4; $techjossecom_i++ ) {
					$techjossecom_title = techjossecom_mod( 'feature' . $techjossecom_i . '_title' );
					$techjossecom_text  = techjossecom_mod( 'feature' . $techjossecom_i . '_text' );

					if ( ! $techjossecom_title && ! $techjossecom_text ) {
						continue;
					}

					printf(
						'<div class="tj-feature"><span class="tj-feature-icon" aria-hidden="true">%1$s</span><div class="tj-feature-text"><strong>%2$s</strong><span>%3$s</span></div></div>',
						$techjossecom_icons[ $techjossecom_i - 1 ],
						esc_html( $techjossecom_title ),
						esc_html( $techjossecom_text )
					);
				}
				?>
			</div>
		</section>
	<?php endif; ?>
	<?php
	$techjossecom_categories = techjossecom_mod( 'show_categories' ) ? techjossecom_product_categories( techjossecom_mod( 'categories_limit' ) ) : array();

	if ( $techjossecom_categories ) :
		?>
		<section class="tj-section tj-section--categories">
			<div class="tj-container">
				<div class="tj-section-head">
					<h2 class="tj-section-title"><?php echo esc_html( techjossecom_mod( 'categories_title' ) ); ?></h2>
					<a class="tj-section-link" href="<?php echo esc_url( techjossecom_shop_url() ); ?>"><?php esc_html_e( 'View all', 'techjossecom' ); ?> &rarr;</a>
				</div>

				<ul class="tj-category-grid">
					<?php foreach ( $techjossecom_categories as $techjossecom_term ) : ?>
						<?php $techjossecom_thumb_id = (int) get_term_meta( $techjossecom_term->term_id, 'thumbnail_id', true ); ?>
						<li class="tj-category-card">
							<a href="<?php echo esc_url( get_term_link( $techjossecom_term ) ); ?>">
								<span class="tj-category-thumb">
									<?php
									if ( $techjossecom_thumb_id ) {
										echo wp_get_attachment_image( $techjossecom_thumb_id, 'techjossecom-category', false, array( 'loading' => 'lazy', 'alt' => $techjossecom_term->name ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									} else {
										echo '<span class="tj-category-icon" aria-hidden="true">&#128230;</span>';
									}
									?>
								</span>
								<span class="tj-category-name"><?php echo esc_html( $techjossecom_term->name ); ?></span>
								<span class="tj-category-count">
									<?php
									printf(
										/* translators: %s: product count. */
										esc_html( _n( '%s item', '%s items', $techjossecom_term->count, 'techjossecom' ) ),
										esc_html( number_format_i18n( $techjossecom_term->count ) )
									);
									?>
								</span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php
	$techjossecom_deals = ( techjossecom_mod( 'show_deals' ) && $techjossecom_has_woo )
		? techjossecom_on_sale_products( techjossecom_mod( 'deals_limit' ) )
		: null;

	if ( $techjossecom_deals && $techjossecom_deals->have_posts() ) :
		?>
		<section class="tj-section tj-section--deals">
			<div class="tj-container">
				<div class="tj-section-head">
					<h2 class="tj-section-title"><?php echo esc_html( techjossecom_mod( 'deals_title' ) ); ?></h2>

					<div class="tj-section-tools">
						<a class="tj-section-link" href="<?php echo esc_url( techjossecom_shop_url() ); ?>"><?php esc_html_e( 'View All', 'techjossecom' ); ?> &rsaquo;</a>

						<span class="tj-scroll-buttons">
							<button type="button" class="tj-scroll-btn" data-tj-scroll="prev" aria-label="<?php esc_attr_e( 'Previous products', 'techjossecom' ); ?>">&#8249;</button>
							<button type="button" class="tj-scroll-btn" data-tj-scroll="next" aria-label="<?php esc_attr_e( 'Next products', 'techjossecom' ); ?>">&#8250;</button>
						</span>
					</div>
				</div>

				<div class="tj-scroller" data-tj-scroller>
					<?php techjossecom_product_grid( $techjossecom_deals, 4 ); ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	$techjossecom_latest = $techjossecom_has_woo ? techjossecom_latest_products( 8 ) : null;

	if ( $techjossecom_latest && $techjossecom_latest->have_posts() ) :
		?>
		<section class="tj-section tj-section--latest">
			<div class="tj-container">
				<div class="tj-section-head">
					<h2 class="tj-section-title"><?php esc_html_e( 'New Arrivals', 'techjossecom' ); ?></h2>
					<a class="tj-section-link" href="<?php echo esc_url( techjossecom_shop_url() ); ?>"><?php esc_html_e( 'Shop all', 'techjossecom' ); ?> &rarr;</a>
				</div>

				<?php techjossecom_product_grid( $techjossecom_latest, 4 ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php
	$techjossecom_intro       = techjossecom_mod( 'intro_text' );
	$techjossecom_page_id     = (int) get_option( 'page_on_front' );
	$techjossecom_page_object = ( $techjossecom_page_id && get_queried_object_id() === $techjossecom_page_id ) ? get_post( $techjossecom_page_id ) : null;

	if ( techjossecom_mod( 'show_intro' ) && ( $techjossecom_intro || ( $techjossecom_page_object && $techjossecom_page_object->post_content ) ) ) :
		?>
		<section class="tj-section tj-section--intro">
			<div class="tj-container tj-intro">
				<h2 class="tj-section-title"><?php echo esc_html( techjossecom_mod( 'intro_title' ) ); ?></h2>

				<?php
				if ( $techjossecom_page_object && $techjossecom_page_object->post_content ) {
					echo '<div class="tj-entry-content">' . apply_filters( 'the_content', $techjossecom_page_object->post_content ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} elseif ( $techjossecom_intro ) {
					echo wpautop( wp_kses_post( $techjossecom_intro ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</div>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer();

