<?php
/**
 * Master content importer.
 *
 * Adds "Appearance → Master Content", where a single JSON file (master-form.json)
 * can describe the whole site: theme options, pages, menus, product categories
 * and products. Designed so an AI agent can configure a fresh install in one go.
 *
 * @package TechJosse_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the admin page.
 *
 * @return void
 */
function techjossecom_master_menu() {
	add_theme_page(
		__( 'Master Content', 'techjossecom' ),
		__( 'Master Content', 'techjossecom' ),
		'manage_options',
		'techjossecom-master',
		'techjossecom_master_page'
	);
}
add_action( 'admin_menu', 'techjossecom_master_menu' );

/**
 * Path of the bundled master JSON file.
 *
 * @return string
 */
function techjossecom_master_file() {
	return TECHJOSSECOM_DIR . '/master-form.json';
}

/**
 * Read the bundled master JSON file.
 *
 * @return string Raw JSON, or an empty string when the file is missing.
 */
function techjossecom_master_file_contents() {
	$file = techjossecom_master_file();

	if ( ! file_exists( $file ) ) {
		return '';
	}

	return (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

/**
 * Export the current theme options in the master JSON shape.
 *
 * @return string Pretty printed JSON.
 */
function techjossecom_master_export() {
	$settings = array();

	foreach ( techjossecom_customizer_schema() as $keys ) {
		foreach ( $keys as $key ) {
			$settings[ $key ] = techjossecom_mod( $key );
		}
	}

	$payload = array(
		'$schema'  => 'techjossecom/master-form/1.0',
		'settings' => $settings,
	);

	return wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
}

/**
 * Render the master content page.
 *
 * @return void
 */
function techjossecom_master_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$messages = array();
	$errors   = array();
	$json     = techjossecom_master_file_contents();

	if ( isset( $_POST['tj_master_action'] ) ) {
		check_admin_referer( 'techjossecom_master' );

		$action  = sanitize_text_field( wp_unslash( $_POST['tj_master_action'] ) );
		$raw     = isset( $_POST['tj_master_json'] ) ? wp_unslash( $_POST['tj_master_json'] ) : '';
		$json    = $raw;
		$context = isset( $_POST['tj_master_context'] ) ? sanitize_text_field( wp_unslash( $_POST['tj_master_context'] ) ) : 'site';

		if ( 'apply' === $action ) {
			$result   = techjossecom_master_apply( $raw, $context );
			$messages = $result['messages'];
			$errors   = $result['errors'];
		}
	}

	if ( ! $json ) {
		$json = techjossecom_master_export();
	}

	echo '<div class="wrap tj-master-wrap">';
	echo '<h1>' . esc_html__( 'Master Content', 'techjossecom' ) . '</h1>';
	echo '<p class="description">' . esc_html__( 'Paste (or edit) the master JSON and press Apply to configure the whole site in one step: theme options, pages, menus, product categories and products.', 'techjossecom' ) . '</p>';

	foreach ( $messages as $message ) {
		echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>';
	}

	foreach ( $errors as $error ) {
		echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
	}

	echo '<form method="post">';
	wp_nonce_field( 'techjossecom_master' );

	echo '<textarea name="tj_master_json" class="tj-master-json" rows="24" spellcheck="false">' . esc_textarea( $json ) . '</textarea>';

	echo '<p class="tj-master-actions">';
	echo '<select name="tj_master_context">';
	printf( '<option value="site">%s</option>', esc_html__( 'Import everything', 'techjossecom' ) );
	printf( '<option value="settings">%s</option>', esc_html__( 'Theme options only', 'techjossecom' ) );
	printf( '<option value="content">%s</option>', esc_html__( 'Content only (pages, menus, products)', 'techjossecom' ) );
	echo '</select> ';

	echo '<button type="submit" name="tj_master_action" value="apply" class="button button-primary">' . esc_html__( 'Apply master content', 'techjossecom' ) . '</button> ';
	echo '<button type="submit" name="tj_master_action" value="reload" class="button">' . esc_html__( 'Reload bundled file', 'techjossecom' ) . '</button>';
	echo '</p>';

	echo '</form>';

	echo '<p class="description">' . esc_html__( 'Tip: press "Export current settings" to copy the JSON already stored in your database.', 'techjossecom' ) . '</p>';

	echo '<details class="tj-master-export"><summary>' . esc_html__( 'Export current settings', 'techjossecom' ) . '</summary>';
	echo '<textarea class="tj-master-json" rows="16" spellcheck="false" readonly onclick="this.select()">' . esc_textarea( techjossecom_master_export() ) . '</textarea>';
	echo '</details>';

	echo '</div>';
}

/**
 * Minimal styling for the admin screen.
 *
 * @return void
 */
function techjossecom_master_admin_css() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'appearance_page_techjossecom-master' !== $screen->id ) {
		return;
	}

	echo '<style>.tj-master-json{width:100%;font-family:Consolas,Monaco,monospace;font-size:12px;line-height:1.5}.tj-master-actions{margin:12px 0}</style>';
}
add_action( 'admin_head', 'techjossecom_master_admin_css' );
/**
 * Apply a master JSON payload.
 *
 * @param string $raw     Raw JSON.
 * @param string $context Either 'site', 'settings' or 'content'.
 * @return array Array with 'messages' and 'errors' keys.
 */
