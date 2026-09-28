<?php
/**
 * TechJosse Commerce - theme bootstrap.
 *
 * A lightweight, SEO friendly WooCommerce theme with AJAX live search,
 * a slide-in mini cart and a one-click Cash on Delivery order popup.
 *
 * @package TechJosse_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'TECHJOSSECOM_VERSION', '1.0.0' );
define( 'TECHJOSSECOM_DIR', get_template_directory() );
define( 'TECHJOSSECOM_URI', get_template_directory_uri() );

/**
 * Load theme modules.
 */
require_once TECHJOSSECOM_DIR . '/inc/customizer.php';
require_once TECHJOSSECOM_DIR . '/inc/performance-seo.php';
require_once TECHJOSSECOM_DIR . '/inc/woocommerce-setup.php';
require_once TECHJOSSECOM_DIR . '/inc/ajax-handlers.php';
require_once TECHJOSSECOM_DIR . '/inc/master-import.php';

/**
 * Default values for every customizer setting.
 *
 * Keeping the defaults in one place means the customizer, the templates and
 * the master JSON importer all agree on the same values.
 *
 * @return array
 */
function techjossecom_defaults() {
	$defaults = array(
		'topbar_text'         => 'Best Online Shop in Bangladesh — Genuine Products, Nationwide Delivery',
		'show_topbar'         => true,
		'hotline_label'       => 'Hotline',
		'hotline_number'      => '+8801700000000',
		'search_placeholder'  => 'Search for products...',
		'show_live_search'    => true,
		'hero1_image'         => '',
		'hero1_title'         => 'Big Savings On Everyday Products',
		'hero1_subtitle'      => 'Genuine items, fast delivery, honest prices',
		'hero1_button_text'   => 'Shop Now',
		'hero1_button_url'    => '',
		'hero2_image'         => '',
		'hero2_badge'         => 'New Arrival',
		'hero2_title'         => 'Home Essentials',
		'hero2_url'           => '',
		'hero3_image'         => '',
		'hero3_badge'         => 'Best Price',
		'hero3_title'         => 'Top Rated Items',
		'hero3_url'           => '',
		'show_front_heading'  => true,
		'front_heading'       => 'Shop Online in Bangladesh — Genuine Products, Fast Delivery',
		'show_intro'          => true,
		'intro_title'         => 'Your Trusted Online Shop in Bangladesh',
		'intro_text'          => '',
		'show_categories'     => true,
		'categories_title'    => 'Shop by Category',
		'categories_limit'    => 12,
		'show_deals'          => true,
		'deals_title'         => 'Best Deals',
		'deals_limit'         => 8,
		'show_features'       => true,
		'feature1_title'      => '100% Genuine Products',
		'feature1_text'       => 'Verified quality items',
		'feature2_title'      => 'Nationwide Delivery',
		'feature2_text'       => 'Fast delivery across Bangladesh',
		'feature3_title'      => '24/7 Support',
		'feature3_text'       => 'Call us any time',
		'feature4_title'      => 'Best Price Guarantee',
		'feature4_text'       => 'Affordable & transparent',
		'enable_cod'          => true,
		'cod_button_text'     => 'Order Now',
		'cod_modal_title'     => 'Order Now — Cash on Delivery',
		'cod_success_message' => 'Thank you! Your order has been received.',
		'contact_address'     => 'Dhaka, Bangladesh',
		'contact_email'       => '',
		'whatsapp_number'     => '',
		'messenger_username'  => '',
		'social_facebook'     => '',
		'social_youtube'      => '',
		'social_x'            => '',
		'social_linkedin'     => '',
		'social_instagram'    => '',
		'social_pinterest'    => '',
		'footer_about'        => 'We are a trusted online shop delivering genuine products all over Bangladesh with cash on delivery.',
		'footer_copyright'    => '',
		'footer_payments'     => 'We accept: bKash • Nagad • Cash on Delivery • Cards',
		'primary_color'       => '#0a7d4f',
		'accent_color'        => '#ff6b00',
		'dark_color'          => '#12251c',
		'secondary_color'     => '#1c4fd8',
		'show_single_share'   => true,
	);

	/**
	 * Filter the theme default settings.
	 *
	 * @param array $defaults Theme defaults keyed by setting name.
	 */
	return apply_filters( 'techjossecom_defaults', $defaults );
}

