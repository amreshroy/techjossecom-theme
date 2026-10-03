<?php
/**
 * Theme settings screen: Appearance → Theme Settings.
 *
 * A plain admin form that edits the same theme mods the customizer writes, so
 * the site can be configured without ever opening the customizer.
 *
 * Every field is one entry in techjossecom_admin_fields() and every group is
 * one entry in techjossecom_admin_sections(), so adding a new option later
 * means adding one more array entry rather than new markup.
 *
 * @package TechJosse_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Group definitions for the settings screen.
 *
 * Each group may carry a short 'note', printed under its heading, for the one
 * fact a group needs stated once rather than repeated on every field.
 *
 * @return array Section id => array( 'title' => string, 'icon' => string, 'note' => string ).
 */
function techjossecom_admin_sections() {
	$sections = array(
		'hero'    => array(
			'title' => __( 'Hero Section', 'techjossecom' ),
			'icon'  => 'dashicons-images-alt2',
		),
		'banners' => array(
			'title' => __( 'Layout 1 Banners', 'techjossecom' ),
			'icon'  => 'dashicons-cover-image',
			'note'  => sprintf(
				/* translators: 1: recommended size of the large banner, 2: recommended size of a small banner. */
				__( 'Artwork sizes: large banner %1$s, each small banner %2$s. All three share one 2.5:1 ratio, which is what makes the three banners line up on a desktop. On a desktop the large banner is drawn wider and shorter, and the title, subtitle, button and each small banner\'s badge and title are laid over the foot of their picture. A picture that is a little off the ratio is trimmed to fill its slot rather than stretched, so keep the message clear of the outer edge.', 'techjossecom' ),
				techjossecom_hero_banner_size_label( 'main' ),
				techjossecom_hero_banner_size_label( 'side' )
			),
		),
		'home'    => array(
			'title' => __( 'Homepage Sections', 'techjossecom' ),
			'icon'  => 'dashicons-screenoptions',
		),
		'contact' => array(
			'title' => __( 'Top Bar & Contact', 'techjossecom' ),
			'icon'  => 'dashicons-phone',
		),
		'footer'  => array(
			'title' => __( 'Footer', 'techjossecom' ),
			'icon'  => 'dashicons-privacy',
		),
		'colors'  => array(
			'title' => __( 'Colours', 'techjossecom' ),
			'icon'  => 'dashicons-art',
		),
	);

	/** This filter is documented below the fields function. */
	return apply_filters( 'techjossecom_admin_sections', $sections );
}

/**
 * Sanitize one posted value according to its field type.
 *
 * @param string $key   Setting name without the theme prefix.
 * @param mixed  $value Raw posted value.
 * @param array  $field Field definition.
 * @return mixed Clean value ready for set_theme_mod().
 */
