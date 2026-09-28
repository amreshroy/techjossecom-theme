<?php
/**
 * Performance and SEO layer.
 *
 * Keeps the HTML head clean and fast: no emoji scripts, no oEmbed discovery,
 * no jQuery Migrate on the front end, plus meta description, Open Graph and
 * JSON-LD structured data when no SEO plugin is active.
 *
 * @package TechJosse_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove the small pieces of WordPress that every modern site can do without.
 *
 * @return void
 */
function techjossecom_head_cleanup() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );

	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );

	// oEmbed discovery links and the embed script.
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_action( 'rest_api_init', 'wp_oembed_register_route' );
	add_filter( 'embed_oembed_discover', '__return_false' );
}
add_action( 'init', 'techjossecom_head_cleanup' );

/**
 * Speed up the front end by dropping jQuery Migrate and Dashicons for guests.
 *
 * @return void
 */
function techjossecom_script_optimisations() {
	if ( is_admin() || is_customize_preview() ) {
		return;
	}

	// jQuery Migrate is only needed by legacy plugins.
	add_action(
		'wp_default_scripts',
		function ( $scripts ) {
			if ( empty( $scripts->registered['jquery'] ) ) {
				return;
			}

			$jquery = $scripts->registered['jquery'];

			if ( ! empty( $jquery->deps ) ) {
				$jquery->deps = array_diff( $jquery->deps, array( 'jquery-migrate' ) );
			}
		}
	);

	// Dashicons are only needed for logged-in users (admin bar) and blocks.
	if ( ! is_user_logged_in() ) {
		wp_deregister_style( 'dashicons' );
	}

	// The comment reply script is only needed on singular pages with comments.
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'techjossecom_script_optimisations', 1 );

/**
 * Disable the XML-RPC endpoint and the WP REST oEmbed noise on tiny sites.
 *
 * @param bool $enabled Whether XML-RPC is enabled.
 * @return bool
 */
function techjossecom_disable_xmlrpc( $enabled ) {
	return false;
}
add_filter( 'xmlrpc_enabled', 'techjossecom_disable_xmlrpc' );

/**
 * Remove the WordPress.org resource hints.
 *
 * @param array  $urls          Resource hint URLs.
 * @param string $relation_type Relation type.
 * @return array
 */
function techjossecom_resource_hints( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$urls = array_values(
			array_filter(
				$urls,
				function ( $url ) {
					$url = is_array( $url ) ? $url['href'] : $url;

					return false === strpos( $url, 's.w.org' );
				}
			)
		);
	}

	return $urls;
}
add_filter( 'wp_resource_hints', 'techjossecom_resource_hints', 10, 2 );

/**
 * Build a short meta description for the current view.
 *
 * @return string
 */
function techjossecom_meta_description() {
	$description = '';

	if ( is_singular() ) {
		$post = get_queried_object();

		if ( $post instanceof WP_Post ) {
			if ( ! empty( $post->post_excerpt ) ) {
				$description = $post->post_excerpt;
			} else {
				$description = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
			}
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$description = term_description();
		if ( ! $description ) {
			/* translators: %s: archive name. */
			$description = sprintf( __( 'Browse %s and order online with cash on delivery.', 'techjossecom' ), single_term_title( '', false ) );
		}
	} elseif ( is_home() || is_front_page() ) {
		$description = techjossecom_mod( 'intro_text' );

		if ( ! $description ) {
			$description = get_bloginfo( 'description', 'display' );
		}
	} elseif ( is_search() ) {
		/* translators: %s: search query. */
		$description = sprintf( __( 'Search results for %s.', 'techjossecom' ), get_search_query() );
	}

	if ( ! $description ) {
		$description = get_bloginfo( 'description', 'display' );
	}

	$description = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $description ) ) );

	if ( mb_strlen( $description ) > 158 ) {
		$description = mb_substr( $description, 0, 155 ) . '...';
	}

	/**
	 * Filter the meta description printed by the theme.
	 *
	 * @param string $description Description text.
	 */
	return apply_filters( 'techjossecom_meta_description', $description );
}

