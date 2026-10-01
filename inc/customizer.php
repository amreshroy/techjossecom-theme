<?php
/**
 * Theme customizer: every visible string, image and toggle lives here so the
 * theme can be re-skinned for any niche without touching template files.
 *
 * @package TechJosse_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize a checkbox setting.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function techjossecom_sanitize_checkbox( $value ) {
	return (bool) $value;
}

/**
 * Sanitize a number setting.
 *
 * @param mixed $value Raw value.
 * @return int
 */
function techjossecom_sanitize_number( $value ) {
	return absint( $value );
}

/**
 * Sanitize a select setting against its registered choices.
 *
 * @param mixed               $value   Raw value.
 * @param WP_Customize_Setting $setting Setting object.
 * @return string
 */
function techjossecom_sanitize_select( $value, $setting ) {
	$control = $setting->manager->get_control( $setting->id );

	if ( $control && ! empty( $control->choices ) && array_key_exists( $value, $control->choices ) ) {
		return $value;
	}

	return $setting->default;
}

/**
 * Sanitize a sanitize_text_field() value.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function techjossecom_sanitize_text( $value ) {
	return sanitize_text_field( (string) $value );
}

/**
 * Sanitize a textarea setting.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function techjossecom_sanitize_textarea( $value ) {
	return sanitize_textarea_field( (string) $value );
}

/**
 * Sanitize a URL setting.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function techjossecom_sanitize_url( $value ) {
	return esc_url_raw( (string) $value );
}

/**
 * Sanitize an email setting.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function techjossecom_sanitize_email( $value ) {
	return sanitize_email( (string) $value );
}

/**
 * Sanitize a colour setting.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function techjossecom_sanitize_color( $value ) {
	$color = sanitize_hex_color( (string) $value );

	return $color ? $color : '';
}

/**
 * Pick the right sanitize callback for a field type.
 *
 * @param string $type Field type.
 * @return string Callback name.
 */
function techjossecom_sanitize_callback( $type ) {
	switch ( $type ) {
		case 'checkbox':
			return 'techjossecom_sanitize_checkbox';
		case 'number':
			return 'techjossecom_sanitize_number';
		case 'select':
			return 'techjossecom_sanitize_select';
		case 'textarea':
			return 'techjossecom_sanitize_textarea';
		case 'url':
			return 'techjossecom_sanitize_url';
		case 'email':
			return 'techjossecom_sanitize_email';
		case 'color':
			return 'techjossecom_sanitize_color';
		case 'image':
			return 'techjossecom_sanitize_number';
		default:
			return 'techjossecom_sanitize_text';
	}
}

/**
 * Register a batch of settings and controls inside one section.
 *
 * Each definition uses the same key as techjossecom_defaults(), so the default
 * value automatically matches the template fallback.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @param string               $section      Section ID.
 * @param array                $fields       Field definitions keyed by setting key.
 * @return void
 */
function techjossecom_add_fields( $wp_customize, $section, $fields ) {
	$defaults = techjossecom_defaults();

	foreach ( $fields as $key => $field ) {
		$setting_id = 'techjossecom_' . $key;
		$type       = isset( $field['type'] ) ? $field['type'] : 'text';
		$default    = array_key_exists( 'default', $field )
			? $field['default']
			: ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : '' );

		$wp_customize->add_setting(
			$setting_id,
			array(
				'default'           => $default,
				'sanitize_callback' => isset( $field['sanitize'] ) ? $field['sanitize'] : techjossecom_sanitize_callback( $type ),
				'transport'         => 'refresh',
			)
		);

		$args = array(
			'label'       => isset( $field['label'] ) ? $field['label'] : $key,
			'description' => isset( $field['description'] ) ? $field['description'] : '',
			'section'     => $section,
			'priority'    => isset( $field['priority'] ) ? $field['priority'] : 10,
		);

		if ( 'image' === $type ) {
			$args['mime_type'] = 'image';
			$wp_customize->add_control(
				new WP_Customize_Media_Control( $wp_customize, $setting_id, $args )
			);
			continue;
		}

		$args['type'] = $type;

		if ( 'select' === $type ) {
			$args['choices'] = isset( $field['choices'] ) ? $field['choices'] : array();
		}

		if ( 'number' === $type ) {
			$args['input_attrs'] = array(
				'min'  => isset( $field['min'] ) ? $field['min'] : 0,
				'step' => isset( $field['step'] ) ? $field['step'] : 1,
			);

			if ( isset( $field['max'] ) ) {
				$args['input_attrs']['max'] = $field['max'];
			}
		}

		$wp_customize->add_control( $setting_id, $args );
	}
}
/**
 * Register the theme customizer.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @return void
 */
