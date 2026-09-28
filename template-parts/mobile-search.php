<?php
/**
 * Mobile search bar, opened from the bottom navigation.
 *
 * Separate from template-parts/live-search.php because the header box is hidden
 * below 768px: it stays in the DOM, so focusing "the first search input on the
 * page" on a phone lands on an invisible field and appears to do nothing.
 *
 * @package TechJosse_Commerce
 */

$techjossecom_live_search = (bool) techjossecom_mod( 'show_live_search' );
?>
<div id="tj-mobile-search" class="tj-mobile-search" hidden>
	<div class="tj-mobile-search-inner">
		<div class="tj-search tj-search--bar" data-tj-search>
			<form role="search" method="get" class="tj-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<label class="tj-screen-reader-text" for="tj-search-field-bar">
					<?php esc_html_e( 'Search for products', 'techjossecom' ); ?>
				</label>

				<span class="tj-search-field">
					<input
						type="search"
						id="tj-search-field-bar"
						class="tj-search-input"
						name="s"
						placeholder="<?php echo esc_attr( techjossecom_mod( 'search_placeholder' ) ); ?>"
						autocomplete="off"
						data-tj-search-input
						<?php echo $techjossecom_live_search ? 'aria-autocomplete="list" aria-expanded="false"' : ''; ?>
					/>

					<?php if ( $techjossecom_live_search ) : ?>
						<button type="button" class="tj-search-clear" data-tj-search-clear aria-label="<?php esc_attr_e( 'Clear search', 'techjossecom' ); ?>">
							<span aria-hidden="true">&times;</span>
						</button>
					<?php endif; ?>
				</span>

				<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<input type="hidden" name="post_type" value="product" />
				<?php endif; ?>

				<button type="submit" class="tj-search-submit" aria-label="<?php esc_attr_e( 'Search', 'techjossecom' ); ?>">
					<span aria-hidden="true"><?php echo techjossecom_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</button>
			</form>

			<?php if ( $techjossecom_live_search ) : ?>
				<div class="tj-live-search" data-tj-search-results role="listbox" aria-label="<?php esc_attr_e( 'Search suggestions', 'techjossecom' ); ?>" hidden></div>
			<?php endif; ?>
		</div>

		<button type="button" class="tj-mobile-search-close" data-tj-close="tj-mobile-search" aria-label="<?php esc_attr_e( 'Close search', 'techjossecom' ); ?>">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>
</div>