/**
 * Read a theme setting with a sensible default.
 *
 * @param string $key      Setting name without the theme prefix.
 * @param mixed  $fallback Optional value used when the key has no default.
 * @return mixed
 */
function techjossecom_mod( $key, $fallback = '' ) {
	$defaults = techjossecom_defaults();
	$default  = isset( $defaults[ $key ] ) ? $defaults[ $key ] : $fallback;

	return get_theme_mod( 'techjossecom_' . $key, $default );
}

/**
 * Refresh stored customizer values that still hold a renamed theme default.
 *
 * Renaming a default in techjossecom_defaults() only helps installs that never
 * saved the setting: get_theme_mod() returns the stored copy once the
 * customizer has been published, so the old label would keep rendering. Each
 * entry rewrites the stored theme mod once, and only when it still matches the
 * previous default, so a label the site owner typed themselves is preserved.
 *
 * @return void
 */
function techjossecom_upgrade_mods() {
	if ( '1.1.0' === get_option( 'techjossecom_mods_version' ) ) {
		return;
	}

	$renamed = array(
		'cod_button_text' => array(
			'from' => 'Order Now (Cash on Delivery)',
			'to'   => 'Order Now',
		),
	);

	foreach ( $renamed as $key => $change ) {
		$name  = 'techjossecom_' . $key;
		$saved = get_theme_mod( $name, null );

		if ( null !== $saved && $saved === $change['from'] ) {
			set_theme_mod( $name, $change['to'] );
		}
	}

	update_option( 'techjossecom_mods_version', '1.1.0' );
}
add_action( 'after_setup_theme', 'techjossecom_upgrade_mods' );
/**
 * Theme setup.
 *
 * @return void
 */
function techjossecom_setup() {
	load_theme_textdomain( 'techjossecom', TECHJOSSECOM_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 60,
			'width'       => 220,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 400,
			'single_image_width'    => 700,
			'product_grid'          => array(
				'default_rows'    => 4,
				'min_rows'        => 1,
				'default_columns' => 4,
				'min_columns'     => 2,
				'max_columns'     => 5,
			),
		)
	);
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	register_nav_menus(
		array(
			'primary'  => __( 'Primary Menu (header)', 'techjossecom' ),
			'topbar'   => __( 'Top Bar Menu', 'techjossecom' ),
			'footer_1' => __( 'Footer Column 1 — Top Categories', 'techjossecom' ),
			'footer_2' => __( 'Footer Column 2 — Our Services', 'techjossecom' ),
			'footer_3' => __( 'Footer Column 3 — Information', 'techjossecom' ),
			'mobile'   => __( 'Mobile Slide Menu', 'techjossecom' ),
		)
	);

	add_image_size( 'techjossecom-card', 400, 400, true );
	add_image_size( 'techjossecom-category', 120, 120, true );

	$GLOBALS['content_width'] = 1240;
}
add_action( 'after_setup_theme', 'techjossecom_setup' );

/**
 * Register widget areas.
 *
 * @return void
 */
