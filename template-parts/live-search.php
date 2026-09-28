<?php
/**
 * Product search box with AJAX suggestions.
 *
 * @package TechJosse_Commerce
 *
 * @var array $args Template arguments: 'context' => header|mobile.
 */

$techjossecom_context     = isset( $args['context'] ) ? $args['context'] : 'header';
$techjossecom_field_id    = 'tj-search-field-' . $techjossecom_context;
$techjossecom_live_search = (bool) techjossecom_mod( 'show_live_search' );
?>
<div class="tj-search tj-search--<?php echo esc_attr( $techjossecom_context ); ?>" data-tj-search>
	<form role="search" method="get" class="tj-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="tj-screen-reader-text" for="<?php echo esc_attr( $techjossecom_field_id ); ?>">
			<?php esc_html_e( 'Search for products', 'techjossecom' ); ?>
		</label>

		<?php
		$techjossecom_selected_cat = isset( $_GET['product_cat'] ) ? sanitize_title( wp_unslash( $_GET['product_cat'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'header' === $techjossecom_context && class_exists( 'WooCommerce' ) ) :
			$techjossecom_categories = techjossecom_product_categories( 24 );
			?>
			<span class="tj-search-cat">
				<select
					id="<?php echo esc_attr( $techjossecom_field_id ); ?>-cat"
					class="tj-search-cat-select"
					name="product_cat"
					aria-label="<?php esc_attr_e( 'All Categories', 'techjossecom' ); ?>"
				>
					<option value=""><?php esc_html_e( 'All Categories', 'techjossecom' ); ?></option>

					<?php foreach ( $techjossecom_categories as $techjossecom_category ) : ?>
						<option value="<?php echo esc_attr( $techjossecom_category->slug ); ?>" <?php selected( $techjossecom_selected_cat, $techjossecom_category->slug ); ?>>
							<?php echo esc_html( $techjossecom_category->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<span class="tj-search-cat-caret" aria-hidden="true"><?php echo techjossecom_icon( 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</span>
		<?php endif; ?>

		<input
			type="search"
			id="<?php echo esc_attr( $techjossecom_field_id ); ?>"
			class="tj-search-input"
			name="s"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			placeholder="<?php echo esc_attr( techjossecom_mod( 'search_placeholder' ) ); ?>"
			autocomplete="off"
			data-tj-search-input
			<?php echo $techjossecom_live_search ? 'aria-autocomplete="list" aria-expanded="false"' : ''; ?>
		/>

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
