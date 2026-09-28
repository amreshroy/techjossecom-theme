<?php
/**
 * "All Categories" mega menu built from the WooCommerce product categories.
 *
 * @package TechJosse_Commerce
 *
 * @var array $args Template arguments: 'context' => desktop|mobile.
 */

$techjossecom_context    = isset( $args['context'] ) ? $args['context'] : 'desktop';
$techjossecom_categories = techjossecom_product_categories( 12 );

if ( ! $techjossecom_categories ) {
	return;
}

/**
 * Children for one category, limited to eight.
 *
 * @param WP_Term $term Parent term.
 * @return array
 */
$techjossecom_children_of = function ( $term ) {
	$children = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => $term->term_id,
			'hide_empty' => true,
			'number'     => 8,
		)
	);

	return ( is_wp_error( $children ) || empty( $children ) ) ? array() : $children;
};

if ( 'mobile' === $techjossecom_context ) :
	?>
	<div class="tj-mobile-cats">
		<h3 class="tj-mobile-cats-title"><?php esc_html_e( 'Shop by Category', 'techjossecom' ); ?></h3>
		<ul class="tj-mobile-cats-list">
			<?php foreach ( $techjossecom_categories as $techjossecom_term ) : ?>
				<?php $techjossecom_children = $techjossecom_children_of( $techjossecom_term ); ?>
				<li class="tj-mobile-cat">
					<?php if ( $techjossecom_children ) : ?>
						<details>
							<summary><?php echo esc_html( $techjossecom_term->name ); ?></summary>
							<ul>
								<?php foreach ( $techjossecom_children as $techjossecom_child ) : ?>
									<li>
										<a href="<?php echo esc_url( get_term_link( $techjossecom_child ) ); ?>">
											<?php echo esc_html( $techjossecom_child->name ); ?>
											<span class="tj-cat-count">(<?php echo esc_html( $techjossecom_child->count ); ?>)</span>
										</a>
									</li>
								<?php endforeach; ?>
								<li>
									<a class="tj-mobile-cat-all" href="<?php echo esc_url( get_term_link( $techjossecom_term ) ); ?>">
										<?php esc_html_e( 'View all', 'techjossecom' ); ?>
									</a>
								</li>
							</ul>
						</details>
					<?php else : ?>
						<a href="<?php echo esc_url( get_term_link( $techjossecom_term ) ); ?>">
							<?php echo esc_html( $techjossecom_term->name ); ?>
							<span class="tj-cat-count">(<?php echo esc_html( $techjossecom_term->count ); ?>)</span>
						</a>
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