function techjossecom_admin_sanitize( $key, $value, $field ) {
	$type = isset( $field['type'] ) ? $field['type'] : 'text';

	switch ( $type ) {
		case 'checkbox':
			return ! empty( $value );

		case 'image':
			return absint( $value );

		case 'number':
			$number = absint( $value );

			if ( isset( $field['min'] ) ) {
				$number = max( (int) $field['min'], $number );
			}

			if ( isset( $field['max'] ) ) {
				$number = min( (int) $field['max'], $number );
			}

			return $number;

		case 'url':
			return esc_url_raw( trim( (string) $value ) );

		case 'email':
			return sanitize_email( (string) $value );

		case 'textarea':
			return sanitize_textarea_field( (string) $value );

		/*
		 * The description block is the one field that keeps its markup, because
		 * a sub-heading and a bold keyword are how the text is meant to read.
		 * wp_kses_post() keeps the tags a post may use and drops anything else,
		 * so the field stays as safe as the plain text ones.
		 */
		case 'html':
			return wp_kses_post( (string) $value );

		case 'color':
			$color = sanitize_hex_color( (string) $value );

			// An empty colour box must not wipe the stored value.
			return $color ? $color : techjossecom_mod( $key );

		case 'select':
			$choices = isset( $field['choices'] ) ? $field['choices'] : array();

			return array_key_exists( $value, $choices ) ? $value : techjossecom_mod( $key );

		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Every setting the screen can edit.
 *
 * Each entry is one field:
 *   key         setting name without the theme prefix
 *   type        text | textarea | html | url | email | number | checkbox | image | select | color
 *   label       field label
 *   description optional help text under the label
 *   section     which group the field belongs to
 *   choices     options for the 'select' type
 *   min / max   bounds for the 'number' type
 *
 * @return array Field definitions keyed by setting name.
 */
function techjossecom_admin_fields() {
	$fields = array(
		// --- Hero layout ------------------------------------------------------
		'hero_layout'         => array(
			'type'        => 'select',
			'label'       => __( 'Hero design', 'techjossecom' ),
			'description' => __( 'Pick the design used at the top of the homepage. Both designs keep their own settings, so you can switch back and forth without losing anything.', 'techjossecom' ),
			'section'     => 'hero',
			'choices'     => techjossecom_hero_layouts(),
		),
		'hero_slider_autoplay' => array(
			'type'        => 'checkbox',
			'label'       => __( 'Slide the banners automatically', 'techjossecom' ),
			'description' => __( 'The banner holds still while the visitor is hovering over it, and respects the "reduce motion" browser setting.', 'techjossecom' ),
			'section'     => 'hero',
			'only_layout' => 'slider',
		),
		'hero_slider_speed'   => array(
			'type'        => 'number',
			'label'       => __( 'Seconds per banner', 'techjossecom' ),
			'section'     => 'hero',
			'min'         => 2,
			'max'         => 30,
			'only_layout' => 'slider',
		),
	);

	/*
	 * The slider owns a fixed number of slides, so the image and link fields
	 * are generated instead of typed out. Raising techjossecom_hero_slide_count()
	 * adds a slide to this screen, to the defaults and to the front end at once.
	 */
	for ( $techjossecom_i = 1; $techjossecom_i <= techjossecom_hero_slide_count(); $techjossecom_i++ ) {
		$fields[ 'hero_slide' . $techjossecom_i . '_image' ] = array(
			'type'        => 'image',
			'label'       => sprintf(
				/* translators: %d: slide number. */
				__( 'Banner %d image', 'techjossecom' ),
				$techjossecom_i
			),
			'description' => 1 === $techjossecom_i
				? __( 'Recommended 1600 x 300 px. Leave a slide empty to skip it.', 'techjossecom' )
				: '',
			'section'     => 'hero',
			'only_layout' => 'slider',
		);

		$fields[ 'hero_slide' . $techjossecom_i . '_mobile_image' ] = array(
				'type'        => 'image',
				'label'       => sprintf(
						/* translators: %d: slide number. */
						__( 'Banner %d phone image', 'techjossecom' ),
						$techjossecom_i
				),
				'description' => 1 === $techjossecom_i
						? __( 'Optional. Shown instead of the banner image on phones, so a phone does not download the wide desktop file. Recommended 800 x 300 px. Leave empty to use one picture everywhere.', 'techjossecom' )
						: '',
				'section'     => 'hero',
				'only_layout' => 'slider',
		);

		$fields[ 'hero_slide' . $techjossecom_i . '_url' ] = array(
			'type'        => 'url',
			'label'       => sprintf(
				/* translators: %d: slide number. */
				__( 'Banner %d link', 'techjossecom' ),
				$techjossecom_i
			),
			'description' => 1 === $techjossecom_i
				? __( 'Where the banner points. Leave empty to show the image without a link.', 'techjossecom' )
				: '',
			'section'     => 'hero',
			'only_layout' => 'slider',
		);
	}

	$fields += array(
		// --- Layout 1 banners --------------------------------------------------
		/*
		 * The sizes below are read from techjossecom_hero_banner_specs(), which is
		 * the same place the front end reads them from, so the advice given here
		 * can never drift away from the layout that renders the picture.
		 */
		'hero1_image'        => array(
			'type'        => 'image',
			'label'       => __( 'Main banner image', 'techjossecom' ),
			'description' => sprintf(
				/* translators: %s: recommended image size, e.g. "1200 × 600 px". */
				__( 'The large banner on the left. Recommended %s. On a desktop it is drawn wider and shorter, and the title, subtitle and button sit over the foot of the picture.', 'techjossecom' ),
				techjossecom_hero_banner_size_label( 'main' )
			),
			'section'     => 'banners',
		),
		'hero1_title'        => array(
			'type'    => 'text',
			'label'   => __( 'Main banner title', 'techjossecom' ),
			'description' => __( 'Shown over the banner at every screen size, and used as the image description, so always fill it in.', 'techjossecom' ),
			'section' => 'banners',
		),
		'hero1_subtitle'     => array(
			'type'        => 'text',
			'label'       => __( 'Main banner subtitle', 'techjossecom' ),
			'description' => __( 'Shown over the banner, under the title.', 'techjossecom' ),
			'section'     => 'banners',
		),
		'hero1_button_text'  => array(
			'type'        => 'text',
			'label'       => __( 'Main banner button text', 'techjossecom' ),
			'description' => __( 'Shown over the banner. The whole banner is a link as well, so the button is a shortcut rather than the only way through.', 'techjossecom' ),
			'section'     => 'banners',
		),
		'hero1_button_url'   => array(
			'type'        => 'url',
			'label'       => __( 'Main banner button link', 'techjossecom' ),
			'description' => __( 'Where the banner points. Leave empty to link to the shop page.', 'techjossecom' ),
			'section'     => 'banners',
		),
		'hero2_image'        => array(
			'type'        => 'image',
			'label'       => __( 'Side banner 1 image', 'techjossecom' ),
			'description' => sprintf(
				/* translators: %s: recommended image size, e.g. "600 × 300 px". */
				__( 'The small banner on the right, top. Recommended %s - the same ratio as the main banner, at half the height.', 'techjossecom' ),
				techjossecom_hero_banner_size_label( 'side' )
			),
			'section'     => 'banners',
		),
		'hero2_badge'        => array(
			'type'        => 'text',
			'label'       => __( 'Side banner 1 badge', 'techjossecom' ),
			'description' => __( 'A short label above the title, laid over the foot of the banner.', 'techjossecom' ),
			'section'     => 'banners',
		),
		'hero2_title'        => array(
			'type'        => 'text',
			'label'       => __( 'Side banner 1 title', 'techjossecom' ),
			'description' => __( 'Under the banner on phones, over its foot on a desktop. Also used as the image description, so always fill it in.', 'techjossecom' ),
			'section'     => 'banners',
		),
		'hero2_url'          => array(
			'type'        => 'url',
			'label'       => __( 'Side banner 1 link', 'techjossecom' ),
			'description' => __( 'Leave empty to link to the shop page.', 'techjossecom' ),
			'section'     => 'banners',
		),
		'hero3_image'        => array(
			'type'        => 'image',
			'label'       => __( 'Side banner 2 image', 'techjossecom' ),
			'description' => sprintf(
				/* translators: %s: recommended image size, e.g. "600 × 300 px". */
				__( 'The small banner on the right, bottom. Recommended %s.', 'techjossecom' ),
				techjossecom_hero_banner_size_label( 'side' )
			),
			'section'     => 'banners',
		),
		'hero3_badge'        => array(
			'type'        => 'text',
			'label'       => __( 'Side banner 2 badge', 'techjossecom' ),
			'description' => __( 'A short label above the title, laid over the foot of the banner.', 'techjossecom' ),
			'section'     => 'banners',
		),
		'hero3_title'        => array(
			'type'        => 'text',
			'label'       => __( 'Side banner 2 title', 'techjossecom' ),
			'description' => __( 'Under the banner on phones, over its foot on a desktop. Also used as the image description, so always fill it in.', 'techjossecom' ),
			'section'     => 'banners',
		),
		'hero3_url'          => array(
			'type'        => 'url',
			'label'       => __( 'Side banner 2 link', 'techjossecom' ),
			'description' => __( 'Leave empty to link to the shop page.', 'techjossecom' ),
			'section'     => 'banners',
		),

		// --- Homepage sections ---------------------------------------------
		'show_front_heading' => array(
			'type'    => 'checkbox',
			'label'   => __( 'Show the page heading above the banner', 'techjossecom' ),
			'section' => 'home',
		),
		'front_heading'      => array(
			'type'    => 'text',
			'label'   => __( 'Page heading text (H1)', 'techjossecom' ),
			'section' => 'home',
		),
		'show_categories'    => array(
			'type'    => 'checkbox',
			'label'   => __( 'Show the "Shop by Category" grid', 'techjossecom' ),
			'section' => 'home',
		),
		'categories_title'   => array(
			'type'    => 'text',
			'label'   => __( 'Category section heading', 'techjossecom' ),
			'section' => 'home',
		),
		'categories_limit'   => array(
			'type'    => 'number',
			'label'   => __( 'Number of categories', 'techjossecom' ),
			'section' => 'home',
			'min'     => 2,
			'max'     => 24,
		),
		'categories_mobile_limit' => array(
			'type'        => 'number',
			'label'       => __( 'Categories on mobile', 'techjossecom' ),
			'description' => __( 'Leave 0 to use the desktop number. Set a smaller number to show fewer categories on phones.', 'techjossecom' ),
			'section'     => 'home',
			'min'         => 0,
			'max'         => 24,
		),
		'show_deals'         => array(
			'type'    => 'checkbox',
			'label'   => __( 'Show the "Best Deals" grid', 'techjossecom' ),
			'section' => 'home',
		),
		'deals_title'        => array(
			'type'    => 'text',
			'label'   => __( 'Deals section heading', 'techjossecom' ),
			'section' => 'home',
		),
		'deals_limit'        => array(
			'type'    => 'number',
			'label'   => __( 'Number of deal products', 'techjossecom' ),
			'section' => 'home',
			'min'     => 2,
			'max'     => 16,
		),
		'deals_mobile_limit' => array(
			'type'        => 'number',
			'label'       => __( 'Deal products on mobile', 'techjossecom' ),
			'description' => __( 'Leave 0 to use the desktop number. Set a smaller number to show fewer deals on phones.', 'techjossecom' ),
			'section'     => 'home',
			'min'         => 0,
			'max'         => 16,
		),
		'show_latest'        => array(
			'type'    => 'checkbox',
			'label'   => __( 'Show the "New Arrivals" grid', 'techjossecom' ),
			'section' => 'home',
		),
		'latest_title'       => array(
			'type'    => 'text',
			'label'   => __( 'New Arrivals section heading', 'techjossecom' ),
			'section' => 'home',
		),
		'latest_limit'       => array(
			'type'    => 'number',
			'label'   => __( 'Number of new arrival products', 'techjossecom' ),
			'section' => 'home',
			'min'     => 2,
			'max'     => 16,
		),
		'latest_mobile_limit' => array(
			'type'        => 'number',
			'label'       => __( 'New arrival products on mobile', 'techjossecom' ),
			'description' => __( 'Leave 0 to use the desktop number. Set a smaller number to show fewer new arrivals on phones.', 'techjossecom' ),
			'section'     => 'home',
			'min'         => 0,
			'max'         => 16,
		),
		'show_intro'         => array(
			'type'    => 'checkbox',
			'label'   => __( 'Show the intro text block', 'techjossecom' ),
			'section' => 'home',
		),
		'intro_title'        => array(
			'type'    => 'text',
			'label'   => __( 'Intro heading', 'techjossecom' ),
			'section' => 'home',
		),
		'intro_text'         => array(
			'type'    => 'textarea',
			'label'   => __( 'Intro paragraph', 'techjossecom' ),
			'section' => 'home',
		),
		'show_featured'       => array(
			'type'        => 'checkbox',
			'label'       => __( 'Show the "Featured Products" grid', 'techjossecom' ),
			'description' => __( 'Shows the products ticked "Featured" in WooCommerce → Products, under the New Arrivals row.', 'techjossecom' ),
			'section'     => 'home',
		),
		'featured_title'      => array(
			'type'    => 'text',
			'label'   => __( 'Featured Products section heading', 'techjossecom' ),
			'section' => 'home',
		),
		'featured_limit'      => array(
			'type'    => 'number',
			'label'   => __( 'Number of featured products', 'techjossecom' ),
			'section' => 'home',
			'min'     => 2,
			'max'     => 16,
		),
		'featured_mobile_limit' => array(
			'type'        => 'number',
			'label'       => __( 'Featured products on mobile', 'techjossecom' ),
			'description' => __( 'Leave 0 to use the desktop number. Set a smaller number to show fewer featured products on phones.', 'techjossecom' ),
			'section'     => 'home',
			'min'         => 0,
			'max'         => 16,
		),
		'show_seo'            => array(
			'type'        => 'checkbox',
			'label'       => __( 'Show the homepage description block', 'techjossecom' ),
			'description' => __( 'The keyword text at the foot of the homepage, opened by a "Read More" button.', 'techjossecom' ),
			'section'     => 'home',
		),
		'seo_title'           => array(
			'type'    => 'text',
			'label'   => __( 'Description block heading', 'techjossecom' ),
			'section' => 'home',
		),
		'seo_text'            => array(
			'type'        => 'html',
			'label'       => __( 'Description block text', 'techjossecom' ),
			'description' => __( 'Basic HTML is kept here, so sub-headings and bold keywords survive. The whole text is printed on the page and read by search engines; only its height is capped behind the "Read More" button.', 'techjossecom' ),
			'section'     => 'home',
		),
		'seo_read_more'       => array(
			'type'    => 'text',
			'label'   => __( 'Description block button label', 'techjossecom' ),
			'section' => 'home',
		),

		// --- Top bar and contact -------------------------------------------
		'show_topbar'        => array(
			'type'    => 'checkbox',
			'label'   => __( 'Show the top bar', 'techjossecom' ),
			'section' => 'contact',
		),
		'topbar_text'        => array(
			'type'    => 'text',
			'label'   => __( 'Top bar welcome text', 'techjossecom' ),
			'section' => 'contact',
		),
		'hotline_label'      => array(
			'type'    => 'text',
			'label'   => __( 'Hotline label', 'techjossecom' ),
			'section' => 'contact',
		),
		'hotline_number'     => array(
			'type'        => 'text',
			'label'       => __( 'Hotline number', 'techjossecom' ),
			'description' => __( 'Shown in the header and used by the click-to-call link.', 'techjossecom' ),
			'section'     => 'contact',
		),
		'whatsapp_number'    => array(
			'type'        => 'text',
			'label'       => __( 'WhatsApp number', 'techjossecom' ),
			'description' => __( 'Include the country code, for example +8801700000000.', 'techjossecom' ),
			'section'     => 'contact',
		),
		'contact_email'      => array(
			'type'    => 'email',
			'label'   => __( 'Contact email', 'techjossecom' ),
			'section' => 'contact',
		),
		'contact_address'    => array(
			'type'    => 'textarea',
			'label'   => __( 'Address', 'techjossecom' ),
			'section' => 'contact',
		),

		// --- Footer -----------------------------------------------------------
		'footer_about'       => array(
			'type'    => 'textarea',
			'label'   => __( 'About text (first column)', 'techjossecom' ),
			'section' => 'footer',
		),
		'footer_payments'    => array(
			'type'    => 'text',
			'label'   => __( 'Payment note', 'techjossecom' ),
			'section' => 'footer',
		),
		'footer_copyright'   => array(
			'type'        => 'text',
			'label'       => __( 'Copyright line', 'techjossecom' ),
			'description' => __( 'Leave empty to use the site name and the current year.', 'techjossecom' ),
			'section'     => 'footer',
		),

		// --- Colours -----------------------------------------------------------
		'primary_color'      => array(
			'type'    => 'color',
			'label'   => __( 'Primary colour', 'techjossecom' ),
			'section' => 'colors',
		),
		'accent_color'       => array(
			'type'    => 'color',
			'label'   => __( 'Accent / price colour', 'techjossecom' ),
			'section' => 'colors',
		),
		'dark_color'         => array(
			'type'    => 'color',
			'label'   => __( 'Dark colour (header & footer)', 'techjossecom' ),
			'section' => 'colors',
		),
		'secondary_color'    => array(
			'type'        => 'color',
			'label'       => __( 'Secondary colour', 'techjossecom' ),
			'description' => __( 'Used for the "All Categories" menu button.', 'techjossecom' ),
			'section'     => 'colors',
		),
	);

	/*
	 * Everything in the "banners" group drives layout 1, so the rows are tagged
	 * in one pass instead of repeating "only_layout" on all twelve entries.
	 * The values stay in the form either way, so switching the design back and
	 * forth never empties a field.
	 */
	foreach ( $fields as $techjossecom_key => $techjossecom_field ) {
		if ( 'banners' === $techjossecom_field['section'] ) {
			$fields[ $techjossecom_key ]['only_layout'] = 'split';
		}
	}

	/**
	 * Filter the fields shown on the theme settings screen.
	 *
	 * @param array $fields Field definitions keyed by setting name.
	 */
	return apply_filters( 'techjossecom_admin_fields', $fields );
}

/**
 * Register the screen under Appearance.
 *
 * @return void
 */
function techjossecom_admin_menu() {
	add_theme_page(
		__( 'Theme Settings', 'techjossecom' ),
		__( 'Theme Settings', 'techjossecom' ),
		'edit_theme_options',
		'techjossecom-settings',
		'techjossecom_admin_page'
	);
}
add_action( 'admin_menu', 'techjossecom_admin_menu' );

/**
 * The media library scripts, needed by the image picker.
 *
 * @param string $hook Current admin page hook.
 * @return void
 */
function techjossecom_admin_scripts( $hook ) {
	if ( 'appearance_page_techjossecom-settings' !== $hook ) {
		return;
	}

	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'techjossecom_admin_scripts' );

/**
 * Handle a submitted settings form.
 *
 * Runs on admin_init so the post/redirect happens before any output, which is
 * the way WordPress expects a form handler to behave.
 *
 * @return void
 */
function techjossecom_admin_save() {
	if ( ! isset( $_POST['techjossecom_settings_nonce'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to change theme settings.', 'techjossecom' ) );
	}

	check_admin_referer( 'techjossecom_save_settings', 'techjossecom_settings_nonce' );

	$fields = techjossecom_admin_fields();
	$saved  = 0;

	foreach ( $fields as $key => $field ) {
		// An unchecked checkbox is never posted, so treat it as "off".
		if ( 'checkbox' === $field['type'] ) {
			set_theme_mod( 'techjossecom_' . $key, isset( $_POST['tj'][ $key ] ) );
			$saved++;

			continue;
		}

		// The "remove image" button posts a flag for that field only.
		if ( isset( $_POST['tj_clear'][ $key ] ) && '1' === $_POST['tj_clear'][ $key ] ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitised
			set_theme_mod( 'techjossecom_' . $key, 'image' === $field['type'] ? 0 : '' );
			$saved++;

			continue;
		}

		if ( ! isset( $_POST['tj'][ $key ] ) ) {
			continue;
		}

		$raw = wp_unslash( $_POST['tj'][ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitised

		set_theme_mod( 'techjossecom_' . $key, techjossecom_admin_sanitize( $key, $raw, $field ) );
		$saved++;
	}

	// Redirect so a refresh does not resubmit the form.
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'    => 'techjossecom-settings',
				'updated' => $saved,
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}
add_action( 'admin_init', 'techjossecom_admin_save' );

/**
 * Render an image field with a preview and the media picker.
 *
 * @param string $key   Setting name without the prefix.
 * @param mixed  $value Stored attachment ID.
 * @return void
 */
function techjossecom_admin_render_image( $key, $value ) {
	$id       = 'tj-' . $key;
	$image_id = (int) $value;
	$preview  = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

	echo '<div class="tj-image" data-tj-image="' . esc_attr( $key ) . '">';

	if ( $preview ) {
		echo '<div class="tj-image__preview"><img src="' . esc_url( $preview ) . '" alt="" /></div>';
	} else {
		echo '<div class="tj-image__preview tj-image__preview--empty"><span>' . esc_html__( 'No image selected', 'techjossecom' ) . '</span></div>';
	}

	echo '<input type="hidden" name="tj[' . esc_attr( $key ) . ']" id="' . esc_attr( $id ) . '" value="' . esc_attr( $image_id ) . '" />';

	echo '<p class="tj-image__actions">';
	echo '<button type="button" class="button" data-tj-image-select>' . esc_html__( 'Choose image', 'techjossecom' ) . '</button> ';
	echo '<button type="button" class="button-link" data-tj-image-clear>' . esc_html__( 'Remove', 'techjossecom' ) . '</button>';
	echo '<input type="hidden" name="tj_clear[' . esc_attr( $key ) . ']" value="0" data-tj-image-clear-flag />';
	echo '</p></div>';
}

/**
 * Render one field, whatever its type.
 *
 * @param string $key   Setting name without the prefix.
 * @param array  $field Field definition.
 * @return void
 */
function techjossecom_admin_render_field( $key, $field ) {
	$id    = 'tj-' . $key;
	$value = techjossecom_mod( $key );
	$type  = isset( $field['type'] ) ? $field['type'] : 'text';

	/*
	 * "only_layout" marks a field that belongs to one hero design. The markup
	 * is still rendered - the browser needs it so the values post back and are
	 * never lost - and the settings script hides the row when another design
	 * is selected.
	 */
	$only = ! empty( $field['only_layout'] ) ? ' data-tj-layout="' . esc_attr( $field['only_layout'] ) . '"' : '';

	echo '<tr class="tj-field tj-field--' . esc_attr( $type ) . '"' . $only . '><th scope="row">';
	echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';

	if ( ! empty( $field['description'] ) ) {
		echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
	}

	echo '</th><td>';

	switch ( $type ) {
		case 'checkbox':
			echo '<label class="tj-switch"><input type="checkbox" id="' . esc_attr( $id ) . '" name="tj[' . esc_attr( $key ) . ']" value="1" ' . checked( (bool) $value, true, false ) . ' />';
			echo '<span>' . esc_html__( 'Enabled', 'techjossecom' ) . '</span></label>';
			break;

		case 'textarea':
			echo '<textarea id="' . esc_attr( $id ) . '" name="tj[' . esc_attr( $key ) . ']" rows="4" class="large-text">' . esc_textarea( $value ) . '</textarea>';
			break;

		/*
		 * The same box as the plain text areas, only taller: the description
		 * block holds a heading and several paragraphs, and the sanitiser behind
		 * it - wp_kses_post() - is the same one a post goes through.
		 */
		case 'html':
			echo '<textarea id="' . esc_attr( $id ) . '" name="tj[' . esc_attr( $key ) . ']" rows="12" class="large-text code">' . esc_textarea( $value ) . '</textarea>';
			break;

		case 'number':
			echo '<input type="number" id="' . esc_attr( $id ) . '" name="tj[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" class="small-text"';
			echo ' min="' . esc_attr( isset( $field['min'] ) ? $field['min'] : 0 ) . '"';
			echo ' max="' . esc_attr( isset( $field['max'] ) ? $field['max'] : 100 ) . '" />';
			break;

		case 'color':
			echo '<input type="text" id="' . esc_attr( $id ) . '" name="tj[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" class="tj-color" placeholder="#000000" />';
			break;

		case 'select':
			echo '<select id="' . esc_attr( $id ) . '" name="tj[' . esc_attr( $key ) . ']">';

			foreach ( $field['choices'] as $choice_value => $choice_label ) {
				echo '<option value="' . esc_attr( $choice_value ) . '" ' . selected( $value, $choice_value, false ) . '>' . esc_html( $choice_label ) . '</option>';
			}

			echo '</select>';
			break;

		case 'image':
			techjossecom_admin_render_image( $key, $value );
			break;

		default:
			echo '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="tj[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '" class="regular-text" />';
			break;
	}

	echo '</td></tr>';
}

/**
 * Render the settings screen.
 *
 * @return void
 */
function techjossecom_admin_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$fields   = techjossecom_admin_fields();
	$sections = techjossecom_admin_sections();
	$updated  = isset( $_GET['updated'] ) ? absint( $_GET['updated'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	echo '<div class="wrap tj-settings">';
	echo '<h1>' . esc_html__( 'Theme Settings', 'techjossecom' ) . '</h1>';

	if ( $updated ) {
		echo '<div class="notice notice-success is-dismissible"><p>';

		/* translators: %d: number of saved settings. */
		printf( esc_html__( '%d setting(s) saved.', 'techjossecom' ), $updated );

		echo '</p></div>';
	}

	echo '<p class="description">' . esc_html__( 'Everything the homepage and header show is configured here. No customizer needed.', 'techjossecom' ) . '</p>';

	// Only render groups that have at least one field.
	$grouped = array();

	foreach ( $fields as $key => $field ) {
		$group               = isset( $field['section'] ) ? $field['section'] : 'misc';
		$grouped[ $group ][] = $key;
	}

	echo '<form method="post">';
	wp_nonce_field( 'techjossecom_save_settings', 'techjossecom_settings_nonce' );

	foreach ( $sections as $group => $meta ) {
		if ( empty( $grouped[ $group ] ) ) {
			continue;
		}

		echo '<div class="tj-card"><h2 class="tj-card__title">';
		echo '<span class="dashicons ' . esc_attr( $meta['icon'] ) . '" aria-hidden="true"></span>';
		echo esc_html( $meta['title'] );
		echo '</h2>';

		if ( ! empty( $meta['note'] ) ) {
			echo '<p class="tj-card__note">' . esc_html( $meta['note'] ) . '</p>';
		}

		echo '<table class="form-table tj-table"><tbody>';

		foreach ( $grouped[ $group ] as $key ) {
			techjossecom_admin_render_field( $key, $fields[ $key ] );
		}

		echo '</tbody></table></div>';
	}

	submit_button( __( 'Save settings', 'techjossecom' ) );

	echo '</form></div>';
}

/**
 * Small stylesheet for the settings screen.
 *
 * @return void
 */
function techjossecom_admin_style() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'appearance_page_techjossecom-settings' !== $screen->id ) {
		return;
	}

	echo '<style>
		.tj-settings .tj-card{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:4px 20px 16px;margin:20px 0}
		.tj-settings .tj-card__title{display:flex;align-items:center;gap:8px;font-size:15px;margin:16px 0 4px}
		.tj-settings .tj-card__title .dashicons{color:#2271b1}
		.tj-settings .tj-table th{width:280px;font-weight:600}
		.tj-settings .tj-table .description{margin:4px 0 0;font-style:normal;color:#646970}
		.tj-image{display:flex;gap:16px;align-items:flex-start}
		.tj-image__preview{width:180px;height:110px;border:1px solid #dcdcde;border-radius:6px;overflow:hidden;background:#f6f7f7;display:flex;align-items:center;justify-content:center}
		.tj-image__preview img{width:100%;height:100%;object-fit:cover;display:block}
		.tj-image__preview--empty span{color:#8c8f94;font-size:12px}
		.tj-image__actions{margin:0;display:flex;gap:10px;align-items:center}
		.tj-image__actions .button-link{color:#b32d2e}
		.tj-switch{display:inline-flex;align-items:center;gap:6px}
		.tj-settings .tj-card__note{margin:0 0 4px;color:#646970}
		.tj-settings tr[hidden]{display:none}
	</style>';
}
add_action( 'admin_head', 'techjossecom_admin_style' );

/**
 * Media picker behaviour for the image fields.
 *
 * @return void
 */
function techjossecom_admin_media_script() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'appearance_page_techjossecom-settings' !== $screen->id ) {
		return;
	}
	?>
	<script>
	( function () {
		/*
		 * One media frame is shared by every field, but the "select" handler still
		 * has to know which field the visitor is filling. "current" is set on every
		 * click, so a pick always lands in the field whose button was pressed.
		 * Binding the handler to one field instead would send every later pick back
		 * to whichever field happened to be used first, which is what made slide 4
		 * overwrite slide 3.
		 */
		var frame = null;
		var current = null;

		document.querySelectorAll( '[data-tj-image]' ).forEach( function ( wrap ) {
			var input     = wrap.querySelector( 'input[type="hidden"][name^="tj["]' );
			var preview   = wrap.querySelector( '.tj-image__preview' );
			var clearFlag = wrap.querySelector( '[data-tj-image-clear-flag]' );

			wrap.querySelector( '[data-tj-image-select]' ).addEventListener( 'click', function () {
				current = { input: input, preview: preview, clearFlag: clearFlag };

				if ( ! frame ) {
					frame = wp.media( {
						title: 'Choose an image',
						button: { text: 'Use this image' },
						library: { type: 'image' },
						multiple: false
					} );

					frame.on( 'select', function () {
						if ( ! current ) {
							return;
						}

						var item = frame.state().get( 'selection' ).first().toJSON();
						var url  = ( item.sizes && item.sizes.medium ) ? item.sizes.medium.url : item.url;

						current.input.value = item.id;
						current.clearFlag.value = '0';

						current.preview.classList.remove( 'tj-image__preview--empty' );
						current.preview.innerHTML = '<img src="' + url + '" alt="" />';
					} );
				}

				frame.open();
			} );

			wrap.querySelector( '[data-tj-image-clear]' ).addEventListener( 'click', function () {
				input.value = '';
				clearFlag.value = '1';

				preview.classList.add( 'tj-image__preview--empty' );
				preview.innerHTML = '<span>No image selected</span>';
			} );
		} );

		/*
		 * Hero design switcher.
		 *
		 * Rows tagged with data-tj-layout belong to one design, so they are hidden
		 * when a different design is picked. They stay in the DOM and keep their
		 * values, which is what makes switching back and forth non destructive.
		 */
		var layoutSelect = document.getElementById( 'tj-hero_layout' );

		if ( layoutSelect ) {
			var applyLayout = function () {
				document.querySelectorAll( '[data-tj-layout]' ).forEach( function ( row ) {
					row.hidden = row.getAttribute( 'data-tj-layout' ) !== layoutSelect.value;
				} );
			};

			layoutSelect.addEventListener( 'change', applyLayout );
			applyLayout();
		}
	} )();
	</script>
	<?php
}
add_action( 'admin_print_footer_scripts', 'techjossecom_admin_media_script' );