function techjossecom_master_apply( $raw, $context = 'site' ) {
	$messages = array();
	$errors   = array();

	$raw = trim( (string) $raw );

	if ( '' === $raw ) {
		$errors[] = __( 'No JSON was supplied.', 'techjossecom' );

		return array(
			'messages' => $messages,
			'errors'   => $errors,
		);
	}

	$data = json_decode( $raw, true );

	if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
		/* translators: %s: JSON error message. */
		$errors[] = sprintf( __( 'The JSON could not be parsed: %s', 'techjossecom' ), json_last_error_msg() );

		return array(
			'messages' => $messages,
			'errors'   => $errors,
		);
	}

	if ( in_array( $context, array( 'site', 'settings' ), true ) && ! empty( $data['settings'] ) && is_array( $data['settings'] ) ) {
		$settings_result = techjossecom_master_apply_settings( $data['settings'] );
		$messages        = array_merge( $messages, $settings_result['messages'] );
		$errors          = array_merge( $errors, $settings_result['errors'] );
	}

	if ( in_array( $context, array( 'site', 'content' ), true ) && ! empty( $data['content'] ) && is_array( $data['content'] ) ) {
		$content_result = techjossecom_master_apply_content( $data['content'] );
		$messages       = array_merge( $messages, $content_result['messages'] );
		$errors         = array_merge( $errors, $content_result['errors'] );
	}

	if ( ! $messages && ! $errors ) {
		$messages[] = __( 'Nothing to import: the JSON contains no settings or content.', 'techjossecom' );
	}

	return array(
		'messages' => $messages,
		'errors'   => $errors,
	);
}

/**
 * Guess the field type from its key, so values can be sanitized correctly.
 *
 * @param string $key Setting name without the theme prefix.
 * @return string One of: checkbox, number, image, url, email, color, textarea, text.
 */
function techjossecom_master_field_type( $key ) {
	if ( 0 === strpos( $key, 'show_' ) || 0 === strpos( $key, 'enable_' ) ) {
		return 'checkbox';
	}

	if ( '_limit' === substr( $key, -6 ) ) {
		return 'number';
	}

	if ( '_image' === substr( $key, -6 ) ) {
		return 'image';
	}

	if ( 0 === strpos( $key, 'social_' ) || '_url' === substr( $key, -4 ) ) {
		return 'url';
	}

	if ( in_array( $key, array( 'contact_email' ), true ) ) {
		return 'email';
	}

	if ( in_array( $key, array( 'primary_color', 'accent_color', 'dark_color', 'secondary_color' ), true ) ) {
		return 'color';
	}

	if ( in_array( $key, array( 'footer_about', 'intro_text', 'cod_success_message', 'contact_address' ), true ) ) {
		return 'textarea';
	}

	return 'text';
}

