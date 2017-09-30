<?php
/**
 * Plugin Name: GPS Vulture
 * Plugin URI: http://luminfire.com
 * Description: Collect GPS tracks and draw shapes on a map.
 * Version: 0.0.1
 * Author: Michael Moore / Luminfire.com
 * Text Domain: luminfire
 * Domain Path: /lang
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package gps-vulture
 *
 * -------------------------------------------------------------
 * Copyright 2017, LuminFire
 */

define( 'GPS_VULTURE_VERSION', '0.0.1' );


/**
 * Set up the GPS Vulture when GravityForms loads.
 */
function gps_vulture_init() {
	require_once( __DIR__ . '/lib/class-gps-vulture.php' );

	$wpgm_loader = __DIR__ . '/lib/wp-geometa-lib/wp-geometa-lib-loader.php';
	if ( !file_exists( $wpgm_loader ) ) {
		error_log( __( "Could not load wp-geometa-lib. You probably cloned GPS Vulture from git and didn't check out submodules!", 'gps-vulture' ) );
		return false;
	} 

	$leaflet_loader = __DIR__ . '/lib/leaflet-php/leaflet-php-loader.php';
	if ( !file_exists( $leaflet_loader ) ) {
		error_log( __( "Could not load Leaflet-PHP. You probably cloned GPS Vulture from git and didn't check out submodules!", 'gps-vulture' ) );
		return false;
	} 

	require_once( __DIR__ . '/lib/wp-geometa-lib/wp-geometa-lib-loader.php' );
	require_once( __DIR__ . '/lib/leaflet-php/leaflet-php-loader.php' );

	GFForms::include_addon_framework();
	GPS_Vulture::register();
}
add_action( 'gform_loaded', 'gps_vulture_init', 5 );

/**
 * On activation make sure that Gravity Forms is present. 
 */
function gps_vulture_activation_hook() {
	if ( !class_exists( 'GFForms' ) || -1 === version_compare( GFForms::$version, '2.0.0' ) ) {
		wp_die( esc_html__( 'This plugin requires Gravity Forms 2.0.0 or higher. Please install and activate it first, then activate this plugin.', 'gps-vulture') );
    }

	$wpgm_loader = __DIR__ . '/lib/wp-geometa-lib/wp-geometa-lib-loader.php';
	if ( !file_exists( $wpgm_loader ) ) {
		wp_die( esc_html__( "Could not load wp-geometa-lib. You probably cloned GPS Vulture from git and didn't check out submodules!", 'gps-vulture' ) );
	}

	$leaflet_loader = __DIR__ . '/lib/leaflet-php/leaflet-php-loader.php';
	if ( !file_exists( $leaflet_loader ) ) {
		wp_die( esc_html__( "Could not load Leaflet-PHP. You probably cloned GPS Vulture from git and didn't check out submodules!", 'gps-vulture' ) );
	}

	require_once( $wpgm_loader );
	WP_GeoMeta::install();
}

register_activation_hook( __FILE__ , 'gps_vulture_activation_hook' );