/**
 * The best image available for social sharing on the current view.
 *
 * @return string Image URL or an empty string.
 */
function techjossecom_social_image() {
	if ( is_singular() && has_post_thumbnail() ) {
		$url = get_the_post_thumbnail_url( null, 'large' );
		if ( $url ) {
			return $url;
		}
	}

	if ( class_exists( 'WooCommerce' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product ) {
			$image_id = $product->get_image_id();
			if ( $image_id ) {
				$url = wp_get_attachment_image_url( $image_id, 'large' );
				if ( $url ) {
					return $url;
				}
			}
		}
	}

	$logo_id = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$url = wp_get_attachment_image_url( $logo_id, 'full' );
		if ( $url ) {
			return $url;
		}
	}

	return '';
}
/**
 * Print description, canonical, Open Graph and Twitter card tags.
 *
 * Skipped entirely when a dedicated SEO plugin is installed so the site never
 * ends up with duplicate meta tags.
 *
 * @return void
 */
function techjossecom_meta_tags() {
	if ( techjossecom_seo_plugin_active() ) {
		return;
	}

	$title       = wp_get_document_title();
	$description = techjossecom_meta_description();
	$image       = techjossecom_social_image();
	$current_url = home_url( '/', 'relative' );

	if ( is_singular() ) {
		$permalink   = get_permalink();
		$current_url = $permalink ? $permalink : $current_url;
	} else {
		$current_url = home_url( add_query_arg( array(), $GLOBALS['wp']->request ? '/' . ltrim( $GLOBALS['wp']->request, '/' ) . '/' : '/' ) );
	}

	if ( is_front_page() || is_home() ) {
		$current_url = home_url( '/' );
	}

	if ( $description ) {
		printf( '<meta name="description" content="%s" />' . "\n", esc_attr( $description ) );
	}

	printf( '<link rel="canonical" href="%s" />' . "\n", esc_url( $current_url ) );
	printf( '<meta name="theme-color" content="%s" />' . "\n", esc_attr( techjossecom_mod( 'primary_color' ) ) );

	printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $current_url ) );
	printf( '<meta property="og:locale" content="%s" />' . "\n", esc_attr( get_locale() ) );

	if ( $description ) {
		printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( $description ) );
	}

	if ( $image ) {
		printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $image ) );
	}

	$type = 'website';

	if ( is_singular( 'post' ) ) {
		$type = 'article';
	} elseif ( class_exists( 'WooCommerce' ) && is_product() ) {
		$type = 'product';
	}

	printf( '<meta property="og:type" content="%s" />' . "\n", esc_attr( $type ) );

	printf( '<meta name="twitter:card" content="%s" />' . "\n", esc_attr( $image ? 'summary_large_image' : 'summary' ) );
	printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr( $title ) );

	if ( $description ) {
		printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr( $description ) );
	}

	if ( $image ) {
		printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url( $image ) );
	}
}
add_action( 'wp_head', 'techjossecom_meta_tags', 1 );
/**
 * WebSite + Organization + Breadcrumb JSON-LD.
 *
 * WooCommerce already prints Product schema, so the theme never duplicates it.
 *
 * @return void
 */