/**
 * Import the "settings" object into theme mods.
 *
 * @param array $settings Setting name => raw value.
 * @return array Array with 'messages' and 'errors' keys.
 */
function techjossecom_master_apply_settings( $settings ) {
	$messages = array();
	$errors   = array();
	$defaults = techjossecom_defaults();
	$updated  = 0;

	foreach ( $settings as $key => $value ) {
		$key = sanitize_key( $key );

		if ( ! array_key_exists( $key, $defaults ) ) {
			/* translators: %s: setting name. */
			$errors[] = sprintf( __( 'Skipped unknown setting "%s".', 'techjossecom' ), $key );
			continue;
		}

		$type = techjossecom_master_field_type( $key );
		$mods = techjossecom_master_sanitize_value( $key, $value, $type, $errors );

		if ( null === $mods ) {
			continue;
		}

		set_theme_mod( 'techjossecom_' . $key, $mods );
		$updated++;
	}

	if ( $updated ) {
		/* translators: %d: number of settings. */
		$messages[] = sprintf( __( '%d theme option(s) updated.', 'techjossecom' ), $updated );
	}

	return array(
		'messages' => $messages,
		'errors'   => $errors,
	);
}

/**
 * Sanitize a single master JSON value.
 *
 * Image fields accept either an attachment ID or a remote URL that is
 * downloaded into the media library.
 *
 * @param string $key    Setting name.
 * @param mixed  $value  Raw value.
 * @param string $type   Field type.
 * @param array  $errors Error collector passed by reference.
 * @return mixed|null Sanitized value, or null to skip the field.
 */
function techjossecom_master_sanitize_value( $key, $value, $type, &$errors ) {
	switch ( $type ) {
		case 'checkbox':
			return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );

		case 'number':
			return absint( $value );

		case 'url':
			return esc_url_raw( (string) $value );

		case 'email':
			return sanitize_email( (string) $value );

		case 'color':
			$color = sanitize_hex_color( (string) $value );

			if ( ! $color ) {
				/* translators: %s: setting name. */
				$errors[] = sprintf( __( 'Ignoring invalid colour for "%s".', 'techjossecom' ), $key );

				return null;
			}

			return $color;

		case 'textarea':
			return sanitize_textarea_field( (string) $value );

		case 'image':
			return techjossecom_master_import_image( $value, $key, $errors );

		default:
			return sanitize_text_field( (string) $value );
	}
}
/**
 * Import an image setting: either an attachment ID or a remote URL.
 *
 * @param mixed  $value  Raw value.
 * @param string $key    Setting name, used as the attachment title.
 * @param array  $errors Error collector passed by reference.
 * @return int Attachment ID, or 0 on failure.
 */
