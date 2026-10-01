<?php
/**
 * Hero layout 1: one large banner on the left, two smaller banners stacked on
 * the right. Text, subtitle and the shop button come from the settings screen.
 *
 * On a phone the three banners are shown one after another with the heading,
 * the subtitle and the button laid over the large one, and a badge and a title
 * under the small ones.
 *
 * From 992px up the layout becomes a flush wall of pictures: the banners fill
 * their boxes edge to edge at one height, one large beside two small ones, and
 * the row is kept short so it reads as a wide band. Only the stylesheet changes
 * at that width - the same markup is sent to everybody, which keeps one cached
 * page correct for both sizes. The heading, the subtitle, the button and each
 * small banner's badge and title are all still shown there, over a gradient at
 * the foot of each picture, so the words entered in the theme settings appear
 * on a desktop as well as on a phone. The heading stays in the document as real
 * text for screen readers and search engines, and the large banner is wrapped
 * in the same link the "Shop Now" button points at, so the text never becomes
 * the only way into the shop.
 *
 * Banner artwork, all three at one 2.5:1 ratio so the row lines up:
 *
 *   Large banner    1200 x 480 px  (techjossecom_hero_banner_size_label( 'main' ))
 *   Small banners    600 x 240 px  (techjossecom_hero_banner_size_label( 'side' ))
 *
 * A file that is close but not exact is cropped to fill its box rather than
 * squashed, so keep the message away from the outer edge of the artwork.
 *
 * @package TechJosse_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$techjossecom_hero_button_url = techjossecom_mod( 'hero1_button_url' );
$techjossecom_hero_button_url = $techjossecom_hero_button_url ? $techjossecom_hero_button_url : techjossecom_shop_url();
?>
<section class="tj-hero">
	<div class="tj-container tj-hero-grid">
		<div class="tj-hero-main">
			<?php
			$techjossecom_hero1      = techjossecom_mod( 'hero1_image' );
			$techjossecom_hero1_spec = techjossecom_hero_banner_spec( 'main' );
			$techjossecom_hero1_alt  = techjossecom_mod( 'hero1_title' );

			/*
			 * The large banner carries the heading on a phone, so its alt text is
			 * the heading. From 992px up the overlay is hidden and the alt is then
			 * the only description of the picture a visitor or a crawler gets.
			 * An empty setting leaves the media library's own alt text in place
			 * rather than overwriting it with an empty attribute.
			 */
			$techjossecom_hero1_attr = array(
				'class'         => 'tj-hero-img',
				'loading'       => 'eager',
				'fetchpriority' => 'high',
				'decoding'      => 'sync',
				'sizes'         => $techjossecom_hero1_spec['sizes'],
			);

			if ( $techjossecom_hero1_alt ) {
				$techjossecom_hero1_attr['alt'] = $techjossecom_hero1_alt;
			}
			?>
			<a class="tj-hero-link" href="<?php echo esc_url( $techjossecom_hero_button_url ); ?>">
				<?php
				if ( $techjossecom_hero1 ) {
					echo techjossecom_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$techjossecom_hero1,
						$techjossecom_hero1_spec['image_size'],
						$techjossecom_hero1_attr
					);
				} else {
					echo techjossecom_placeholder( 'tj-hero-img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</a>

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
			$techjossecom_hero_side_spec = techjossecom_hero_banner_spec( 'side' );

			foreach ( array( 2, 3 ) as $techjossecom_index ) {
				$techjossecom_image_id = techjossecom_mod( 'hero' . $techjossecom_index . '_image' );
				$techjossecom_url      = techjossecom_mod( 'hero' . $techjossecom_index . '_url' );
				$techjossecom_url      = $techjossecom_url ? $techjossecom_url : techjossecom_shop_url();
				$techjossecom_badge    = techjossecom_mod( 'hero' . $techjossecom_index . '_badge' );
				$techjossecom_title    = techjossecom_mod( 'hero' . $techjossecom_index . '_title' );

				if ( ! $techjossecom_image_id && ! $techjossecom_title ) {
					continue;
				}

				/*
				 * The small banners are the only thing carrying their message on a
				 * desktop, where the text underneath them is hidden, so the title
				 * doubles as the alt text. The badge is only ever a short label
				 * above the title, so it is left out rather than repeated.
				 */
				$techjossecom_card_attr = array(
					'class' => 'tj-hero-card-img',
					'sizes' => $techjossecom_hero_side_spec['sizes'],
				);

				if ( $techjossecom_title ) {
					$techjossecom_card_attr['alt'] = $techjossecom_title;
				}
				?>
				<a class="tj-hero-card" href="<?php echo esc_url( $techjossecom_url ); ?>">
					<?php
					if ( $techjossecom_image_id ) {
						echo techjossecom_image( $techjossecom_image_id, $techjossecom_hero_side_spec['image_size'], $techjossecom_card_attr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
