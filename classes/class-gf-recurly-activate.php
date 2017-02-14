<?php
require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-data-io.php';
class GFRecurly_Activator {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {}

	public static function activate() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( GFRecurly_Data::get_table_schema() );
	}
}
?>
