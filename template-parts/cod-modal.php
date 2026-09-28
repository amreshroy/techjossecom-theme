<?php
/**
 * Cash on delivery quick order popup.
 *
 * The product details are filled in by JavaScript when an "Order Now" button
 * is clicked; the shipping options and the order itself are handled by AJAX.
 *
 * @package TechJosse_Commerce
 */

if ( ! techjossecom_mod( 'enable_cod' ) || ! class_exists( 'WooCommerce' ) ) {
	return;
}
?>
<div id="tj-cod-modal" class="tj-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="tj-cod-title">
	<div class="tj-modal-backdrop" data-tj-close="tj-cod-modal"></div>

	<div class="tj-modal-panel" role="document">
		<button type="button" class="tj-icon-btn tj-modal-close" data-tj-close="tj-cod-modal" aria-label="<?php esc_attr_e( 'Close', 'techjossecom' ); ?>">&times;</button>

		<h2 id="tj-cod-title" class="tj-modal-title"><?php echo esc_html( techjossecom_mod( 'cod_modal_title' ) ); ?></h2>

		<div class="tj-cod-product">
			<span class="tj-cod-thumb">
				<img data-tj-cod-image alt="" width="72" height="72" class="tj-cod-image" hidden />
				<span class="tj-cod-thumb-fallback" aria-hidden="true">&#128230;</span>
			</span>
			<span class="tj-cod-product-text">
				<span class="tj-cod-name" data-tj-cod-name></span>
				<span class="tj-cod-price" data-tj-cod-price></span>
			</span>
		</div>

		<div class="tj-cod-row tj-cod-variation" data-tj-cod-variation-wrap hidden>
			<label class="tj-cod-label" for="tj-cod-variation"><?php esc_html_e( 'Choose an option', 'techjossecom' ); ?></label>
			<select id="tj-cod-variation" class="tj-cod-select" data-tj-cod-variation></select>
		</div>

		<div class="tj-cod-row tj-cod-qty">
			<span class="tj-cod-label"><?php esc_html_e( 'Quantity', 'techjossecom' ); ?></span>
			<div class="tj-qty">
				<button type="button" class="tj-qty-btn" data-tj-qty-step="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'techjossecom' ); ?>">&minus;</button>
				<input type="number" class="tj-qty-input" data-tj-cod-qty value="1" min="1" step="1" inputmode="numeric" aria-label="<?php esc_attr_e( 'Quantity', 'techjossecom' ); ?>" />
				<button type="button" class="tj-qty-btn" data-tj-qty-step="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'techjossecom' ); ?>">+</button>
			</div>
		</div>

		<div class="tj-cod-row tj-cod-shipping">
			<span class="tj-cod-label"><?php esc_html_e( 'Delivery option', 'techjossecom' ); ?></span>
			<div class="tj-cod-shipping-list" data-tj-cod-shipping>
				<p class="tj-cod-loading"><?php esc_html_e( 'Checking delivery options…', 'techjossecom' ); ?></p>
			</div>
		</div>

		<form class="tj-cod-form" data-tj-cod-form novalidate>
			<input type="hidden" name="product_id" value="" data-tj-cod-product-id />

			<div class="tj-cod-grid">
				<p class="tj-field">
					<label for="tj-cod-name-input"><?php esc_html_e( 'Your name', 'techjossecom' ); ?> <span class="tj-req">*</span></label>
					<input type="text" id="tj-cod-name-input" name="name" required autocomplete="name" />
				</p>

				<p class="tj-field">
					<label for="tj-cod-phone-input"><?php esc_html_e( 'Mobile number', 'techjossecom' ); ?> <span class="tj-req">*</span></label>
					<input type="tel" id="tj-cod-phone-input" name="phone" required inputmode="numeric" autocomplete="tel" placeholder="01XXXXXXXXX" />
				</p>
			</div>

			<p class="tj-field">
				<label for="tj-cod-address-input"><?php esc_html_e( 'Full address', 'techjossecom' ); ?> <span class="tj-req">*</span></label>
				<textarea id="tj-cod-address-input" name="address" rows="2" required autocomplete="street-address"></textarea>
			</p>

			<div class="tj-cod-grid">
				<p class="tj-field">
					<label for="tj-cod-city-input"><?php esc_html_e( 'City / District', 'techjossecom' ); ?></label>
					<input type="text" id="tj-cod-city-input" name="city" autocomplete="address-level2" />
				</p>

				<p class="tj-field">
					<label for="tj-cod-email-input"><?php esc_html_e( 'Email (optional)', 'techjossecom' ); ?></label>
					<input type="email" id="tj-cod-email-input" name="email" autocomplete="email" />
				</p>
			</div>

			<p class="tj-field">
				<label for="tj-cod-note-input"><?php esc_html_e( 'Order note (optional)', 'techjossecom' ); ?></label>
				<textarea id="tj-cod-note-input" name="note" rows="2"></textarea>
			</p>

			<input type="text" name="tj_hp" class="tj-hp" tabindex="-1" autocomplete="off" aria-hidden="true" />

			<div class="tj-cod-summary">
				<span class="tj-cod-summary-label"><?php esc_html_e( 'Total payable', 'techjossecom' ); ?></span>
				<strong class="tj-cod-summary-value" data-tj-cod-total>&mdash;</strong>
			</div>

			<button type="submit" class="tj-btn tj-btn--primary tj-btn--block" data-tj-cod-submit>
				<?php esc_html_e( 'Confirm order (Cash on delivery)', 'techjossecom' ); ?>
			</button>

			<p class="tj-cod-message" data-tj-cod-message role="status" aria-live="polite"></p>
			<p class="tj-cod-terms"><?php esc_html_e( 'No advance payment needed. Pay the courier when the product arrives.', 'techjossecom' ); ?></p>
		</form>
	</div>
</div>
