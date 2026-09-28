<?php
/**
 * Site header.
 *
 * @package TechJosse_Commerce
 */

$techjossecom_hotline = techjossecom_mod( 'hotline_number' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="tj-skip-link" href="#tj-main"><?php esc_html_e( 'Skip to content', 'techjossecom' ); ?></a>

<div id="page" class="tj-site">

	<?php get_template_part( 'template-parts/topbar' ); ?>

	<header id="masthead" class="tj-header" role="banner">
		<div class="tj-container tj-header-inner">

			<button type="button" class="tj-icon-btn tj-menu-toggle" data-tj-toggle="tj-mobile-menu" aria-expanded="false" aria-controls="tj-mobile-menu">
				<span class="tj-burger" aria-hidden="true"></span>
				<span class="tj-screen-reader-text"><?php esc_html_e( 'Menu', 'techjossecom' ); ?></span>
			</button>

			<?php techjossecom_site_branding(); ?>

			<?php get_template_part( 'template-parts/live-search' ); ?>

			<div class="tj-header-actions">
				<?php if ( $techjossecom_hotline ) : ?>
					<a class="tj-hotline" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $techjossecom_hotline ) ); ?>">
						<span class="tj-hotline-icon" aria-hidden="true"><?php echo techjossecom_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="tj-hotline-text">
							<span class="tj-hotline-label"><?php echo esc_html( techjossecom_mod( 'hotline_label' ) ); ?></span>
							<span class="tj-hotline-number"><?php echo esc_html( $techjossecom_hotline ); ?></span>
						</span>
					</a>
				<?php endif; ?>

				<?php if ( techjossecom_has_woocommerce() ) : ?>
					<a class="tj-header-link tj-account-link" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
						<span class="tj-header-link-icon" aria-hidden="true"><?php echo techjossecom_icon( 'user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="tj-header-link-label"><?php esc_html_e( 'Account', 'techjossecom' ); ?></span>
					</a>

					<a class="tj-header-link tj-cart-button" href="<?php echo esc_url( wc_get_cart_url() ); ?>" data-tj-open-cart aria-label="<?php esc_attr_e( 'Open the cart', 'techjossecom' ); ?>">
						<span class="tj-cart-icon" aria-hidden="true">
							<?php
							echo techjossecom_icon( 'cart' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo techjossecom_mini_cart_part( 'count' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</span>
						<?php echo techjossecom_mini_cart_part( 'total' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</header>

	<nav class="tj-nav-bar" aria-label="<?php esc_attr_e( 'Primary', 'techjossecom' ); ?>">
		<div class="tj-container tj-nav-inner">
			<?php get_template_part( 'template-parts/mega-menu' ); ?>

			<div class="tj-primary-nav">
				<?php
				techjossecom_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_class'     => 'tj-menu',
						'depth'          => 2,
					)
				);
				?>
			</div>

			<?php if ( techjossecom_mod( 'show_deals' ) ) : ?>
				<a class="tj-nav-deal" href="<?php echo esc_url( techjossecom_shop_url() ); ?>">
					<span aria-hidden="true"><?php echo techjossecom_icon( 'tag' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php esc_html_e( 'Shop Deals', 'techjossecom' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</nav>

	<div id="tj-mobile-menu" class="tj-mobile-menu" hidden>
		<div class="tj-mobile-menu-head">
			<strong><?php esc_html_e( 'Menu', 'techjossecom' ); ?></strong>
			<button type="button" class="tj-icon-btn" data-tj-close="tj-mobile-menu" aria-label="<?php esc_attr_e( 'Close', 'techjossecom' ); ?>">&times;</button>
		</div>
		<div class="tj-mobile-menu-body">
			<?php get_template_part( 'template-parts/live-search', null, array( 'context' => 'mobile' ) ); ?>
			<?php
			/*
			 * The mobile drawer intentionally does not repeat the desktop
			 * primary menu. On a small screen it would only repeat the shop
			 * pages, push the category list far down the drawer and add a
			 * second way to reach the same links. The category list below
			 * already covers navigation on mobile.
			 */
			get_template_part( 'template-parts/mega-menu', null, array( 'context' => 'mobile' ) );
			?>
		</div>
	</div>

	<?php get_template_part( 'template-parts/mobile-search' ); ?>
