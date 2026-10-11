<?php
/**
 * COD shipping helpers: all zones + zone regions.
 *
 * @package TechJosse_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Label for one zone location.
 *
 * @param object $location Zone location.
 * @return string
 */
function techjossecom_zone_region_label( $location ) {
	$type = isset( $location->type ) ? (string) $location->type : '';
	$code = isset( $location->code ) ? (string) $location->code : '';
	if ( '' === $code ) {
		return '';
	}
	if ( function_exists( 'WC' ) && WC()->countries ) {
		$countries = WC()->countries->get_countries();
		if ( 'country' === $type && isset( $countries[ $code ] ) ) {
			return $countries[ $code ];
		}
		if ( 'state' === $type ) {
			$parts = explode( ':', $code );
			if ( 2 === count( $parts ) ) {
				$states = WC()->countries->get_states( $parts[0] );
				if ( isset( $states[ $parts[1] ] ) ) {
					return $states[ $parts[1] ];
				}
			}
			return $code;
		}
	}
	return $code;
}

/**
 * Evaluate a flat-rate style cost string.
 *
 * @param string $formula Cost string.
 * @param array  $package Package.
 * @return float
 */
function techjossecom_evaluate_method_cost( $formula, $package ) {
	$formula = trim( (string) $formula );
	if ( '' === $formula ) {
		return 0.0;
	}
	$qty  = 0;
	$cost = isset( $package['contents_cost'] ) ? (float) $package['contents_cost'] : 0.0;
	if ( ! empty( $package['contents'] ) && is_array( $package['contents'] ) ) {
		foreach ( $package['contents'] as $item ) {
			$qty += isset( $item['quantity'] ) ? (int) $item['quantity'] : 0;
		}
	}
	$formula = str_replace( array( '[qty]', '[cost]' ), array( $qty, $cost ), $formula );
	$formula = preg_replace( '/\[fee[^\]]*\]/', '0', $formula );
	if ( class_exists( 'WC_Eval_Math' ) ) {
		$evaluated = WC_Eval_Math::evaluate( $formula );
		return is_numeric( $evaluated ) ? (float) $evaluated : (float) $formula;
	}
	return is_numeric( $formula ) ? (float) $formula : 0.0;
}

/**
 * Minimal package for one product + quantity.
 *
 * @param int $product_id Product id.
 * @param int $variation_id Variation id.
 * @param int $quantity Quantity.
 * @return array
 */
function techjossecom_cod_package_for_product( $product_id, $variation_id = 0, $quantity = 1 ) {
	$quantity   = max( 1, (int) $quantity );
	$product_id = absint( $product_id );
	$product    = function_exists( 'wc_get_product' ) ? wc_get_product( $variation_id ? $variation_id : $product_id ) : null;
	if ( ! $product && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( $product_id );
	}
	$line_total = $product ? (float) $product->get_price() * $quantity : 0.0;
	return array(
		'contents'      => array( array( 'data' => $product, 'quantity' => $quantity, 'line_total' => $line_total ) ),
		'contents_cost' => $line_total,
		'destination'   => array( 'country' => '', 'state' => '', 'postcode' => '', 'city' => '', 'address' => '', 'address_1' => '', 'address_2' => '' ),
	);
}



/**
 * Cost of one method instance for the package.
 *
 * @param object $method Method instance.
 * @param array  $package Package.
 * @return array
 */