function techjossecom_master_import_image( $value, $key, &$errors ) {
	if ( is_numeric( $value ) ) {
		$attachment_id = absint( $value );

		return ( $attachment_id && wp_get_attachment_url( $attachment_id ) ) ? $attachment_id : 0;
	}

	$url = esc_url_raw( (string) $value );

	if ( '' === $url ) {
		return 0;
	}

	$existing = attachment_url_to_postid( $url );

	if ( $existing ) {
		return (int) $existing;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$attachment_id = media_sideload_image( $url, 0, $key, 'id' );

	if ( is_wp_error( $attachment_id ) ) {
		/* translators: 1: setting name, 2: error message. */
		$errors[] = sprintf( __( 'Could not download the image for "%1$s": %2$s', 'techjossecom' ), $key, $attachment_id->get_error_message() );

		return 0;
	}

	return (int) $attachment_id;
}

/**
 * Import the "content" object: categories, pages, menus and products.
 *
 * @param array $content Content definition.
 * @return array Array with 'messages' and 'errors' keys.
 */
function techjossecom_master_apply_content( $content ) {
	$messages = array();
	$errors   = array();

	if ( ! empty( $content['categories'] ) && is_array( $content['categories'] ) ) {
		$count = techjossecom_master_import_categories( $content['categories'], $errors );

		if ( $count ) {
			/* translators: %d: number of product categories. */
			$messages[] = sprintf( __( '%d product category(ies) created or updated.', 'techjossecom' ), $count );
		}
	}

	if ( ! empty( $content['pages'] ) && is_array( $content['pages'] ) ) {
		$page_ids = array();
		$count    = 0;

		foreach ( $content['pages'] as $page ) {
			if ( ! is_array( $page ) || empty( $page['slug'] ) ) {
				$errors[] = __( 'Skipped a page without a slug.', 'techjossecom' );
				continue;
			}

			$page_id = techjossecom_master_import_page( $page, $errors );

			if ( $page_id ) {
				$count++;
				$page_ids[ $page['slug'] ] = $page_id;

				if ( ! empty( $page['front_page'] ) ) {
					update_option( 'show_on_front', 'page' );
					update_option( 'page_on_front', $page_id );
				}

				if ( ! empty( $page['posts_page'] ) ) {
					update_option( 'show_on_front', 'page' );
					update_option( 'page_for_posts', $page_id );
				}
			}
		}

		if ( $count ) {
			/* translators: %d: number of pages. */
			$messages[] = sprintf( __( '%d page(s) created or updated.', 'techjossecom' ), $count );
		}

		$content['page_ids'] = $page_ids;
	}

	if ( ! empty( $content['menu'] ) && is_array( $content['menu'] ) ) {
		$menu_result = techjossecom_master_import_menu( $content['menu'] );

		$messages = array_merge( $messages, $menu_result['messages'] );
		$errors   = array_merge( $errors, $menu_result['errors'] );
	}

	if ( ! empty( $content['products'] ) && is_array( $content['products'] ) ) {
		if ( ! class_exists( 'WC_Product_Simple' ) ) {
			$errors[] = __( 'WooCommerce is not active, so products were skipped.', 'techjossecom' );
		} else {
			$count = 0;

			foreach ( $content['products'] as $product_data ) {
				$product_id = techjossecom_master_import_product( $product_data, $errors );

				if ( $product_id ) {
					$count++;
				}
			}

			if ( $count ) {
				/* translators: %d: number of products. */
				$messages[] = sprintf( __( '%d product(s) created or updated.', 'techjossecom' ), $count );
			}
		}
	}

	return array(
		'messages' => $messages,
		'errors'   => $errors,
	);
}

/**
 * Create or update a page.
 *
 * @param array $page   Page definition.
 * @param array $errors Error collector passed by reference.
 * @return int Page ID, or 0 on failure.
 */
function techjossecom_master_import_page( $page, &$errors ) {
	$slug    = sanitize_title( $page['slug'] );
	$existing = get_page_by_path( $slug );

	$args = array(
		'post_type'    => 'page',
		'post_title'   => isset( $page['title'] ) ? sanitize_text_field( $page['title'] ) : ucwords( str_replace( '-', ' ', $slug ) ),
		'post_name'    => $slug,
		'post_content' => isset( $page['content'] ) ? wp_kses_post( $page['content'] ) : '',
		'post_status'  => isset( $page['status'] ) ? sanitize_key( $page['status'] ) : 'publish',
		'meta_input'   => array(),
	);

	if ( $existing instanceof WP_Post ) {
		$args['ID'] = $existing->ID;
	}

	if ( ! empty( $page['parent'] ) ) {
		$parent = get_page_by_path( sanitize_title( $page['parent'] ) );

		if ( $parent instanceof WP_Post ) {
			$args['post_parent'] = $parent->ID;
		}
	}

	if ( ! empty( $page['template'] ) ) {
		$args['meta_input']['_wp_page_template'] = sanitize_file_name( $page['template'] );
	}

	$page_id = wp_insert_post( $args, true );

	if ( is_wp_error( $page_id ) ) {
		/* translators: 1: page slug, 2: error message. */
		$errors[] = sprintf( __( 'Page "%1$s" failed: %2$s', 'techjossecom' ), $slug, $page_id->get_error_message() );

		return 0;
	}

	return (int) $page_id;
}
/**
 * Create or update a navigation menu and assign it to theme locations.
 *
 * @param array $menu Menu definition, or a list of menu definitions.
 * @return array Array with 'messages' and 'errors' keys.
 */
function techjossecom_master_import_menu( $menu ) {
	$messages = array();
	$errors   = array();

	// Allow either a single menu object or a list of menus.
	$menus = isset( $menu['name'] ) || isset( $menu['items'] ) ? array( $menu ) : $menu;

	foreach ( $menus as $menu_data ) {
		if ( ! is_array( $menu_data ) ) {
			continue;
		}

		$name    = ! empty( $menu_data['name'] ) ? sanitize_text_field( $menu_data['name'] ) : __( 'Main Menu', 'techjossecom' );
		$current = wp_get_nav_menu_object( $name );
		$menu_id = $current ? (int) $current->term_id : wp_create_nav_menu( $name );

		if ( is_wp_error( $menu_id ) ) {
			/* translators: 1: menu name, 2: error message. */
			$errors[] = sprintf( __( 'Menu "%1$s" failed: %2$s', 'techjossecom' ), $name, $menu_id->get_error_message() );
			continue;
		}

		// Track what is already there so the import can be run repeatedly.
		$seen  = array();
		$items = wp_get_nav_menu_items( $menu_id );

		if ( $items ) {
			foreach ( $items as $item ) {
				$seen[ techjossecom_master_menu_item_key( $item->type, $item->object_id, $item->url ) ] = true;
			}
		}

		$added = 0;

		if ( ! empty( $menu_data['items'] ) && is_array( $menu_data['items'] ) ) {
			foreach ( $menu_data['items'] as $item ) {
				$args = array(
					'menu-item-status' => 'publish',
				);

				if ( ! empty( $item['page'] ) ) {
					$page = get_page_by_path( sanitize_title( $item['page'] ) );

					if ( ! $page instanceof WP_Post ) {
						/* translators: %s: page slug. */
						$errors[] = sprintf( __( 'Menu item skipped, page "%s" does not exist.', 'techjossecom' ), $item['page'] );
						continue;
					}

					$key = techjossecom_master_menu_item_key( 'post_type', $page->ID, '' );

					$args['menu-item-type']      = 'post_type';
					$args['menu-item-object']    = 'page';
					$args['menu-item-object-id'] = $page->ID;

					if ( empty( $item['title'] ) ) {
						$args['menu-item-title'] = get_the_title( $page );
					}
				} elseif ( ! empty( $item['product_cat'] ) ) {
					$term = get_term_by( 'slug', sanitize_title( $item['product_cat'] ), 'product_cat' );

					if ( ! $term || is_wp_error( $term ) ) {
						/* translators: %s: category slug. */
						$errors[] = sprintf( __( 'Menu item skipped, product category "%s" does not exist.', 'techjossecom' ), $item['product_cat'] );
						continue;
					}

					$key = techjossecom_master_menu_item_key( 'taxonomy', $term->term_id, '' );

					$args['menu-item-type']      = 'taxonomy';
					$args['menu-item-object']    = 'product_cat';
					$args['menu-item-object-id'] = $term->term_id;

					if ( empty( $item['title'] ) ) {
						$args['menu-item-title'] = $term->name;
					}
				} elseif ( ! empty( $item['url'] ) ) {
					$url = esc_url_raw( $item['url'] );
					$key = techjossecom_master_menu_item_key( 'custom', 0, $url );

					$args['menu-item-type'] = 'custom';
					$args['menu-item-url']  = $url;

					if ( empty( $item['title'] ) ) {
						$args['menu-item-title'] = $url;
					}
				} else {
					continue;
				}

				if ( isset( $seen[ $key ] ) ) {
					continue;
				}

				if ( ! empty( $item['title'] ) ) {
					$args['menu-item-title'] = sanitize_text_field( $item['title'] );
				}

				$result = wp_update_nav_menu_item( $menu_id, 0, $args );

				if ( is_wp_error( $result ) ) {
					$errors[] = $result->get_error_message();
					continue;
				}

				$seen[ $key ] = true;
				$added++;
			}
		}

		if ( ! empty( $menu_data['locations'] ) && is_array( $menu_data['locations'] ) ) {
			$locations = get_theme_mod( 'nav_menu_locations' );
			$locations = is_array( $locations ) ? $locations : array();

			foreach ( $menu_data['locations'] as $location ) {
				$locations[ sanitize_key( $location ) ] = $menu_id;
			}

			set_theme_mod( 'nav_menu_locations', $locations );
		}

		/* translators: 1: menu name, 2: number of items. */
		$messages[] = sprintf( __( 'Menu "%1$s" ready with %2$d new item(s).', 'techjossecom' ), $name, $added );
	}

	return array(
		'messages' => $messages,
		'errors'   => $errors,
	);
}

/**
 * Build a stable key for a menu item so duplicates can be detected.
 *
 * @param string $type      Menu item type.
 * @param int    $object_id Object ID.
 * @param string $url       Custom URL.
 * @return string
 */
function techjossecom_master_menu_item_key( $type, $object_id, $url ) {
	if ( 'custom' === $type ) {
		return 'custom:' . untrailingslashit( (string) $url );
	}

	return $type . ':' . (int) $object_id;
}
/**
 * Create or update product categories. Parents must be listed before children.
 *
 * @param array $categories Category definitions.
 * @param array $errors     Error collector passed by reference.
 * @return int Number of categories processed.
 */
function techjossecom_master_import_categories( $categories, &$errors ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		$errors[] = __( 'WooCommerce is not active, so product categories were skipped.', 'techjossecom' );

		return 0;
	}

	$count = 0;

	foreach ( $categories as $category ) {
		if ( is_string( $category ) ) {
			$category = array( 'name' => $category );
		}

		if ( ! is_array( $category ) || empty( $category['name'] ) ) {
			continue;
		}

		$name = sanitize_text_field( $category['name'] );
		$slug = ! empty( $category['slug'] ) ? sanitize_title( $category['slug'] ) : sanitize_title( $name );

		$parent_id = 0;

		if ( ! empty( $category['parent'] ) ) {
			$parent = get_term_by( 'slug', sanitize_title( $category['parent'] ), 'product_cat' );

			if ( $parent && ! is_wp_error( $parent ) ) {
				$parent_id = (int) $parent->term_id;
			}
		}

		$args = array(
			'slug'        => $slug,
			'description' => isset( $category['description'] ) ? sanitize_textarea_field( $category['description'] ) : '',
			'parent'      => $parent_id,
		);

		$existing = get_term_by( 'slug', $slug, 'product_cat' );

		if ( $existing && ! is_wp_error( $existing ) ) {
			$result = wp_update_term( $existing->term_id, 'product_cat', $args );
		} else {
			$args['name'] = $name;
			$result       = wp_insert_term( $name, 'product_cat', $args );
		}

		if ( is_wp_error( $result ) ) {
			/* translators: 1: category name, 2: error message. */
			$errors[] = sprintf( __( 'Category "%1$s" failed: %2$s', 'techjossecom' ), $name, $result->get_error_message() );
			continue;
		}

		$term_id = isset( $result['term_id'] ) ? (int) $result['term_id'] : 0;

		if ( $term_id && ! empty( $category['image'] ) ) {
			$image_id = techjossecom_master_import_image( $category['image'], 'product-cat-image', $errors );

			if ( $image_id ) {
				update_term_meta( $term_id, 'thumbnail_id', $image_id );
			}
		}

		$count++;
	}

	return $count;
}

