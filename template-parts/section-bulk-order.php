<?php
/**
 * Bulk Order / Wholesale section template.
 *
 * Left side: heading, description, quick-order button.
 * Right side: admin-selected image.
 *
 * @package TechJosse_Commerce
 */

$techjossecom_bulk_title   = techjossecom_mod( 'bulk_order_title' );
$techjossecom_bulk_sub     = techjossecom_mod( 'bulk_order_subtitle' );
$techjossecom_bulk_btn     = techjossecom_mod( 'bulk_order_button_text' );
$techjossecom_bulk_link    = techjossecom_mod( 'bulk_order_link' );
$techjossecom_bulk_image   = (int) techjossecom_mod( 'bulk_order_image' );

if ( ! $techjossecom_bulk_title && ! $techjossecom_bulk_sub ) {
return;
}
?>
<section class="tj-section tj-section--bulk-order">
<div class="tj-container">
<div class="tj-bulk-order">
<div class="tj-bulk-order__content">
<?php if ( $techjossecom_bulk_title ) : ?>
<h2 class="tj-bulk-order__title"><?php echo esc_html( $techjossecom_bulk_title ); ?></h2>
<?php endif; ?>

<?php if ( $techjossecom_bulk_sub ) : ?>
<div class="tj-bulk-order__subtitle"><?php echo wp_kses_post( wpautop( $techjossecom_bulk_sub ) ); ?></div>
<?php endif; ?>

<?php if ( $techjossecom_bulk_link ) : ?>
<?php if ( false !== strpos( $techjossecom_bulk_link, '[' ) ) : ?>
<?php echo do_shortcode( $techjossecom_bulk_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php elseif ( $techjossecom_bulk_btn ) : ?>
<a href="<?php echo esc_url( $techjossecom_bulk_link ); ?>" class="button button--bulk-order">
<?php echo esc_html( $techjossecom_bulk_btn ); ?> &rarr;
</a>
<?php endif; ?>
<?php elseif ( $techjossecom_bulk_btn ) : ?>
<span class="button button--bulk-order"><?php echo esc_html( $techjossecom_bulk_btn ); ?></span>
<?php endif; ?>
</div>

<div class="tj-bulk-order__image">
<?php if ( $techjossecom_bulk_image ) : ?>
<?php echo wp_get_attachment_image( $techjossecom_bulk_image, 'large', false, array( 'loading' => 'lazy', 'class' => 'tj-bulk-order__image-img', 'alt' => $techjossecom_bulk_title ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endif; ?>
</div>
</div>
</div>
</section>
