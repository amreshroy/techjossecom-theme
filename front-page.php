<?php
/**
 * Front page: hero banners, service highlights, category grid, deals and
 * latest products.
 *
 * @package TechJosse_Commerce
 */

get_header();

$techjossecom_has_woo = class_exists( 'WooCommerce' );
?>
<main id="tj-main" class="tj-main tj-front-page" role="main">

	<?php if ( techjossecom_mod( 'show_front_heading' ) && techjossecom_mod( 'front_heading' ) ) : ?>
		<div class="tj-front-head">
			<div class="tj-container">
				<h1 class="tj-front-heading"><?php echo esc_html( techjossecom_mod( 'front_heading' ) ); ?></h1>
			</div>
		</div>
	<?php endif; ?>

	<?php
	/*
	 * The hero design is chosen in Appearance -> Theme Settings. Each layout is
	 * its own template part, so swapping (or adding) a design never touches
	 * this file.
	 *
	 * A slider with no banners uploaded would leave a gap at the top of the
	 * page, so the split layout is used instead until at least one slide has
	 * an image.
	 */
	$techjossecom_hero_layout = techjossecom_hero_layout();

	if ( 'slider' === $techjossecom_hero_layout && ! techjossecom_hero_slides() ) {
		$techjossecom_hero_layout = 'split';
	}

	get_template_part( 'template-parts/hero-' . $techjossecom_hero_layout );
	?>

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
	$techjossecom_categories        = techjossecom_mod( 'show_categories' ) ? techjossecom_product_categories( techjossecom_section_limit( 'categories_limit' ) ) : array();
	$techjossecom_categories_mobile = techjossecom_section_mobile_limit( 'categories_limit' );

	if ( $techjossecom_categories ) :
		?>
		<section class="tj-section tj-section--categories">
			<div class="tj-container">
				<div class="tj-section-head">
					<h2 class="tj-section-title"><?php echo esc_html( techjossecom_mod( 'categories_title' ) ); ?></h2>
					<a class="tj-section-link" href="<?php echo esc_url( techjossecom_shop_url() ); ?>"><?php esc_html_e( 'View all', 'techjossecom' ); ?> &rarr;</a>
				</div>

				<ul class="tj-category-grid"<?php echo $techjossecom_categories_mobile ? ' data-tj-mobile-limit="' . esc_attr( $techjossecom_categories_mobile ) . '"' : ''; ?>>
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
	$techjossecom_deals        = ( techjossecom_mod( 'show_deals' ) && $techjossecom_has_woo )
		? techjossecom_on_sale_products( techjossecom_section_limit( 'deals_limit' ) )
		: null;
	$techjossecom_deals_mobile = techjossecom_section_mobile_limit( 'deals_limit' );

	if ( $techjossecom_deals && $techjossecom_deals->have_posts() ) :
		?>
		<section class="tj-section tj-section--deals">
			<div class="tj-container">
				<div class="tj-section-head">
					<h2 class="tj-section-title"><?php echo esc_html( techjossecom_mod( 'deals_title' ) ); ?></h2>

					<div class="tj-section-tools">
						<?php
						/*
						 * The arrows sit in the heading, on the left of the "View
						 * All" link, at every screen size. They used to be a row of
						 * their own under the products, and before that floated over
						 * the middle of the row, which covered the very pictures
						 * they exist to scroll. In the heading they cover nothing and
						 * the track keeps the full width of the section.
						 *
						 * techjossecom_scroll_buttons() writes the buttons, their labels
						 * and their icons in one place for every carousel on the site,
						 * so a new section reuses this one by wrapping its product grid
						 * in .tj-carousel / [data-tj-scroller] and calling it inside its
						 * .tj-section-tools: no copied markup, no new CSS and no new
						 * JavaScript.
						 */
						techjossecom_scroll_buttons();
						?>

						<a class="tj-section-link" href="<?php echo esc_url( techjossecom_shop_url() ); ?>"><?php esc_html_e( 'View All', 'techjossecom' ); ?> &rsaquo;</a>
					</div>
				</div>

				<div class="tj-carousel">
					<div class="tj-scroller" data-tj-scroller<?php echo $techjossecom_deals_mobile ? ' data-tj-mobile-limit="' . esc_attr( $techjossecom_deals_mobile ) . '"' : ''; ?>>
						<?php techjossecom_product_grid( $techjossecom_deals, 4 ); ?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	$techjossecom_latest        = ( techjossecom_mod( 'show_latest' ) && $techjossecom_has_woo )
		? techjossecom_latest_products( techjossecom_section_limit( 'latest_limit' ) )
		: null;
	$techjossecom_latest_mobile = techjossecom_section_mobile_limit( 'latest_limit' );

	if ( $techjossecom_latest && $techjossecom_latest->have_posts() ) :
		?>
		<section class="tj-section tj-section--latest">
			<div class="tj-container">
				<div class="tj-section-head">
					<h2 class="tj-section-title"><?php echo esc_html( techjossecom_mod( 'latest_title' ) ); ?></h2>

					<div class="tj-section-tools">
						<?php techjossecom_scroll_buttons(); ?>

						<a class="tj-section-link" href="<?php echo esc_url( techjossecom_shop_url() ); ?>"><?php esc_html_e( 'Shop all', 'techjossecom' ); ?> &rarr;</a>
					</div>
				</div>

				<div class="tj-carousel">
					<div class="tj-scroller" data-tj-scroller<?php echo $techjossecom_latest_mobile ? ' data-tj-mobile-limit="' . esc_attr( $techjossecom_latest_mobile ) . '"' : ''; ?>>
						<?php techjossecom_product_grid( $techjossecom_latest, 4 ); ?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	/*
	 * Featured Products.
	 *
	 * A fourth product row, built from exactly the parts the two rows above are
	 * built from - the same .tj-section shell, the same heading with the same
	 * .tj-section-tools, techjossecom_scroll_buttons() for the arrows and
	 * techjossecom_product_grid() for the cards. That is the whole reason the
	 * arrows and the grid were written as shared functions, so this section adds
	 * a row of products without a line of new CSS or JavaScript and cannot drift
	 * away from the look of the rows around it.
	 *
	 * The products are the ones ticked "Featured" in WooCommerce → Products, so
	 * the section is filled from the shop rather than from a second list of
	 * settings that would have to be kept in step with the first.
	 */
	$techjossecom_featured        = ( techjossecom_mod( 'show_featured' ) && $techjossecom_has_woo )
		? techjossecom_featured_products( techjossecom_section_limit( 'featured_limit' ) )
		: null;
	$techjossecom_featured_mobile = techjossecom_section_mobile_limit( 'featured_limit' );

	if ( $techjossecom_featured && $techjossecom_featured->have_posts() ) :
		?>
		<section class="tj-section tj-section--featured">
			<div class="tj-container">
				<div class="tj-section-head">
					<h2 class="tj-section-title"><?php echo esc_html( techjossecom_mod( 'featured_title' ) ); ?></h2>

					<div class="tj-section-tools">
						<?php techjossecom_scroll_buttons(); ?>

						<a class="tj-section-link" href="<?php echo esc_url( techjossecom_shop_url() ); ?>"><?php esc_html_e( 'Shop all', 'techjossecom' ); ?> &rarr;</a>
					</div>
				</div>

				<div class="tj-carousel">
					<div class="tj-scroller" data-tj-scroller<?php echo $techjossecom_featured_mobile ? ' data-tj-mobile-limit="' . esc_attr( $techjossecom_featured_mobile ) . '"' : ''; ?>>
						<?php techjossecom_product_grid( $techjossecom_featured, 4 ); ?>
					</div>
				</div>
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

	<?php
	/*
	 * The homepage description block.
	 *
	 * The text comes from a theme setting rather than from an archive, so it is
	 * passed in; everything after that - the markup, the capped height and the
	 * "Read More" button - is the shared block the shop, category and blog
	 * archives print as well.
	 *
	 * The text is never shortened on the server: everything written in the
	 * setting is in the HTML, so search engines and readers without JavaScript
	 * get the whole block. Only the height on screen is capped, and only when
	 * the script that lifts the cap is running - the .tj-js class is set in
	 * header.php before the stylesheet is parsed, which is why the cap and the
	 * button are both written behind that class. A reader with no JavaScript
	 * therefore sees every word and is not shown a button that would do nothing.
	 *
	 * The script measures the block before capping it, so a description short
	 * enough to fit shows no button at all.
	 */
	$techjossecom_seo_title = techjossecom_mod( 'seo_title' );
	$techjossecom_seo_text  = techjossecom_mod( 'seo_text' );

	if ( techjossecom_mod( 'show_seo' ) && $techjossecom_seo_text ) :
		?>
		<div class="tj-container">
			<?php
			techjossecom_the_description_block(
				array(
					'title' => $techjossecom_seo_title,
					'text'  => $techjossecom_seo_text,
					'label' => techjossecom_mod( 'seo_read_more' ),
				)
			);
			?>
		</div>
	<?php endif; ?>
</main>
<?php
get_footer();

