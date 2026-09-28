<?php
/**
 * "Nothing found" message.
 *
 * @package TechJosse_Commerce
 */

if ( is_search() ) {
	$techjossecom_message = __( 'No results matched your search. Try a different keyword or browse the shop.', 'techjossecom' );
} else {
	$techjossecom_message = __( 'Nothing has been published here yet. Please check back soon.', 'techjossecom' );
}
?>
<div class="tj-no-results">
	<h2 class="tj-no-results-title"><?php esc_html_e( 'Nothing found', 'techjossecom' ); ?></h2>
	<p><?php echo esc_html( $techjossecom_message ); ?></p>

	<?php get_search_form(); ?>

	<div class="tj-no-results-actions">
		<a class="tj-btn tj-btn--primary" href="<?php echo esc_url( techjossecom_shop_url() ); ?>"><?php esc_html_e( 'Browse the shop', 'techjossecom' ); ?></a>
	</div>

	<?php
	$techjossecom_suggestions = techjossecom_product_categories( 6 );

	if ( $techjossecom_suggestions ) :
		?>
		<div class="tj-no-results-cats">
			<h3 class="tj-section-title"><?php esc_html_e( 'Shop by category', 'techjossecom' ); ?></h3>
			<ul class="tj-chip-list">
				<?php foreach ( $techjossecom_suggestions as $techjossecom_term ) : ?>
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