/**
 * Create or update a simple product.
 *
 * @param array $data   Product definition.
 * @param array $errors Error collector passed by reference.
 * @return int Product ID, or 0 on failure.
 */
function techjossecom_master_import_product( $data, &$errors ) {
	if ( ! is_array( $data ) || empty( $data['name'] ) ) {
		$errors[] = __( 'Skipped a product without a name.', 'techjossecom' );

		return 0;
	}

	$sku        = ! empty( $data['sku'] ) ? wc_clean( $data['sku'] ) : '';
	$product_id = $sku ? wc_get_product_id_by_sku( $sku ) : 0;
	$product    = $product_id ? wc_get_product( $product_id ) : new WC_Product_Simple();

	if ( ! $product ) {
		$product = new WC_Product_Simple();
	}

	$product->set_name( sanitize_text_field( $data['name'] ) );
	$product->set_status( ! empty( $data['status'] ) ? sanitize_key( $data['status'] ) : 'publish' );
	$product->set_catalog_visibility( 'visible' );

	if ( $sku ) {
		$product->set_sku( $sku );
	}

	if ( isset( $data['regular_price'] ) ) {
		$product->set_regular_price( wc_format_decimal( $data['regular_price'] ) );
	}

	if ( isset( $data['sale_price'] ) && '' !== $data['sale_price'] ) {
		$product->set_sale_price( wc_format_decimal( $data['sale_price'] ) );
	}

	if ( isset( $data['description'] ) ) {
		$product->set_description( wp_kses_post( $data['description'] ) );
	}

	if ( isset( $data['short_description'] ) ) {
		$product->set_short_description( wp_kses_post( $data['short_description'] ) );
	}

	if ( ! empty( $data['stock_status'] ) ) {
		$product->set_stock_status( 'outofstock' === $data['stock_status'] ? 'outofstock' : 'instock' );
	}

	if ( isset( $data['stock_quantity'] ) && '' !== $data['stock_quantity'] ) {
		$product->set_manage_stock( true );
		$product->set_stock_quantity( absint( $data['stock_quantity'] ) );
	}

	if ( ! empty( $data['featured'] ) ) {
		$product->set_featured( true );
	}

	if ( ! empty( $data['categories'] ) ) {
		$term_ids = array();

		foreach ( (array) $data['categories'] as $category ) {
			$term = get_term_by( 'slug', sanitize_title( $category ), 'product_cat' );

			if ( ! $term || is_wp_error( $term ) ) {
				$term = get_term_by( 'name', sanitize_text_field( $category ), 'product_cat' );
			}

			if ( $term && ! is_wp_error( $term ) ) {
				$term_ids[] = (int) $term->term_id;
			}
		}

		if ( $term_ids ) {
			$product->set_category_ids( $term_ids );
		}
	}

	if ( ! empty( $data['image'] ) ) {
		$image_id = techjossecom_master_import_image( $data['image'], 'product-image', $errors );

		if ( $image_id ) {
			$product->set_image_id( $image_id );
		}
	}

	if ( ! empty( $data['gallery'] ) && is_array( $data['gallery'] ) ) {
		$gallery_ids = array();

		foreach ( $data['gallery'] as $gallery_image ) {
			$gallery_id = techjossecom_master_import_image( $gallery_image, 'product-gallery', $errors );

			if ( $gallery_id ) {
				$gallery_ids[] = $gallery_id;
			}
		}

		if ( $gallery_ids ) {
			$product->set_gallery_image_ids( $gallery_ids );
		}
	}

	$new_id = $product->save();

	if ( ! $new_id ) {
		/* translators: %s: product name. */
		$errors[] = sprintf( __( 'Product "%s" could not be saved.', 'techjossecom' ), $data['name'] );

		return 0;
	}

	return (int) $new_id;
}