function techjossecom_widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Footer About Column', 'techjossecom' ),
			'id'            => 'footer-about',
			'description'   => __( 'Appears in the first footer column. Leave empty to use the customizer text.', 'techjossecom' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);

	for ( $i = 1; $i <= 3; $i++ ) {
		register_sidebar(
			array(
				/* translators: %d: footer column number. */
				'name'          => sprintf( __( 'Footer Column %d', 'techjossecom' ), $i + 1 ),
				'id'            => 'footer-' . ( $i + 1 ),
				'description'   => __( 'Footer link column. Falls back to the assigned nav menu.', 'techjossecom' ),
				'before_widget' => '<div id="%1$s" class="widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h3 class="widget-title">',
				'after_title'   => '</h3>',
			)
		);
	}

	register_sidebar(
		array(
			'name'          => __( 'Blog Sidebar', 'techjossecom' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Shown next to blog posts and archives.', 'techjossecom' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Shop Sidebar', 'techjossecom' ),
			'id'            => 'shop-sidebar',
			'description'   => __( 'Shown on shop and product category pages when widgets are added.', 'techjossecom' ),
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'techjossecom_widgets_init' );
/**
 * Load the stylesheets and scripts.
 *
 * @return void
 */
function techjossecom_assets() {
	// Base stylesheet (also holds the theme header).
	wp_enqueue_style( 'techjossecom-base', get_stylesheet_uri(), array(), TECHJOSSECOM_VERSION );

	// Main stylesheet.
	wp_enqueue_style(
		'techjossecom-main',
		TECHJOSSECOM_URI . '/assets/css/main.css',
		array( 'techjossecom-base' ),
		techjossecom_asset_version( 'assets/css/main.css' )
	);

	// Main script, loaded in the footer with defer so it never blocks paint.
	wp_enqueue_script(
		'techjossecom-main',
		TECHJOSSECOM_URI . '/assets/js/main.js',
		array(),
		techjossecom_asset_version( 'assets/js/main.js' ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	$wc_ajax_url = ( class_exists( 'WooCommerce' ) && class_exists( 'WC_AJAX' ) )
		? WC_AJAX::get_endpoint( '%%endpoint%%' )
		: '';

	wp_localize_script(
		'techjossecom-main',
		'techjossecomData',
		array(
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'wcAjaxUrl'      => $wc_ajax_url,
			'searchNonce'    => wp_create_nonce( 'techjossecom_search' ),
			'codNonce'       => wp_create_nonce( 'techjossecom_cod' ),
			'cartUrl'        => class_exists( 'WooCommerce' ) ? wc_get_cart_url() : '',
			'checkoutUrl'    => class_exists( 'WooCommerce' ) ? wc_get_checkout_url() : '',
			'isWooCommerce'  => class_exists( 'WooCommerce' ),
			'liveSearch'     => (bool) techjossecom_mod( 'show_live_search' ),
			'codEnabled'     => (bool) techjossecom_mod( 'enable_cod' ),
			'currencySymbol' => class_exists( 'WooCommerce' ) ? get_woocommerce_currency_symbol() : '',
			'priceFormat'    => array(
				'symbol'            => class_exists( 'WooCommerce' ) ? get_woocommerce_currency_symbol() : '',
				'decimals'          => class_exists( 'WooCommerce' ) ? wc_get_price_decimals() : 2,
				'decimalSeparator'  => class_exists( 'WooCommerce' ) ? wc_get_price_decimal_separator() : '.',
				'thousandSeparator' => class_exists( 'WooCommerce' ) ? wc_get_price_thousand_separator() : ',',
				'position'          => class_exists( 'WooCommerce' ) ? get_option( 'woocommerce_currency_pos', 'left' ) : 'left',
			),
			'i18n'           => array(
				'searching'    => __( 'Searching...', 'techjossecom' ),
				'noResults'    => __( 'No products found.', 'techjossecom' ),
				'viewAll'      => __( 'View all results', 'techjossecom' ),
				'adding'       => __( 'Adding...', 'techjossecom' ),
				'orderPlacing' => __( 'Placing your order...', 'techjossecom' ),
				'error'        => __( 'Something went wrong. Please try again.', 'techjossecom' ),
				'required'     => __( 'Please fill in your name, mobile number and address.', 'techjossecom' ),
				'close'        => __( 'Close', 'techjossecom' ),
				'free'         => __( 'Free', 'techjossecom' ),
				'outOfStock'   => __( 'Out of stock', 'techjossecom' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'techjossecom_assets' );

/**
 * Cache-busting version for an asset, based on its file modification time.
 *
 * @param string $relative_path Path relative to the theme root.
 * @return string
 */
function techjossecom_asset_version( $relative_path ) {
	$file = TECHJOSSECOM_DIR . '/' . ltrim( $relative_path, '/' );

	if ( file_exists( $file ) ) {
		return TECHJOSSECOM_VERSION . '.' . filemtime( $file );
	}

	return TECHJOSSECOM_VERSION;
}

/**
 * Body classes that describe the layout to the stylesheet.
 *
 * @param array $classes Existing classes.
 * @return array
 */
function techjossecom_body_classes( $classes ) {
	$classes[] = 'tj-theme';

	if ( class_exists( 'WooCommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
		$classes[] = 'tj-woo-page';
	}

	if ( is_active_sidebar( 'shop-sidebar' ) ) {
		$classes[] = 'tj-has-shop-sidebar';
	}

	return $classes;
}
add_filter( 'body_class', 'techjossecom_body_classes' );

/**
 * Check whether a nav menu is assigned to a location.
 *
 * @param string $location Menu location slug.
 * @return bool
 */
function techjossecom_has_menu( $location ) {
	$locations = get_nav_menu_locations();

	return ! empty( $locations[ $location ] );
}

/**
 * Print a nav menu with a graceful fallback so the header never looks broken.
 *
 * @param array $args wp_nav_menu() arguments.
 * @return void
 */
function techjossecom_nav_menu( $args ) {
	$args = wp_parse_args(
		$args,
		array(
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'tj-menu',
			'depth'          => 2,
			'fallback_cb'    => false,
		)
	);

	if ( techjossecom_has_menu( $args['theme_location'] ) ) {
		wp_nav_menu( $args );
		return;
	}

	// Fallback: the shop page plus a handful of pages.
	$items   = array();
	$page_id = (int) get_queried_object_id();

	if ( class_exists( 'WooCommerce' ) ) {
		$shop_id = wc_get_page_id( 'shop' );
		if ( $shop_id > 0 ) {
			$items[] = get_post( $shop_id );
		}
	}

	$pages = get_posts(
		array(
			'post_type'   => 'page',
			'numberposts' => 5,
			'orderby'     => 'menu_order',
			'order'       => 'ASC',
		)
	);

	$items = array_merge( $items, $pages );

	echo '<ul class="' . esc_attr( $args['menu_class'] ) . '">';
	foreach ( $items as $item ) {
		if ( ! $item ) {
			continue;
		}
		printf(
			'<li class="menu-item%1$s"><a href="%2$s">%3$s</a></li>',
			( (int) $item->ID === $page_id ) ? ' current-menu-item' : '',
			esc_url( get_permalink( $item ) ),
			esc_html( get_the_title( $item ) )
		);
	}
	echo '</ul>';
}
/**
 * Blog card excerpt length.
 *
 * @return int
 */
function techjossecom_excerpt_length() {
	return 24;
}
add_filter( 'excerpt_length', 'techjossecom_excerpt_length', 999 );

/**
 * Blog card excerpt ending.
 *
 * @return string
 */
function techjossecom_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'techjossecom_excerpt_more' );

/**
 * Nicer pagination markup for blog, archive and search results.
 *
 * @return void
 */
function techjossecom_pagination() {
	the_posts_pagination(
		array(
			'mid_size'  => 1,
			'prev_text' => __( '&larr;', 'techjossecom' ),
			'next_text' => __( '&rarr;', 'techjossecom' ),
			'class'     => 'tj-pagination',
		)
	);
}

/**
 * Print the site logo (custom logo if set, otherwise a text logo).
 *
 * @return void
 */
function techjossecom_site_branding() {
	echo '<div class="tj-branding">';

	if ( has_custom_logo() ) {
		the_custom_logo();
	} else {
		$name = get_bloginfo( 'name' );
		printf(
			'<a class="tj-text-logo" href="%1$s" rel="home"><span class="tj-text-logo-mark" aria-hidden="true">%2$s</span><span class="tj-text-logo-name">%3$s</span></a>',
			esc_url( home_url( '/' ) ),
			esc_html( strtoupper( mb_substr( $name, 0, 1 ) ) ),
			esc_html( $name )
		);

		$description = get_bloginfo( 'description', 'display' );
		if ( $description ) {
			printf( '<span class="tj-tagline">%s</span>', esc_html( $description ) );
		}
	}

	echo '</div>';
}

/**
 * WhatsApp and Messenger quick contact buttons.
 *
 * @param string $context Either 'single' or 'footer'.
 * @return void
 */
function techjossecom_contact_buttons( $context = 'single' ) {
	$phone     = techjossecom_mod( 'whatsapp_number' );
	$messenger = techjossecom_mod( 'messenger_username' );

	if ( ! $phone && ! $messenger ) {
		return;
	}

	echo '<div class="tj-contact-buttons tj-contact-buttons--' . esc_attr( $context ) . '">';

	if ( $phone ) {
		$digits = preg_replace( '/[^0-9]/', '', $phone );
		printf(
			'<a class="tj-btn tj-btn--whatsapp" href="%1$s" target="_blank" rel="noopener nofollow">%2$s %3$s</a>',
			esc_url( 'https://wa.me/' . $digits ),
			techjossecom_icon( 'whatsapp' ),
			esc_html__( 'WhatsApp', 'techjossecom' )
		);
	}

	if ( $messenger ) {
		printf(
			'<a class="tj-btn tj-btn--messenger" href="%1$s" target="_blank" rel="noopener nofollow">%2$s %3$s</a>',
			esc_url( 'https://m.me/' . rawurlencode( ltrim( $messenger, '@' ) ) ),
			techjossecom_icon( 'messenger' ),
			esc_html__( 'Messenger', 'techjossecom' )
		);
	}

	echo '</div>';
}

/**
 * Whether a known SEO plugin already prints meta / social tags, so the theme
 * avoids duplicate output.
 *
 * @return bool
 */
function techjossecom_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' )
		|| defined( 'RANK_MATH_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| defined( 'AIOSEO_VERSION' );
}

/**
 * Social profile links defined in the customizer.
 *
 * @return array Keyed by network slug.
 */
function techjossecom_social_links() {
	$networks = array(
		'facebook'  => array( '' === techjossecom_mod( 'social_facebook' ) ? home_url( '/' ) : techjossecom_mod( 'social_facebook' ), 'Facebook', 'f' ),
		'youtube'   => array( techjossecom_mod( 'social_youtube' ), 'YouTube', '▶' ),
		'x'         => array( techjossecom_mod( 'social_x' ), 'X (Twitter)', 'X' ),
		'linkedin'  => array( techjossecom_mod( 'social_linkedin' ), 'LinkedIn', 'in' ),
		'instagram' => array( techjossecom_mod( 'social_instagram' ), 'Instagram', '◎' ),
		'pinterest' => array( techjossecom_mod( 'social_pinterest' ), 'Pinterest', 'P' ),
	);

	$links = array();

	foreach ( $networks as $slug => $data ) {
		if ( empty( $data[0] ) ) {
			continue;
		}

		$links[ $slug ] = array(
			'url'   => $data[0],
			'label' => $data[1],
			'icon'  => $data[2],
		);
	}

	return $links;
}

/**
 * Inline SVG icon set used by the header, the navigation and the buttons.
 *
 * Inline SVG keeps the interface crisp on every platform (emoji are rendered as
 * colourful pictures on Windows) and inherits the colour of its parent.
 *
 * @param string $name  Icon slug.
 * @param string $class Extra CSS class for the SVG element.
 * @return string SVG markup, or an empty string for an unknown slug.
 */
function techjossecom_icon( $name, $class = '' ) {
	$icons = array(
		'phone'         => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/>',
		'user'          => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
		'cart'          => '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h2.2l2.5 12.1a1.6 1.6 0 0 0 1.6 1.3h9.1a1.6 1.6 0 0 0 1.6-1.3L21 7H5.4"/>',
		'search'        => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/>',
		'grid'          => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.4"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.4"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.4"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.4"/>',
		'chevron-down'  => '<path d="m6 9 6 6 6-6"/>',
		'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
		'tag'           => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8Z"/><path d="M7 7h.01"/>',
		'card'          => '<rect x="2.5" y="5" width="19" height="14" rx="2.4"/><path d="M2.5 10h19"/>',
		'store'         => '<path d="M3 9.5 5.5 3h13L21 9.5"/><path d="M4 9.5h16V20a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1Z"/><path d="M9 21v-6h6v6"/>',
		'truck'         => '<path d="M1.5 4.5h12v11h-12z"/><path d="M13.5 8.5h3.8l3.2 3.2v3.8h-7"/><circle cx="6" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>',
		'mail'          => '<rect x="2.5" y="4.5" width="19" height="15" rx="2"/><path d="m3 6.5 9 6 9-6"/>',
		'home'          => '<path d="m3 10.5 9-7.5 9 7.5"/><path d="M5.5 9.8V20h13V9.8"/><path d="M10 20v-5.5h4V20"/>',
		'chat'          => '<path d="M20.5 11.5c0 4.1-3.8 7.4-8.5 7.4-1 0-2-.2-2.9-.5L4.5 20l1.1-3.6A7 7 0 0 1 3.5 11.5c0-4.1 3.8-7.4 8.5-7.4s8.5 3.3 8.5 7.4Z"/>',
		'whatsapp'      => '<path d="M20.5 11.6c0 4.1-3.8 7.4-8.5 7.4-1.4 0-2.7-.3-3.9-.9l-3.6 1.3 1.2-3.3a6.9 6.9 0 0 1-1.2-4.5c0-4.1 3.8-7.4 8.5-7.4s7.5 3.3 7.5 7.4Z"/><path d="M10 9.6c.2-.5.9-.6 1.2-.1l.6 1.1c.1.3 0 .6-.2.8l-.4.3c.4.9 1.1 1.6 2 2l.3-.4c.2-.2.5-.3.8-.2l1.1.6c.5.3.4 1-.1 1.2-.7.3-1.5.4-2.2 0a6.5 6.5 0 0 1-3.1-3.1c-.3-.7-.2-1.5.1-2.2Z"/>',
		'messenger'     => '<path d="M12 3.2c-5 0-9.1 3.6-9.1 8.1 0 2.5 1.3 4.8 3.3 6.3v3.6l3.1-1.7c.9.2 1.8.3 2.7.3 5 0 9.1-3.6 9.1-8.1S17 3.2 12 3.2Z"/><path d="m6.9 13.1 2.7-4.2 2.3 2.5 3-1.6-2.7 4.2-2.3-2.5z"/>',
		'facebook'      => '<path d="M14.6 8.6H17V5.5h-2.4c-2.3 0-4.1 1.8-4.1 4.1v2H8v3.1h2.5v6h3.1v-6H16l.5-3.1h-3.4V9.6c0-.6.5-1 1.1-1Z"/>',
		'x'             => '<path d="M4.5 4h4.2l4 5.4L17.6 4h2.4l-6.1 6.8L20.5 20h-4.2l-4.2-5.6L7 20H4.6l6.3-6.9Z"/>',
		'pinterest'     => '<circle cx="12" cy="12" r="8.5"/><path d="m10 18.6 2.4-9.6"/><path d="M9.6 10.2c0-2 1.6-3.4 3.6-3.4 2.1 0 3.5 1.3 3.5 3.3 0 2.2-1.1 3.8-2.7 3.8-.9 0-1.5-.7-1.3-1.5"/>',
		'check'         => '<path d="m5 12.5 4.5 4.5L19 7"/>',
		'shield'        => '<path d="M12 3 4.5 6v5.5c0 4.3 3 7.6 7.5 9 4.5-1.4 7.5-4.7 7.5-9V6Z"/>',
		'refresh'       => '<path d="M4 12a8 8 0 0 1 13.7-5.6L20 8.5"/><path d="M20 4.5v4h-4"/><path d="M20 12a8 8 0 0 1-13.7 5.6L4 15.5"/><path d="M4 19.5v-4h4"/>',
	);

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	return sprintf(
		'<svg class="tj-icon tj-icon--%1$s%2$s" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		$class ? ' ' . esc_attr( $class ) : '',
		$icons[ $name ]
	);
}

/**
 * Return a small inline SVG placeholder so empty image slots still look tidy.
 *
 * @param string $class Extra CSS class for the wrapper.
 * @return string
 */
function techjossecom_placeholder( $class = '' ) {
	return sprintf(
		'<div class="tj-placeholder %1$s" aria-hidden="true"><span>%2$s</span></div>',
		esc_attr( $class ),
		esc_html__( 'Add an image in Appearance → Customize', 'techjossecom' )
	);
}

/**
 * Render an attachment image from a customizer media setting.
 *
 * @param int    $attachment_id Attachment ID stored in the setting.
 * @param string $size          Registered image size.
 * @param array  $attr          Extra attributes.
 * @return string Image markup or an empty string.
 */
function techjossecom_image( $attachment_id, $size = 'large', $attr = array() ) {
	$attachment_id = (int) $attachment_id;

	if ( $attachment_id <= 0 ) {
		return '';
	}

	$attr = wp_parse_args(
		$attr,
		array(
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	);

	return wp_get_attachment_image( $attachment_id, $size, false, $attr );
}

/**
 * Return the URL for an attachment ID, or an empty string when not set.
 *
 * @param int    $attachment_id Attachment ID.
 * @param string $size          Registered image size.
 * @return string
 */
function techjossecom_image_url( $attachment_id, $size = 'full' ) {
	$attachment_id = (int) $attachment_id;

	if ( $attachment_id <= 0 ) {
		return '';
	}

	$url = wp_get_attachment_image_url( $attachment_id, $size );

	return $url ? $url : '';
}

/**
 * Print one footer link column.
 *
 * Priority: a widget area of the same name, then the assigned nav menu
 * (using the menu name as the heading), then top product categories for the
 * first column.
 *
 * @param string $location       Nav menu location slug.
 * @param string $fallback_title Heading used when the menu has no name.
 * @return void
 */
function techjossecom_footer_column( $location, $fallback_title ) {
	$sidebar_id = str_replace( '_', '-', $location );

	if ( is_active_sidebar( $sidebar_id ) ) {
		echo '<div class="tj-footer-col">';
		dynamic_sidebar( $sidebar_id );
		echo '</div>';
		return;
	}

	$locations = get_nav_menu_locations();
	$menu      = ! empty( $locations[ $location ] ) ? wp_get_nav_menu_object( $locations[ $location ] ) : false;

	if ( $menu ) {
		printf( '<div class="tj-footer-col"><h3 class="tj-footer-title">%s</h3>', esc_html( $menu->name ? $menu->name : $fallback_title ) );

		wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'tj-footer-menu',
				'depth'          => 1,
			)
		);

		echo '</div>';
		return;
	}

	// Last resort for the category column: the shop's top product categories.
	$categories = techjossecom_product_categories( 8 );

	if ( ! $categories ) {
		return;
	}

	printf( '<div class="tj-footer-col"><h3 class="tj-footer-title">%s</h3><ul class="tj-footer-menu">', esc_html( $fallback_title ) );

	foreach ( $categories as $term ) {
		printf(
			'<li class="menu-item"><a href="%1$s">%2$s</a></li>',
			esc_url( get_term_link( $term ) ),
			esc_html( $term->name )
		);
	}

	echo '</ul></div>';
}




