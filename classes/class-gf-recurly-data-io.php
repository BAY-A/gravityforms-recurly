<?php
require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-data.php';
class GFRecurly_Data_IO {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {}

	public static function insert_transaction( $t_method = 'entry', $t_entry_id = 0, $t_user_id = false, $t_transaction_type = 'single_payment', $t_transaction_id = '', $t_status = '', $t_amount = 0, $t_currency = 'USD', $t_data = null ) {

		if( !$t_user_id ){

			$t_user_id = get_current_user_id();
		}

		return GFRecurly_Data::insert_transaction(
			$t_method,
			$t_entry_id,
			$t_user_id,
			$t_transaction_type,
			$t_transaction_id,
			$t_status,
			$t_amount,
			$t_currency,
			$t_data
		);
	}

	public static function update_transaction( $entry_id, $property_name, $property_value ) {

		return GFRecurly_Data::update_transaction( $entry_id, $property_name, $property_value );
	}

	public static function get_all_recurly_data_for_user( $user_id = -1 ){

		return GFRecurly_Data::get_transaction_by( 'user_id', $user_id );
	}
}
?>
