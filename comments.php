<?php
/**
 * Comments template.
 *
 * @package TechJosse_Commerce
 */

if ( post_password_required() ) {
	return;
}
?>
<div id="comments" class="tj-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="tj-comments-title">
			<?php
			printf(
				/* translators: %s: comment count. */
				esc_html( _n( '%s comment', '%s comments', get_comments_number(), 'techjossecom' ) ),
				esc_html( number_format_i18n( get_comments_number() ) )
			);
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'       => 'ol',
					'short_ping'  => true,
					'avatar_size' => 48,
				)
			);
			?>
		</ol>

		<?php
		the_comments_pagination(
			array(
				'prev_text' => __( '&larr; Older comments', 'techjossecom' ),
				'next_text' => __( 'Newer comments &rarr;', 'techjossecom' ),
				'class'     => 'tj-pagination',
			)
		);
		?>
	<?php endif; ?>

	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="tj-comments-closed"><?php esc_html_e( 'Comments are closed.', 'techjossecom' ); ?></p>
	<?php endif; ?>

	<?php
	comment_form(
		array(
			'class_form'         => 'tj-comment-form',
			'class_submit'       => 'tj-btn tj-btn--primary',
			'title_reply'        => __( 'Leave a comment', 'techjossecom' ),
			'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
			'title_reply_after'  => '</h3>',
		)
	);
	?>
</div>