function techjossecom_customize_register( $wp_customize ) {
	$wp_customize->add_panel(
		'techjossecom_panel',
		array(
			'title'       => __( 'TechJosse Theme Options', 'techjossecom' ),
			'description' => __( 'Top bar, hero banners, homepage sections, Cash on Delivery popup and footer.', 'techjossecom' ),
			'priority'    => 20,
		)
	);

	$sections = array(
		'topbar'  => array( __( '1. Top Bar & Contact', 'techjossecom' ), 10 ),
		'header'  => array( __( '2. Header & Live Search', 'techjossecom' ), 20 ),
		'hero'    => array( __( '3. Homepage Hero Banners', 'techjossecom' ), 30 ),
		'home'    => array( __( '4. Homepage Sections', 'techjossecom' ), 40 ),
		'cod'     => array( __( '5. Cash on Delivery Popup', 'techjossecom' ), 50 ),
		'product' => array( __( '6. Product Page', 'techjossecom' ), 60 ),
		'footer'  => array( __( '7. Footer', 'techjossecom' ), 70 ),
		'colors'  => array( __( '8. Colours', 'techjossecom' ), 80 ),
	);

	foreach ( $sections as $slug => $data ) {
		$wp_customize->add_section(
			'techjossecom_section_' . $slug,
			array(
				'title'    => $data[0],
				'panel'    => 'techjossecom_panel',
				'priority' => $data[1],
			)
		);
	}

	// ---------------------------------------------------------------------
	// 1. Top bar and contact details.
	// ---------------------------------------------------------------------
	techjossecom_add_fields(
		$wp_customize,
		'techjossecom_section_topbar',
		array(
			'show_topbar'       => array(
				'label'    => __( 'Show the top bar', 'techjossecom' ),
				'type'     => 'checkbox',
				'priority' => 5,
			),
			'topbar_text'       => array(
				'label'    => __( 'Top bar welcome text', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 10,
			),
			'hotline_label'     => array(
				'label'    => __( 'Hotline label', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 20,
			),
			'hotline_number'    => array(
				'label'       => __( 'Hotline number', 'techjossecom' ),
				'description' => __( 'Shown in the header and used by the click-to-call link.', 'techjossecom' ),
				'type'        => 'text',
				'priority'    => 30,
			),
			'whatsapp_number'   => array(
				'label'       => __( 'WhatsApp number', 'techjossecom' ),
				'description' => __( 'Include the country code, for example +8801700000000.', 'techjossecom' ),
				'type'        => 'text',
				'priority'    => 40,
			),
			'messenger_username' => array(
				'label'       => __( 'Messenger username', 'techjossecom' ),
				'description' => __( 'Facebook page username without the @ sign.', 'techjossecom' ),
				'type'        => 'text',
				'priority'    => 50,
			),
		)
	);

	// ---------------------------------------------------------------------
	// 2. Header and live search.
	// ---------------------------------------------------------------------
	techjossecom_add_fields(
		$wp_customize,
		'techjossecom_section_header',
		array(
			'search_placeholder' => array(
				'label'    => __( 'Search box placeholder', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 10,
			),
			'show_live_search'   => array(
				'label'       => __( 'Enable AJAX live search suggestions', 'techjossecom' ),
				'description' => __( 'Products appear in a dropdown while typing. Disable to use a plain search form.', 'techjossecom' ),
				'type'        => 'checkbox',
				'priority'    => 20,
			),
		)
	);

	// ---------------------------------------------------------------------
	// 3. Hero design and the layout 1 banners.
	// ---------------------------------------------------------------------

	/*
	 * The design picker comes first, then the settings layout 1 uses. The
	 * slider settings live in their own section below, so the picker and the
	 * two option sets are never confused for one another. Appearance ->
	 * Theme Settings offers the same options without opening the customizer.
	 */
	techjossecom_add_fields(
		$wp_customize,
		'techjossecom_section_hero',
		array(
			'hero_layout'         => array(
				'label'       => __( 'Hero design', 'techjossecom' ),
				'description' => __( 'Choose which design is used at the top of the homepage.', 'techjossecom' ),
				'type'        => 'select',
				'choices'     => techjossecom_hero_layouts(),
				'priority'    => 5,
			),
			'hero1_image'       => array(
				'label'       => __( 'Main banner image', 'techjossecom' ),
				'description' => sprintf(
					/* translators: %s: recommended image size, e.g. "1200 × 480 px". */
					__( 'The large banner on the left. Recommended %s. Shown wider and shorter on a desktop, with the title, subtitle and button over the foot of the picture.', 'techjossecom' ),
					techjossecom_hero_banner_size_label( 'main' )
				),
				'type'        => 'image',
				'priority'    => 10,
			),
			'hero1_title'       => array(
				'label'    => __( 'Main banner title', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 20,
			),
			'hero1_subtitle'    => array(
				'label'    => __( 'Main banner subtitle', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 30,
			),
			'hero1_button_text' => array(
				'label'    => __( 'Main banner button text', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 40,
			),
			'hero1_button_url'  => array(
				'label'       => __( 'Main banner button link', 'techjossecom' ),
				'description' => __( 'Leave empty to link to the shop page.', 'techjossecom' ),
				'type'        => 'url',
				'priority'    => 50,
			),
			'hero2_image'       => array(
				'label'       => __( 'Side banner 1 image', 'techjossecom' ),
				'description' => sprintf(
					/* translators: %s: recommended image size, e.g. "600 × 240 px". */
					__( 'The small banner on the right, top. Recommended %s, the same ratio as the main banner at half the height. The badge and title below sit over its foot on a desktop.', 'techjossecom' ),
					techjossecom_hero_banner_size_label( 'side' )
				),
				'type'        => 'image',
				'priority'    => 60,
			),
			'hero2_badge'       => array(
				'label'    => __( 'Side banner 1 badge', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 70,
			),
			'hero2_title'       => array(
				'label'    => __( 'Side banner 1 title', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 80,
			),
			'hero2_url'         => array(
				'label'    => __( 'Side banner 1 link', 'techjossecom' ),
				'type'     => 'url',
				'priority' => 90,
			),
			'hero3_image'       => array(
				'label'       => __( 'Side banner 2 image', 'techjossecom' ),
				'description' => sprintf(
					/* translators: %s: recommended image size, e.g. "600 × 240 px". */
					__( 'The small banner on the right, bottom. Recommended %s, the same ratio as the main banner at half the height.', 'techjossecom' ),
					techjossecom_hero_banner_size_label( 'side' )
				),
				'type'        => 'image',
				'priority'    => 100,
			),
			'hero3_badge'       => array(
				'label'    => __( 'Side banner 2 badge', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 110,
			),
			'hero3_title'       => array(
				'label'    => __( 'Side banner 2 title', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 120,
			),
			'hero3_url'         => array(
				'label'    => __( 'Side banner 2 link', 'techjossecom' ),
				'type'     => 'url',
				'priority' => 130,
			),
		)
	);
	// ---------------------------------------------------------------------
	// 3b. Hero slider banners (layout 2).
	// ---------------------------------------------------------------------

	$wp_customize->add_section(
		'techjossecom_section_hero_slider',
		array(
			'title'       => __( 'Hero Slider Banners', 'techjossecom' ),
			'description' => __( 'Used when the hero design is set to the image slider. Each banner is an image with an optional link; a slide with no image is skipped.', 'techjossecom' ),
			'priority'    => 31,
		)
	);

	$slider_fields = array(
		'hero_slider_autoplay' => array(
			'label'       => __( 'Slide the banners automatically', 'techjossecom' ),
			'description' => __( 'The banner holds still while the visitor is hovering over it, and respects the "reduce motion" browser setting.', 'techjossecom' ),
			'type'        => 'checkbox',
			'priority'    => 10,
		),
		'hero_slider_speed'    => array(
			'label'   => __( 'Seconds per banner', 'techjossecom' ),
			'type'    => 'number',
			'priority' => 20,
		),
	);

	// The image and link pair for each slide, numbered in the order shown.
	$priority = 30;

	for ( $techjossecom_i = 1; $techjossecom_i <= techjossecom_hero_slide_count(); $techjossecom_i++ ) {
		$slider_fields[ 'hero_slide' . $techjossecom_i . '_image' ] = array(
			/* translators: %d: slide number. */
			'label'       => sprintf( __( 'Banner %d image', 'techjossecom' ), $techjossecom_i ),
			'description' => 1 === $techjossecom_i ? __( 'Recommended 1600 x 300 px.', 'techjossecom' ) : '',
			'type'        => 'image',
			'priority'    => $priority,
		);

		$slider_fields[ 'hero_slide' . $techjossecom_i . '_mobile_image' ] = array(
			/* translators: %d: slide number. */
			'label'       => sprintf( __( 'Banner %d phone image', 'techjossecom' ), $techjossecom_i ),
			'description' => 1 === $techjossecom_i
				? __( 'Optional. Shown instead of the banner above on phones, which saves a phone from downloading the wide desktop file. Recommended 800 x 300 px. Leave empty to use one picture everywhere.', 'techjossecom' )
				: '',
			'type'        => 'image',
			'priority'    => $priority + 1,
		);

		$slider_fields[ 'hero_slide' . $techjossecom_i . '_url' ] = array(
			/* translators: %d: slide number. */
			'label'       => sprintf( __( 'Banner %d link', 'techjossecom' ), $techjossecom_i ),
			'description' => 1 === $techjossecom_i ? __( 'Leave empty to show the image without a link.', 'techjossecom' ) : '',
			'type'        => 'url',
			'priority'    => $priority + 2,
		);

		$priority += 10;
	}

	techjossecom_add_fields( $wp_customize, 'techjossecom_section_hero_slider', $slider_fields );

	// ---------------------------------------------------------------------
	// 4. Homepage sections.
	// ---------------------------------------------------------------------
	techjossecom_add_fields(
		$wp_customize,
		'techjossecom_section_home',
		array(
			'show_front_heading' => array(
				'label'       => __( 'Show the page heading above the hero banner', 'techjossecom' ),
				'description' => __( 'The main H1 line of the homepage, for example "Medical equipment shop in Dhaka — oxygen cylinder, concentrator & hospital bed".', 'techjossecom' ),
				'type'        => 'checkbox',
				'priority'    => 1,
			),
			'front_heading'      => array(
				'label'    => __( 'Page heading text (H1)', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 2,
			),
			'show_intro'       => array(
				'label'    => __( 'Show the intro text block', 'techjossecom' ),
				'type'     => 'checkbox',
				'priority' => 5,
			),
			'intro_title'      => array(
				'label'    => __( 'Intro heading', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 10,
			),
			'intro_text'       => array(
				'label'       => __( 'Intro paragraph', 'techjossecom' ),
				'description' => __( 'A short SEO friendly paragraph with your main keywords. Leave empty to hide it.', 'techjossecom' ),
				'type'        => 'textarea',
				'priority'    => 20,
			),
			'show_categories'  => array(
				'label'    => __( 'Show the "Shop by Category" grid', 'techjossecom' ),
				'type'     => 'checkbox',
				'priority' => 30,
			),
			'categories_title' => array(
				'label'    => __( 'Category section heading', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 40,
			),
			'categories_limit' => array(
				'label'    => __( 'Number of categories', 'techjossecom' ),
				'type'     => 'number',
				'min'      => 2,
				'max'      => 24,
				'priority' => 50,
			),
			'categories_mobile_limit' => array(
				'label'       => __( 'Categories on mobile', 'techjossecom' ),
				'description' => __( 'Leave 0 to use the desktop number. Set a smaller number to show fewer categories on phones.', 'techjossecom' ),
				'type'        => 'number',
				'min'         => 0,
				'max'         => 24,
				'priority'    => 55,
			),
			'show_deals'       => array(
				'label'    => __( 'Show the "Best Deals" grid', 'techjossecom' ),
				'type'     => 'checkbox',
				'priority' => 60,
			),
			'deals_title'      => array(
				'label'    => __( 'Deals section heading', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 70,
			),
			'deals_limit'      => array(
				'label'    => __( 'Number of deal products', 'techjossecom' ),
				'type'     => 'number',
				'min'      => 2,
				'max'      => 16,
				'priority' => 80,
			),
			'deals_mobile_limit' => array(
				'label'       => __( 'Deal products on mobile', 'techjossecom' ),
				'description' => __( 'Leave 0 to use the desktop number. Set a smaller number to show fewer deals on phones.', 'techjossecom' ),
				'type'        => 'number',
				'min'         => 0,
				'max'         => 16,
				'priority'    => 85,
			),
			'show_latest'      => array(
				'label'    => __( 'Show the "New Arrivals" grid', 'techjossecom' ),
				'type'     => 'checkbox',
				'priority' => 86,
			),
			'latest_title'     => array(
				'label'    => __( 'New Arrivals section heading', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 87,
			),
			'latest_limit'     => array(
				'label'    => __( 'Number of new arrival products', 'techjossecom' ),
				'type'     => 'number',
				'min'      => 2,
				'max'      => 16,
				'priority' => 88,
			),
			'latest_mobile_limit' => array(
				'label'       => __( 'New arrival products on mobile', 'techjossecom' ),
				'description' => __( 'Leave 0 to use the desktop number. Set a smaller number to show fewer new arrivals on phones.', 'techjossecom' ),
				'type'        => 'number',
				'min'         => 0,
				'max'         => 16,
				'priority'    => 89,
			),
			'show_features'    => array(
				'label'       => __( 'Show the service highlight bar', 'techjossecom' ),
				'description' => __( 'The four-item bar with icons, usually placed under the hero banner.', 'techjossecom' ),
				'type'        => 'checkbox',
				'priority'    => 90,
			),
			'feature1_title'   => array(
				'label'    => __( 'Highlight 1 title', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 100,
			),
			'feature1_text'    => array(
				'label'    => __( 'Highlight 1 text', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 110,
			),
			'feature2_title'   => array(
				'label'    => __( 'Highlight 2 title', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 120,
			),
			'feature2_text'    => array(
				'label'    => __( 'Highlight 2 text', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 130,
			),
			'feature3_title'   => array(
				'label'    => __( 'Highlight 3 title', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 140,
			),
			'feature3_text'    => array(
				'label'    => __( 'Highlight 3 text', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 150,
			),
			'feature4_title'   => array(
				'label'    => __( 'Highlight 4 title', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 160,
			),
			'feature4_text'    => array(
				'label'    => __( 'Highlight 4 text', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 170,
			),
		)
	);

	// ---------------------------------------------------------------------
	// 5. Cash on Delivery popup.
	// ---------------------------------------------------------------------
	techjossecom_add_fields(
		$wp_customize,
		'techjossecom_section_cod',
		array(
			'enable_cod'          => array(
				'label'       => __( 'Enable the COD "Order Now" popup', 'techjossecom' ),
				'description' => __( 'Requires the Cash on delivery payment method to be enabled in WooCommerce → Settings → Payments.', 'techjossecom' ),
				'type'        => 'checkbox',
				'priority'    => 5,
			),
			'cod_button_text'     => array(
				'label'    => __( 'Button label', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 10,
			),
			'cod_modal_title'     => array(
				'label'    => __( 'Popup heading', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 20,
			),
			'cod_success_message' => array(
				'label'    => __( 'Message shown after the order is placed', 'techjossecom' ),
				'type'     => 'textarea',
				'priority' => 30,
			),
		)
	);

	// ---------------------------------------------------------------------
	// 6. Product page.
	// ---------------------------------------------------------------------
	techjossecom_add_fields(
		$wp_customize,
		'techjossecom_section_product',
		array(
			'show_single_share' => array(
				'label'    => __( 'Show social share buttons on product pages', 'techjossecom' ),
				'type'     => 'checkbox',
				'priority' => 10,
			),
		)
	);
	// ---------------------------------------------------------------------
	// 7. Footer.
	// ---------------------------------------------------------------------
	techjossecom_add_fields(
		$wp_customize,
		'techjossecom_section_footer',
		array(
			'footer_about'      => array(
				'label'    => __( 'About text (first column)', 'techjossecom' ),
				'type'     => 'textarea',
				'priority' => 10,
			),
			'contact_address'   => array(
				'label'    => __( 'Address', 'techjossecom' ),
				'type'     => 'textarea',
				'priority' => 20,
			),
			'contact_email'     => array(
				'label'    => __( 'Email address', 'techjossecom' ),
				'type'     => 'email',
				'priority' => 30,
			),
			'footer_payments'   => array(
				'label'    => __( 'Payment note', 'techjossecom' ),
				'type'     => 'text',
				'priority' => 40,
			),
			'social_facebook'   => array(
				'label'    => __( 'Facebook URL', 'techjossecom' ),
				'type'     => 'url',
				'priority' => 50,
			),
			'social_youtube'    => array(
				'label'    => __( 'YouTube URL', 'techjossecom' ),
				'type'     => 'url',
				'priority' => 60,
			),
			'social_x'          => array(
				'label'    => __( 'X (Twitter) URL', 'techjossecom' ),
				'type'     => 'url',
				'priority' => 70,
			),
			'social_linkedin'   => array(
				'label'    => __( 'LinkedIn URL', 'techjossecom' ),
				'type'     => 'url',
				'priority' => 80,
			),
			'social_instagram'  => array(
				'label'    => __( 'Instagram URL', 'techjossecom' ),
				'type'     => 'url',
				'priority' => 90,
			),
			'social_pinterest'  => array(
				'label'    => __( 'Pinterest URL', 'techjossecom' ),
				'type'     => 'url',
				'priority' => 100,
			),
			'footer_copyright'  => array(
				'label'       => __( 'Copyright line', 'techjossecom' ),
				'description' => __( 'Leave empty to use the site name and the current year.', 'techjossecom' ),
				'type'        => 'text',
				'priority'    => 110,
			),
		)
	);

	// ---------------------------------------------------------------------
	// 8. Colours.
	// ---------------------------------------------------------------------
	techjossecom_add_fields(
		$wp_customize,
		'techjossecom_section_colors',
		array(
			'primary_color' => array(
				'label'    => __( 'Primary colour', 'techjossecom' ),
				'type'     => 'color',
				'priority' => 10,
			),
			'accent_color'  => array(
				'label'    => __( 'Accent / price colour', 'techjossecom' ),
				'type'     => 'color',
				'priority' => 20,
			),
			'dark_color'    => array(
				'label'    => __( 'Dark colour (header & footer)', 'techjossecom' ),
				'type'     => 'color',
				'priority' => 30,
			),
			'secondary_color' => array(
				'label'       => __( 'Secondary colour', 'techjossecom' ),
				'description' => __( 'Used for the "All Categories" menu button and the active product tab.', 'techjossecom' ),
				'type'        => 'color',
				'priority'    => 40,
			),
		)
	);
}
add_action( 'customize_register', 'techjossecom_customize_register' );

/**
 * Convert a hex colour to an array of RGB values.
 *
 * @param string $hex Hex colour, with or without the leading hash.
 * @return array|false Array with r, g, b keys or false when invalid.
 */
function techjossecom_hex_to_rgb( $hex ) {
	$hex = ltrim( (string) $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return false;
	}

	return array(
		'r' => hexdec( substr( $hex, 0, 2 ) ),
		'g' => hexdec( substr( $hex, 2, 2 ) ),
		'b' => hexdec( substr( $hex, 4, 2 ) ),
	);
}

/**
 * Lighten or darken a hex colour.
 *
 * @param string $hex     Hex colour.
 * @param int    $percent Positive to lighten, negative to darken.
 * @return string Hex colour, or the input when it cannot be parsed.
 */
function techjossecom_shade_hex( $hex, $percent ) {
	$rgb = techjossecom_hex_to_rgb( $hex );

	if ( ! $rgb ) {
		return $hex;
	}

	$factor = ( 100 + (int) $percent ) / 100;
	$shade  = array();

	foreach ( $rgb as $channel ) {
		$shade[] = max( 0, min( 255, (int) round( $channel * $factor ) ) );
	}

	return sprintf( '#%02x%02x%02x', $shade[0], $shade[1], $shade[2] );
}

/**
 * Print the customizer colours as CSS variables.
 *
 * Attached to the main stylesheet so the browser can keep caching the file.
 * The hover / tint colours are derived from the two main colours.
 *
 * @return void
 */
function techjossecom_customizer_css() {
	$primary = techjossecom_mod( 'primary_color' );
	$accent  = techjossecom_mod( 'accent_color' );
	$dark    = techjossecom_mod( 'dark_color' );
	$secondary = techjossecom_mod( 'secondary_color' );

	$primary = $primary ? $primary : '#0a7d4f';
	$accent  = $accent ? $accent : '#ff6b00';
	$dark    = $dark ? $dark : '#12251c';
	$secondary = $secondary ? $secondary : '#1c4fd8';

	$primary_rgb = techjossecom_hex_to_rgb( $primary );
	$primary_rgb = $primary_rgb ? $primary_rgb : array( 'r' => 10, 'g' => 125, 'b' => 79 );

	$css = sprintf(
		':root{--tj-primary:%1$s;--tj-primary-dark:%2$s;--tj-primary-soft:rgba(%3$d,%4$d,%5$d,.08);--tj-primary-ring:rgba(%3$d,%4$d,%5$d,.18);--tj-accent:%6$s;--tj-accent-dark:%7$s;--tj-dark:%8$s;--tj-secondary:%9$s;--tj-secondary-dark:%10$s;}',
		$primary,
		techjossecom_shade_hex( $primary, -25 ),
		$primary_rgb['r'],
		$primary_rgb['g'],
		$primary_rgb['b'],
		$accent,
		techjossecom_shade_hex( $accent, -25 ),
		$dark,
		$secondary,
		techjossecom_shade_hex( $secondary, -25 )
	);

	wp_add_inline_style( 'techjossecom-main', $css );
}
add_action( 'wp_enqueue_scripts', 'techjossecom_customizer_css', 20 );

/**
 * The theme options that are exposed to the master JSON importer.
 *
 * Returns the same structure the customizer uses: section id => field list.
 *
 * @return array
 */
function techjossecom_customizer_schema() {
	return array(
		'topbar'  => array( 'show_topbar', 'topbar_text', 'hotline_label', 'hotline_number', 'whatsapp_number', 'messenger_username' ),
		'header'  => array( 'search_placeholder', 'show_live_search' ),
		'hero'    => array( 'hero_layout', 'hero1_image', 'hero1_title', 'hero1_subtitle', 'hero1_button_text', 'hero1_button_url', 'hero2_image', 'hero2_badge', 'hero2_title', 'hero2_url', 'hero3_image', 'hero3_badge', 'hero3_title', 'hero3_url' ),
		'hero_slider' => array_merge(
			array( 'hero_slider_autoplay', 'hero_slider_speed' ),
			techjossecom_hero_slider_keys()
		),
		'home'    => array( 'show_front_heading', 'front_heading', 'show_intro', 'intro_title', 'intro_text', 'show_categories', 'categories_title', 'categories_limit', 'categories_mobile_limit', 'show_deals', 'deals_title', 'deals_limit', 'deals_mobile_limit', 'show_latest', 'latest_title', 'latest_limit', 'latest_mobile_limit', 'show_features', 'feature1_title', 'feature1_text', 'feature2_title', 'feature2_text', 'feature3_title', 'feature3_text', 'feature4_title', 'feature4_text' ),
		'cod'     => array( 'enable_cod', 'cod_button_text', 'cod_modal_title', 'cod_success_message' ),
		'product' => array( 'show_single_share' ),
		'footer'  => array( 'footer_about', 'contact_address', 'contact_email', 'footer_payments', 'social_facebook', 'social_youtube', 'social_x', 'social_linkedin', 'social_instagram', 'social_pinterest', 'footer_copyright' ),
		'colors'  => array( 'primary_color', 'accent_color', 'dark_color', 'secondary_color' ),
	);
}