function techjossecom_structured_data() {
	if ( techjossecom_seo_plugin_active() ) {
		return;
	}

	$graph = array();

	if ( is_front_page() ) {
		$graph[] = array(
			'@type'           => 'WebSite',
			'@id'             => home_url( '/#website' ),
			'url'             => home_url( '/' ),
			'name'            => get_bloginfo( 'name' ),
			'description'     => get_bloginfo( 'description', 'display' ),
			'inLanguage'      => get_bloginfo( 'language' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}&post_type=product' ),
				),
				'query-input' => 'required name=search_term_string',
			),
		);

		$organization = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);

		$logo_id = get_theme_mod( 'custom_logo' );
		if ( $logo_id ) {
			$org_image = wp_get_attachment_image_url( $logo_id, 'full' );
			if ( $org_image ) {
				$organization['logo']  = array(
					'@type' => 'ImageObject',
					'url'   => $org_image,
				);
				$organization['image'] = $org_image;
			}
		}

		$phone = techjossecom_mod( 'hotline_number' );
		if ( $phone ) {
			$organization['contactPoint'] = array(
				'@type'             => 'ContactPoint',
				'telephone'         => $phone,
				'contactType'       => 'customer service',
				'areaServed'        => 'BD',
				'availableLanguage' => array( 'Bengali', 'English' ),
			);
		}

		$socials = techjossecom_social_links();
		if ( $socials ) {
			$organization['sameAs'] = array_values( wp_list_pluck( $socials, 'url' ) );
		}

		$address = techjossecom_mod( 'contact_address' );
		if ( $address ) {
			$organization['address'] = array(
				'@type'          => 'PostalAddress',
				'streetAddress'  => $address,
				'addressCountry' => 'BD',
			);
		}

		$graph[] = $organization;
	}

	$crumbs = techjossecom_breadcrumb_items();

	if ( count( $crumbs ) > 1 ) {
		$elements = array();

		foreach ( $crumbs as $position => $crumb ) {
			$element = array(
				'@type'    => 'ListItem',
				'position' => $position + 1,
				'name'     => $crumb['label'],
			);

			if ( ! empty( $crumb['url'] ) ) {
				$element['item'] = $crumb['url'];
			}

			$elements[] = $element;
		}

		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => home_url( '/#breadcrumb' ),
			'itemListElement' => $elements,
		);
	}

	if ( ! $graph ) {
		return;
	}

	$data = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'wp_head', 'techjossecom_structured_data', 5 );
/**
 * Breadcrumb trail used by the templates and the JSON-LD graph.
 *
 * @return array List of array( 'label' => string, 'url' => string ).
 */
function techjossecom_breadcrumb_items() {
	$items = array(
		array(
			'label' => __( 'Home', 'techjossecom' ),
			'url'   => home_url( '/' ),
		),
	);

	if ( is_front_page() ) {
		return $items;
	}

	$shop_id = class_exists( 'WooCommerce' ) ? wc_get_page_id( 'shop' ) : 0;

	if ( $shop_id > 0 && is_shop() ) {
		$items[] = array(
			'label' => get_the_title( $shop_id ),
			'url'   => get_permalink( $shop_id ),
		);

		return $items;
	}

	if ( $shop_id > 0 && is_product() ) {
		$items[] = array(
			'label' => get_the_title( $shop_id ),
			'url'   => get_permalink( $shop_id ),
		);

		$terms = get_the_terms( get_queried_object_id(), 'product_cat' );

		if ( $terms && ! is_wp_error( $terms ) ) {
			$term    = array_shift( $terms );
			$parents = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );

			foreach ( $parents as $parent_id ) {
				$parent = get_term( $parent_id, 'product_cat' );
				if ( $parent && ! is_wp_error( $parent ) ) {
					$items[] = array(
						'label' => $parent->name,
						'url'   => get_term_link( $parent ),
					);
				}
			}

			$items[] = array(
				'label' => $term->name,
				'url'   => get_term_link( $term ),
			);
		}

		$items[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);

		return $items;
	}

	if ( is_archive() ) {
		$items[] = array(
			'label' => wp_strip_all_tags( get_the_archive_title() ),
			'url'   => '',
		);

		return $items;
	}

	if ( is_search() ) {
		/* translators: %s: search query. */
		$items[] = array(
			'label' => sprintf( __( 'Search: %s', 'techjossecom' ), get_search_query() ),
			'url'   => '',
		);

		return $items;
	}

	if ( is_singular() ) {
		$post_type = get_post_type_object( get_post_type() );

		if ( $post_type && ! empty( $post_type->has_archive ) ) {
			$items[] = array(
				'label' => $post_type->labels->name,
				'url'   => get_post_type_archive_link( get_post_type() ),
			);
		}

		$ancestors = array_reverse( get_post_ancestors( get_queried_object_id() ) );

		foreach ( $ancestors as $ancestor_id ) {
			$items[] = array(
				'label' => get_the_title( $ancestor_id ),
				'url'   => get_permalink( $ancestor_id ),
			);
		}

		$items[] = array(
			'label' => get_the_title(),
			'url'   => '',
		);
	}

	return $items;
}
/**
 * Print the breadcrumb trail.
 *
 * @return void
 */
