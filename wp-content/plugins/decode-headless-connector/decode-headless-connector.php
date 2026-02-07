<?php
/**
 * Plugin Name: Decode Headless Connector
 * Description: Version simple et optimisée.
 * Version: 1.1
 * Author: Decode
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Chargement des fichiers classes.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-dhc-api.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-dhc-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-dhc-shortcodes.php';
// Initialisation globale.
function dhc_start() {
	// Instance unique de l'API partagée.
	$api = new DHC_Api();
	
	// Lancement Admin et Shortcodes.
	new DHC_Admin( $api );
	new DHC_Shortcodes( $api );
}
add_action( 'plugins_loaded', 'dhc_start' );
