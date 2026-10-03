<?php
/**
 * The collapsible description block.
 *
 * One component written once and used in four places: the homepage, the blog
 * archives, the shop page and the product category / tag archives. Every one
 * of them fills it from a different source - a theme setting, a category
 * description, the content of the shop page - and every one of them looks the
 * same, because the markup, the stylesheet and the script are shared rather
 * than copied per page.
 *
 * The text is written out in full and only its height is capped by the
 * stylesheet, so a search engine reads the whole block. The "Read More" button
 * is hidden by that stylesheet until the script has measured the text and found
 * it too long, which is also what keeps it off the page in a browser with no
 * JavaScript - where the whole block is simply visible.
 *
 * @var array $args {
 *     Heading and body text. "text" keeps its basic HTML, so an author can use
 *     sub-headings and bold keywords; a plain paragraph typed in the editor is
 *     still turned into markup here.
 *
 *     @type string $title Heading line. Leave empty for no heading.
 *     @type string $text  Body text.
 *     @type string $id    id of the region the button controls. Empty asks for a
 *                         fresh one, so two blocks on one page cannot collide.
 *     @type string $label Button label. Empty falls back to "Read More".
 * }
 *
 * @package TechJosse_Commerce
 */

$techjossecom_block = wp_parse_args(
	isset( $args ) && is_array( $args ) ? $args : array(),
	array(
		'title' => '',
		'text'  => '',
		'id'    => '',
		'label' => '',
	)
);

$techjossecom_title = trim( wp_strip_all_tags( (string) $techjossecom_block['title'] ) );
$techjossecom_text  = trim( wp_kses_post( (string) $techjossecom_block['text'] ) );

/**
 * Filter the body text before it is printed, for one block.
 *
 * @param string $techjossecom_text Body text with its HTML kept.
 * @param array  $techjossecom_block The arguments the block was given.
 */
$techjossecom_text = apply_filters( 'techjossecom_description_block_text', $techjossecom_text, $techjossecom_block );

/*
 * The text arrives already marked up from a category description or from the
 * shop page, so running wpautop() over it a second time would be wrong. Text
 * that carries no block level tags at all - a description typed as one plain
 * paragraph in the editor - still needs its line breaks turned into markup.
 */
if ( $techjossecom_text && ! preg_match( '#</?(p|div|h[1-6]|ul|ol|blockquote|figure|table|section)\b#i', $techjossecom_text ) ) {
	$techjossecom_text = wpautop( $techjossecom_text );
}

$techjossecom_body_id = $techjossecom_block['id'] ? $techjossecom_block['id'] : wp_unique_id( 'tj-seo-body-' );

if ( ! $techjossecom_block['label'] ) {
	$techjossecom_block['label'] = __( 'Read More', 'techjossecom' );
}
?>
<div class="tj-seo" data-tj-seo>
	<?php if ( $techjossecom_title ) : ?>
		<h2 class="tj-seo-title"><?php echo esc_html( $techjossecom_title ); ?></h2>
	<?php endif; ?>

	<?php
	/*
	 * The button sits inside this check because the text it opens is what it
	 * controls: with no text there is nothing to open, so neither the button
	 * nor the id it points at is printed.
	 */
	if ( $techjossecom_text ) :
		?>
		<div class="tj-seo-body" id="<?php echo esc_attr( $techjossecom_body_id ); ?>" data-tj-seo-body>
			<?php echo $techjossecom_text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- run through wp_kses_post() above ?>
		</div>

		<button type="button" class="tj-seo-toggle" data-tj-seo-toggle aria-expanded="false" aria-controls="<?php echo esc_attr( $techjossecom_body_id ); ?>"
			data-tj-more="<?php echo esc_attr( $techjossecom_block['label'] ); ?>"
			data-tj-less="<?php esc_attr_e( 'Read Less', 'techjossecom' ); ?>">
			<span class="tj-seo-toggle-label" data-tj-seo-label><?php echo esc_html( $techjossecom_block['label'] ); ?></span>
			<span class="tj-seo-toggle-icon" data-tj-seo-icon aria-hidden="true"><?php echo techjossecom_icon( 'chevron-down' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</button>
	<?php endif; ?>
</div>