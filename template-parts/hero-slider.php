<?php
/**
 * Hero layout 2: a full width image slider.
 *
 * Every slide is a banner image wrapped in a link and nothing else - no
 * heading, no subtitle, no button - so the picture is the only thing the
 * visitor sees and the only thing they can click.
 *
 * A slide may carry a second, phone-sized picture. Those are offered to the
 * browser as a <picture> element, so a phone downloads the small artwork rather
 * than the full width desktop one. See techjossecom_hero_slide_image().
 *
 * The track is a CSS scroll-snap container, which means a touch swipe already
 * works with JavaScript switched off. The script in assets/js/main.js only
 * adds the arrows, the dots and the auto rotation on top of it.
 *
 * @package TechJosse_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$techjossecom_slides = techjossecom_hero_slides();

if ( ! $techjossecom_slides ) {
	return;
}

$techjossecom_total    = count( $techjossecom_slides );
$techjossecom_autoplay = techjossecom_mod( 'hero_slider_autoplay' );
$techjossecom_speed    = (int) techjossecom_mod( 'hero_slider_speed' );
$techjossecom_speed    = $techjossecom_speed > 0 ? $techjossecom_speed : 5;
?>
<section class="tj-hero tj-hero--slider">
	<div class="tj-container">
		<div class="tj-slider"
			data-tj-slider
			data-tj-slider-autoplay="<?php echo $techjossecom_autoplay ? 'true' : 'false'; ?>"
			data-tj-slider-speed="<?php echo esc_attr( $techjossecom_speed ); ?>">

			<div class="tj-slider__track" data-tj-slider-track>
				<?php foreach ( $techjossecom_slides as $techjossecom_index => $techjossecom_slide ) : ?>
					<div class="tj-slider__slide"
						role="group"
						aria-roledescription="<?php esc_attr_e( 'slide', 'techjossecom' ); ?>"
						aria-label="<?php
						echo esc_attr(
							sprintf(
								/* translators: 1: slide number, 2: total number of slides. */
								__( 'Slide %1$s of %2$s', 'techjossecom' ),
								$techjossecom_index + 1,
								$techjossecom_total
							)
						);
						?>">
						<?php
						$techjossecom_picture = techjossecom_hero_slide_image( $techjossecom_slide, $techjossecom_index );
						?>

						<?php if ( $techjossecom_slide['url'] && $techjossecom_picture ) : ?>
							<a class="tj-slider__link" href="<?php echo esc_url( $techjossecom_slide['url'] ); ?>">
								<?php
								echo $techjossecom_picture; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								?>
							</a>
						<?php else : ?>
							<?php
							echo $techjossecom_picture; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $techjossecom_total > 1 ) : ?>
				<button type="button" class="tj-slider__arrow tj-slider__arrow--prev" data-tj-slider-prev aria-label="<?php esc_attr_e( 'Previous banner', 'techjossecom' ); ?>">
					<span aria-hidden="true">&#8249;</span>
				</button>

				<button type="button" class="tj-slider__arrow tj-slider__arrow--next" data-tj-slider-next aria-label="<?php esc_attr_e( 'Next banner', 'techjossecom' ); ?>">
					<span aria-hidden="true">&#8250;</span>
				</button>

				<div class="tj-slider__dots" data-tj-slider-dots>
					<?php foreach ( $techjossecom_slides as $techjossecom_index => $techjossecom_slide ) : ?>
						<button type="button"
							class="tj-slider__dot<?php echo 0 === $techjossecom_index ? ' is-active' : ''; ?>"
							data-tj-slider-dot
							aria-current="<?php echo 0 === $techjossecom_index ? 'true' : 'false'; ?>">
							<span class="tj-screen-reader-text">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %d: slide number. */
										__( 'Go to banner %d', 'techjossecom' ),
										$techjossecom_index + 1
									)
								);
								?>
							</span>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