function techjossecom_cod_method_cost( $method, $package ) {
	$cost = 0.0;
	$has_free = false;
	$class_cost = 0.0;
	if ( ! is_object( $method ) || ! method_exists( $method, 'get_option' ) ) {
		return array( 'cost' => 0.0, 'has_free' => false, 'class_cost' => 0.0 );
	}
	$mid  = method_exists( $method, 'get_method_id' ) ? $method->get_method_id() : '';
	$type = $method->get_option( 'type', '' );
	if ( 'free_shipping' === $mid ) {
		if ( in_array( $type, array( '', 'min_amount' ), true ) ) {
			$min = (float) $method->get_option( 'min_amount', 0 );
			if ( 0 === $min || (float) $package['contents_cost'] >= $min ) {
				$has_free = true;
			} else {
				return array( 'cost' => null, 'has_free' => false, 'class_cost' => 0.0 );
			}
		} else {
			$has_free = true;
		}
	} elseif ( 'flat_rate' === $mid ) {
		$cost = techjossecom_evaluate_method_cost( $method->get_option( 'cost', 0 ), $package );
		if ( ! empty( $package['contents'] ) ) {
			$found = array();
			foreach ( $package['contents'] as $item ) {
				if ( empty( $item['data'] ) || ! is_callable( array( $item['data'], 'get_shipping_class_id' ) ) ) {
					continue;
				}
				$found[] = (int) $item['data']->get_shipping_class_id();
			}
			if ( $found ) {
				foreach ( array_unique( $found ) as $cid ) {
					if ( 0 === $cid ) {
						continue;
					}
					$term = get_term_by( 'id', $cid, 'product_shipping_class' );
					if ( ! $term ) {
						continue;
					}
					$raw = $method->get_option( 'class_cost_' . $term->slug, '' );
					if ( '' === trim( (string) $raw ) ) {
						continue;
					}
					$extra = techjossecom_evaluate_method_cost( $raw, $package );
					if ( $extra > $class_cost ) {
						$class_cost = $extra;
					}
				}
				$no_class = $method->get_option( 'no_class_cost', '' );
				if ( in_array( 0, $found, true ) && '' !== trim( (string) $no_class ) ) {
					$extra = techjossecom_evaluate_method_cost( $no_class, $package );
					if ( $extra > $class_cost ) {
						$class_cost = $extra;
					}
				}
			}
		}
	} elseif ( 'local_pickup' === $mid ) {
		$cost = (float) $method->get_option( 'cost', 0 );
	} else {
		$raw  = $method->get_option( 'cost', null );
		$cost = null === $raw ? 0.0 : techjossecom_evaluate_method_cost( $raw, $package );
	}
	if ( function_exists( 'wc_get_price_decimals' ) && method_exists( $method, 'get_taxes_per_item' ) && $cost > 0 ) {
		$taxes = $method->get_taxes_per_item( array( $cost ) );
		if ( is_array( $taxes ) ) {
			foreach ( $taxes as $tax ) {
				$cost += (float) $tax;
			}
		}
	}
	$dec = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
	return array( 'cost' => round( (float) $cost, $dec ), 'has_free' => $has_free, 'class_cost' => $class_cost );
}

/**
 * Every enabled method across ALL zones + zone regions.
 *
 * @param int $product_id Product id.
 * @param int $variation_id Variation id.
 * @param int $quantity Quantity.
 * @return array
 */
function techjossecom_wc_shipping_options( $product_id = 0, $variation_id = 0, $quantity = 1 ) {
	if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Shipping_Zones' ) ) {
		return array();
	}
	$package = techjossecom_cod_package_for_product( $product_id, $variation_id, $quantity );
	$options = array();
	$zones   = WC_Shipping_Zones::get_zones();
	$zones[] = array( 'zone_id' => 0, 'zone_name' => __( 'Rest of the World', 'woocommerce' ), 'shipping_methods' => WC_Shipping_Zones::get_shipping_methods( 0 ) );
	foreach ( $zones as $zd ) {
		$zid  = isset( $zd['zone_id'] ) ? (int) $zd['zone_id'] : 0;
		$zone = new WC_Shipping_Zone( $zid );
		$zname = $zone->get_zone_name();
		if ( '' === $zname && isset( $zd['zone_name'] ) ) {
			$zname = $zd['zone_name'];
		}
		$regions = array();
		foreach ( $zone->get_zone_locations() as $loc ) {
			$label = techjossecom_zone_region_label( $loc );
			if ( '' === $label ) {
				continue;
			}
			$regions[] = array( 'code' => isset( $loc->code ) ? (string) $loc->code : '', 'type' => isset( $loc->type ) ? (string) $loc->type : '', 'label' => $label );
		}
		$methods = isset( $zd['shipping_methods'] ) ? $zd['shipping_methods'] : array();
		if ( empty( $methods ) && method_exists( $zone, 'get_shipping_methods' ) ) {
			$methods = $zone->get_shipping_methods();
		}
		foreach ( (array) $methods as $m ) {
			if ( ! is_object( $m ) || ! method_exists( $m, 'is_enabled' ) || 'yes' !== $m->is_enabled() ) {
				continue;
			}
			$r = techjossecom_cod_method_cost( $m, $package );
			if ( null === $r['cost'] ) {
				continue;
			}
			$title = method_exists( $m, 'get_title' ) ? $m->get_title() : '';
			if ( '' === $title ) {
				$title = $zname;
			}
			$options[] = array(
				'label' => $title,
				'cost' => $r['cost'],
				'has_free' => $r['has_free'],
				'class_cost' => $r['class_cost'],
				'method_id' => method_exists( $m, 'get_method_id' ) ? $m->get_method_id() : '',
				'instance_id' => method_exists( $m, 'get_instance_id' ) ? (int) $m->get_instance_id() : 0,
				'zone_id' => $zid,
				'zone_name' => $zname,
				'zone_regions' => $regions,
			);
		}
	}
	return $options;
}

