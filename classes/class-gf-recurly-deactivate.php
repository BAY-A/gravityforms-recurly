<?php
require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-data-io.php';
class GFRecurly_Deactivator {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {}

	public static function deactivate() {

		GFRecurly_Data::drop_tables();
	}
}
?>
