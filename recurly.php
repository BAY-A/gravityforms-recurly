<?php
/*
Plugin Name: Gravity Forms Recurly Add-On
Plugin URI: http://backlinko.com
Description: Recurly add-on for taking payments with Gravity Forms
Version: 1.0
Author: joneslloyd
Author URI: https://bay-a.co.uk
Text Domain: gravityforms-recurly
Domain Path: /languages
*/


define( 'GF_RECURLY_VERSION', '1.0.0' );
define( 'GF_RECURLY_DIR', plugin_dir_path( __FILE__ ) );
define( 'GF_RECURLY_URL', plugin_dir_url( __FILE__ ) );

add_action( 'gform_loaded', array( 'GF_Recurly_Bootstrap', 'load' ), 5 );

class GF_Recurly_Bootstrap {

	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-recurly.php' );

		GFAddOn::register( 'GFRecurly' );
	}
}

function gf_recurly() {
	return GFRecurly::get_instance();
}
