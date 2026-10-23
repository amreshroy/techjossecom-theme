<?php
/**
 * Client Logos section template.
 *
 * Centered heading and subtitle above a responsive grid of client logos.
 * Logos are admin-selectable images with optional links.
 *
 * @package TechJosse_Commerce
 */

$techjossecom_clients_title   = techjossecom_mod( 'clients_title' );
$techjossecom_clients_sub     = techjossecom_mod( 'clients_subtitle' );
$techjossecom_clients_view_text = techjossecom_mod( 'clients_view_all_text' );
$techjossecom_clients_view_url  = techjossecom_mod( 'clients_view_all_url' );

$techjossecom_clients = array();
for ( $techjossecom_i = 1; $techjossecom_i <= 6; $techjossecom_i++ ) {
$techjossecom_image_id = (int) techjossecom_mod( 'client' . $techjossecom_i . '_image' );
$techjossecom_url      = techjossecom_mod( 'client' . $techjossecom_i . '_url' );

if ( $techjossecom_image_id > 0 ) {
$techjossecom_clients[] = array(
'image_id' => $techjossecom_image_id,
'url'      => $techjossecom_url,
);
}
}

if ( ! $techjossecom_clients_title && empty( $techjossecom_clients ) ) {
return;
}
?>
<section class="tj-section tj-section--clients">
<div class="tj-container">
<?php if ( $techjossecom_clients_title || $techjossecom_clients_sub ) : ?>
<div class="tj-section-head tj-section-head--center">
<?php if ( $techjossecom_clients_title ) : ?>
<h2 class="tj-section-title tj-section-title--center"><?php echo esc_html( $techjossecom_clients_title ); ?></h2>
<?php endif; ?>
<?php if ( $techjossecom_clients_sub ) : ?>
<p class="tj-section-subtitle"><?php echo wp_kses_post( wpautop( $techjossecom_clients_sub ) ); ?></p>
<?php endif; ?>
</div>
<?php endif; ?>

<?php if ( ! empty( $techjossecom_clients ) ) : ?>
<div class="tj-carousel">
<div class="tj-scroller" data-tj-scroller>
<ul class="tj-client-grid">
<?php foreach ( $techjossecom_clients as $techjossecom_client ) : ?>
<li class="tj-client-item">
<?php if ( $techjossecom_client['url'] ) : ?>
<a href="<?php echo esc_url( $techjossecom_client['url'] ); ?>" class="tj-client-link" aria-label="<?php echo esc_attr( $techjossecom_clients_title ); ?>">
<?php endif; ?>
<?php echo wp_get_attachment_image( $techjossecom_client['image_id'], 'medium', false, array( 'loading' => 'lazy', 'class' => 'tj-client-logo', 'alt' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php if ( $techjossecom_client['url'] ) : ?>
</a>
<?php endif; ?>
</li>
<?php endforeach; ?>
</ul>
</div>
<?php techjossecom_scroll_buttons(); ?>
</div>
<?php endif; ?>

<?php if ( $techjossecom_clients_view_url ) : ?>
<div class="tj-clients-view-all">
<a href="<?php echo esc_url( $techjossecom_clients_view_url ); ?>" class="button button--view-all-clients">
<?php echo esc_html( $techjossecom_clients_view_text ? $techjossecom_clients_view_text : __( 'View All Clients', 'techjossecom' ) ); ?>
</a>
</div>
<?php endif; ?>
</div>
</section>
