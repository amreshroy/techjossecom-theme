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
<div id="tj-cod-modal" class="tj-modal tj-cod" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="tj-cod-title">
	<div class="tj-modal-backdrop" data-tj-close="tj-cod-modal"></div>

	<div class="tj-modal-panel" role="document">
		<button type="button" class="tj-icon-btn tj-modal-close" data-tj-close="tj-cod-modal" aria-label="<?php esc_attr_e( 'Close', 'techjossecom' ); ?>">&times;</button>

		<div class="tj-cod-head">
			<h2 id="tj-cod-title" class="tj-modal-title"><?php echo esc_html( techjossecom_mod( 'cod_modal_title' ) ); ?></h2>
			<p class="tj-cod-subtitle"><?php esc_html_e( 'আপনার তথ্য দিন এবং অপশন সিলেক্ট করুন', 'techjossecom' ); ?></p>
		</div>

		<form class="tj-cod-form" data-tj-cod-form novalidate>
			<input type="hidden" name="product_id" value="" data-tj-cod-product-id />

			<?php /* A variable product is chosen first: the price, the stock and the shipping all depend on which option the shopper picks, so the Size / Color rows sit above everything else. */ ?>
			<div class="tj-cod-row tj-cod-variation" data-tj-cod-variation-wrap hidden>
				<div class="tj-cod-var-groups" data-tj-cod-variation></div>
			</div>

			<p class="tj-cod-field">
				<label class="tj-cod-field-label" for="tj-cod-name-input"><?php esc_html_e( 'আপনার নাম', 'techjossecom' ); ?> <span class="tj-req">*</span></label>
				<span class="tj-cod-control">
					<span class="tj-cod-control-icon" aria-hidden="true"><?php echo techjossecom_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="text" id="tj-cod-name-input" name="name" required autocomplete="name" placeholder="<?php esc_attr_e( 'আপনার নাম', 'techjossecom' ); ?>" />
				</span>
			</p>

			<p class="tj-cod-field">
				<label class="tj-cod-field-label" for="tj-cod-phone-input"><?php esc_html_e( 'ফোন নাম্বার', 'techjossecom' ); ?> <span class="tj-req">*</span></label>
				<span class="tj-cod-control">
					<span class="tj-cod-control-icon" aria-hidden="true"><?php echo techjossecom_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<input type="tel" id="tj-cod-phone-input" name="phone" required inputmode="numeric" autocomplete="tel" placeholder="01XXXXXXXXX" />
				</span>
			</p>

			<p class="tj-cod-field">
				<label class="tj-cod-field-label" for="tj-cod-address-input"><?php esc_html_e( 'আপনার পুরো ঠিকানা', 'techjossecom' ); ?> <span class="tj-req">*</span></label>
				<span class="tj-cod-control tj-cod-control--area">
					<span class="tj-cod-control-icon" aria-hidden="true"><?php echo techjossecom_icon( 'map-pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<textarea id="tj-cod-address-input" name="address" rows="2" required autocomplete="street-address" placeholder="<?php esc_attr_e( 'আপনার পুরো ঠিকানা লিখুন', 'techjossecom' ); ?>"></textarea>
				</span>
			</p>

			<?php /* Delivery options come straight from the WooCommerce shipping zones, so this list is the same one the checkout page shows by default. */ ?>
			<div class="tj-cod-row tj-cod-shipping">
				<span class="tj-cod-label"><?php esc_html_e( 'শিপিং মেথড', 'techjossecom' ); ?></span>
				<div class="tj-cod-shipping-list" data-tj-cod-shipping>
					<p class="tj-cod-loading"><?php esc_html_e( 'Checking delivery options…', 'techjossecom' ); ?></p>
				</div>
				<div class="tj-cod-zone" data-tj-cod-zone-wrap hidden>
					<label class="tj-cod-field-label" for="tj-cod-zone-region"><?php esc_html_e( 'আপনার জোন রিজিয়ন সিলেক্ট করুন', 'techjossecom' ); ?></label>
					<span class="tj-cod-control"><select id="tj-cod-zone-region" name="zone_region" data-tj-cod-zone-region></select></span>
				</div>
			</div>
			<table class="tj-cod-order">
			<thead>
				<tr>
					<th class="tj-cod-order-head" scope="col"><?php esc_html_e( 'Product', 'techjossecom' ); ?></th>
					<th class="tj-cod-order-num" scope="col"><?php esc_html_e( 'Qty', 'techjossecom' ); ?></th>
					<th class="tj-cod-order-num" scope="col"><?php esc_html_e( 'Price', 'techjossecom' ); ?></th>
					<th class="tj-cod-order-num" scope="col"><?php esc_html_e( 'Subtotal', 'techjossecom' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="tj-cod-order-product">
						<span class="tj-cod-order-product-card">
						<span class="tj-cod-thumb">
							<img data-tj-cod-image alt="" width="48" height="48" class="tj-cod-image" hidden />
							<span class="tj-cod-thumb-fallback" data-tj-cod-thumb-fallback aria-hidden="true">&#128230;</span>
						</span>
						<span class="tj-cod-order-name" data-tj-cod-name></span>
						</span>
					</td>
					<td class="tj-cod-order-num">
						<span class="tj-qty">
							<button type="button" class="tj-qty-btn" data-tj-qty-step="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'techjossecom' ); ?>">&minus;</button>
							<input type="number" class="tj-qty-input" data-tj-cod-qty value="1" min="1" step="1" inputmode="numeric" aria-label="<?php esc_attr_e( 'Quantity', 'techjossecom' ); ?>" />
							<button type="button" class="tj-qty-btn" data-tj-qty-step="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'techjossecom' ); ?>">+</button>
						</span>
					</td>
					<td class="tj-cod-order-num" data-tj-cod-price>&mdash;</td>
					<td class="tj-cod-order-num" data-tj-cod-order-subtotal>&mdash;</td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="3" scope="row"><?php esc_html_e( 'সাবটোটাল (VAT বাদে)', 'techjossecom' ); ?></th>
					<td class="tj-cod-order-num" data-tj-cod-subtotal>&mdash;</td>
				</tr>
				<tr>
					<th colspan="3" scope="row"><?php esc_html_e( 'শিপিং চার্জ', 'techjossecom' ); ?></th>
					<td class="tj-cod-order-num" data-tj-cod-shipping-total>&mdash;</td>
				</tr>
				<tr class="tj-cod-order-total">
					<th colspan="4" scope="row"><?php esc_html_e( 'সর্বমোট', 'techjossecom' ); ?></th>
					<td class="tj-cod-order-num" data-tj-cod-total>&mdash;</td>
				</tr>
			</tfoot>
			</table>

			<input type="text" name="tj_hp" class="tj-hp" tabindex="-1" autocomplete="off" aria-hidden="true" />

			<button type="submit" class="tj-btn tj-btn--primary tj-btn--block" data-tj-cod-submit>
			<?php esc_html_e( '✓ অর্ডার কনফার্ম করুন', 'techjossecom' ); ?>
			</button>

			<p class="tj-cod-message" data-tj-cod-message role="status" aria-live="polite"></p>
			<p class="tj-cod-terms"><?php esc_html_e( 'শিপিং চার্জ আগে দিতে হবে।', 'techjossecom' ); ?></p>
			</form>
	</div>
</div>
