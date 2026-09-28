<?php
/**
 * "All Categories" mega menu built from the WooCommerce product categories.
 *
 * @package TechJosse_Commerce
 *
 * @var array $args Template arguments: 'context' => desktop|mobile.
 */

$techjossecom_context    = isset( $args['context'] ) ? $args['context'] : 'desktop';
$techjossecom_is_mobile  = 'mobile' === $techjossecom_context;

/*
 * The desktop dropdown is a fixed, short panel, so it shows the twelve most
 * populated categories. The mobile drawer scrolls, so it lists every category
 * instead of silently dropping the tail. A limit of 0 means "no limit".
 */
$techjossecom_categories = techjossecom_product_categories( $techjossecom_is_mobile ? 0 : 12 );

if ( ! $techjossecom_categories ) {
	return;
}

/**
 * Children for one category.
 *
 * The desktop fly-out has room for a handful of links, so it caps the list at
 * eight. The mobile drawer loops every child instead, so it asks for 0, which
 * means "no limit".
 *
 * @param WP_Term $term  Parent term.
 * @param int     $limit Maximum children, 0 for no limit.
 * @return array
 */
$techjossecom_children_of = function ( $term, $limit = 8 ) {
	$children = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $term->term_id,
			'hide_empty' => true,
			'number'     => absint( $limit ),
		)
	);

	return ( is_wp_error( $children ) || empty( $children ) ) ? array() : $children;
};

if ( $techjossecom_is_mobile ) :
	?>
	<div class="tj-mobile-cats">
		<h3 class="tj-mobile-cats-title"><?php esc_html_e( 'Shop by Category', 'techjossecom' ); ?></h3>
		<ul class="tj-mobile-cats-list">
			<?php foreach ( $techjossecom_categories as $techjossecom_term ) : ?>
				<?php $techjossecom_children = $techjossecom_children_of( $techjossecom_term, 0 ); ?>
				<li class="tj-mobile-cat">
					<a href="<?php echo esc_url( get_term_link( $techjossecom_term ) ); ?>">
						<span class="tj-mobile-cat-name"><?php echo esc_html( $techjossecom_term->name ); ?></span>
						<span class="tj-cat-count">(<?php echo esc_html( $techjossecom_term->count ); ?>)</span>
					</a>

					<?php if ( $techjossecom_children ) : ?>
						<ul class="tj-mobile-cat-children">
							<?php foreach ( $techjossecom_children as $techjossecom_child ) : ?>
								<li>
									<a href="<?php echo esc_url( get_term_link( $techjossecom_child ) ); ?>">
										<span class="tj-mobile-cat-name"><?php echo esc_html( $techjossecom_child->name ); ?></span>
										<span class="tj-cat-count">(<?php echo esc_html( $techjossecom_child->count ); ?>)</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
	return;
endif;
?>
<div class="tj-catmenu">
	<button type="button" class="tj-catmenu-toggle" data-tj-toggle="tj-catmenu-panel" aria-expanded="false" aria-controls="tj-catmenu-panel">
		<span class="tj-catmenu-grid" aria-hidden="true"><?php echo techjossecom_icon( 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<?php esc_html_e( 'All Categories', 'techjossecom' ); ?>
		<span class="tj-caret" aria-hidden="true"><?php echo techjossecom_icon( 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	</button>

	<div id="tj-catmenu-panel" class="tj-catmenu-panel" hidden>
		<ul class="tj-catmenu-list">
			<?php foreach ( $techjossecom_categories as $techjossecom_term ) : ?>
				<?php
				$techjossecom_children = $techjossecom_children_of( $techjossecom_term );
				$techjossecom_image_id = (int) get_term_meta( $techjossecom_term->term_id, 'thumbnail_id', true );
				?>
				<li class="tj-catmenu-item">
					<a class="tj-catmenu-link" href="<?php echo esc_url( get_term_link( $techjossecom_term ) ); ?>">
						<span class="tj-catmenu-icon">
							<?php
							if ( $techjossecom_image_id ) {
								echo wp_get_attachment_image( $techjossecom_image_id, 'techjossecom-category', false, array( 'loading' => 'lazy', 'alt' => $techjossecom_term->name ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							} else {
								echo '<span aria-hidden="true">&#128230;</span>';
							}
							?>
						</span>
						<span class="tj-catmenu-name"><?php echo esc_html( $techjossecom_term->name ); ?></span>
						<span class="tj-cat-count"><?php echo esc_html( $techjossecom_term->count ); ?></span>

						<?php if ( $techjossecom_children ) : ?>
							<span class="tj-catmenu-chevron" aria-hidden="true"><?php echo techjossecom_icon( 'chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
					</a>

					<?php if ( $techjossecom_children ) : ?>
						<ul class="tj-catmenu-sub">
							<?php foreach ( $techjossecom_children as $techjossecom_child ) : ?>
								<li>
									<a href="<?php echo esc_url( get_term_link( $techjossecom_child ) ); ?>"><?php echo esc_html( $techjossecom_child->name ); ?></a>
								</li>
							<?php endforeach; ?>
						</ul>

						<div class="tj-catmenu-flyout">
							<p class="tj-catmenu-flyout-title"><?php echo esc_html( $techjossecom_term->name ); ?></p>

							<ul class="tj-catmenu-flyout-list">
								<?php foreach ( $techjossecom_children as $techjossecom_child ) : ?>
									<li>
										<a href="<?php echo esc_url( get_term_link( $techjossecom_child ) ); ?>">
											<?php echo esc_html( $techjossecom_child->name ); ?>
											<span class="tj-cat-count"><?php echo esc_html( $techjossecom_child->count ); ?></span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<a class="tj-catmenu-all" href="<?php echo esc_url( techjossecom_shop_url() ); ?>">
			<?php esc_html_e( 'Browse all products', 'techjossecom' ); ?> &rarr;
		</a>
	</div>
</div>