function techjossecom_breadcrumbs() {
	$items = techjossecom_breadcrumb_items();

	if ( count( $items ) < 2 ) {
		return;
	}

	echo '<nav class="tj-breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'techjossecom' ) . '"><ol>';

	$last = count( $items ) - 1;

	foreach ( $items as $index => $item ) {
		if ( $index === $last || empty( $item['url'] ) ) {
			printf( '<li aria-current="page">%s</li>', esc_html( $item['label'] ) );
			continue;
		}

		printf(
			'<li><a href="%1$s">%2$s</a></li>',
			esc_url( $item['url'] ),
			esc_html( $item['label'] )
		);
	}

	echo '</ol></nav>';
}

/**
 * Drop the "Category:" / "Tag:" prefix from archive headings.
 *
 * @return string
 */
function techjossecom_archive_title_prefix() {
	return '';
}
add_filter( 'get_the_archive_title_prefix', 'techjossecom_archive_title_prefix' );

/**
 * Only load WooCommerce assets where they are actually used, and only load the
 * block library when the content really contains blocks.
 *
 * @return void
 */
function techjossecom_conditional_assets() {
	if ( is_admin() || is_customize_preview() ) {
		return;
	}

	$is_woo = class_exists( 'WooCommerce' )
		&& ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url() );

	if ( ! $is_woo ) {
		foreach ( array( 'woocommerce-layout', 'woocommerce-smallscreen', 'woocommerce-general', 'wc-blocks-style', 'wc-blocks-style-cart', 'wc-blocks-style-checkout' ) as $handle ) {
			wp_dequeue_style( $handle );
		}
	}

	$uses_blocks = false;

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$uses_blocks = function_exists( 'has_blocks' ) && has_blocks( $post );
		}
	}

	if ( ! $uses_blocks ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
		wp_dequeue_style( 'global-styles' );
	}
}
add_action( 'wp_enqueue_scripts', 'techjossecom_conditional_assets', 99 );

/**
 * Do not load remote block patterns.
 *
 * @return bool
 */
function techjossecom_disable_remote_patterns() {
	return false;
}
add_filter( 'should_load_remote_block_patterns', 'techjossecom_disable_remote_patterns' );

/**
 * Give the WooCommerce product loop images a proper alt attribute fallback so
 * every image on the site is accessible and indexable.
 *
 * @param array       $attr       Image attributes.
 * @param WP_Post     $attachment Attachment post.
 * @param string|int  $size       Requested size.
 * @return array
 */
function techjossecom_image_alt_fallback( $attr, $attachment, $size ) {
	if ( ! empty( $attr['alt'] ) ) {
		return $attr;
	}

	if ( $attachment instanceof WP_Post ) {
		$title = get_the_title( $attachment );

		if ( $title ) {
			$attr['alt'] = $title;
		} elseif ( function_exists( 'get_queried_object' ) ) {
			$object = get_queried_object();

			if ( $object instanceof WP_Post ) {
				$attr['alt'] = get_the_title( $object );
			}
		}
	}

	if ( empty( $attr['alt'] ) ) {
		$attr['alt'] = get_bloginfo( 'name' );
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'techjossecom_image_alt_fallback', 10, 3 );




