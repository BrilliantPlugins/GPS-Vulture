<?php
/**
 * Plugin Name: Partial Station for Gravity Forms
 * Plugin URI: http://luminfire.com
 * Description: Collect GPS tracks and draw and edit tracks manually. It's not a Total Station, but it might be good enough for you!
 * Version: 0.0.1
 * Author: Michael Moore / Luminfire.com
 * Text Domain: luminfire
 * Domain Path: /lang
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package partial-station
 *
 * -------------------------------------------------------------
 * Copyright 2017, LuminFire
 */

define( 'PSGF_VERSION', '0.0.1' );


/**
 * Set up the Partial Station when GravityForms loads.
 */
function partial_station_init() {
	require_once( __DIR__ . '/lib/class-partial-station-gpstrace.php' );

	$wpgm_loader = __DIR__ . '/lib/wp-geometa-lib/wp-geometa-lib-loader.php';
	if ( !file_exists( $wpgm_loader ) ) {
		error_log( __( "Could not load wp-geometa-lib. You probably cloned Partial Station from git and didn't check out submodules!", 'partial-station' ) );
		return false;
	} 

	$leaflet_loader = __DIR__ . '/lib/leaflet-php/leaflet-php-loader.php';
	if ( !file_exists( $leaflet_loader ) ) {
		error_log( __( "Could not load Leaflet-PHP. You probably cloned Partial Station from git and didn't check out submodules!", 'partial-station' ) );
		return false;
	} 

	require_once( __DIR__ . '/lib/wp-geometa-lib/wp-geometa-lib-loader.php' );
	require_once( __DIR__ . '/lib/leaflet-php/leaflet-php-loader.php' );

	GFForms::include_addon_framework();
	Partial_Station_GPS_Trace::register();
}
add_action( 'gform_loaded', 'partial_station_init', 5 );

/**
 * On activation make sure that Gravity Forms is present. 
 */
function partial_station_activation_hook() {
	if ( !class_exists( 'GFForms' ) || -1 === version_compare( GFForms::$version, '2.0.0' ) ) {
		wp_die( esc_html__( 'This plugin requires Gravity Forms 2.0.0 or higher. Please install and activate it first, then activate this plugin.', 'partial-station') );
    }

	$wpgm_loader = __DIR__ . '/lib/wp-geometa-lib/wp-geometa-lib-loader.php';
	if ( !file_exists( $wpgm_loader ) ) {
		wp_die( esc_html__( "Could not load wp-geometa-lib. You probably cloned Partial Station from git and didn't check out submodules!", 'partial-station' ) );
	}

	$leaflet_loader = __DIR__ . '/lib/leaflet-php/leaflet-php-loader.php';
	if ( !file_exists( $leaflet_loader ) ) {
		wp_die( esc_html__( "Could not load Leaflet-PHP. You probably cloned Partial Station from git and didn't check out submodules!", 'partial-station' ) );
	}

	require_once( $wpgm_loader );
	WP_GeoMeta::install();
}

register_activation_hook( __FILE__ , 'partial_station_activation_hook' );
