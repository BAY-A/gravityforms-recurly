<?php
class GFRecurly_Data {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {}

	public static function get_recurly_table_name() {

		global $wpdb;

		return $wpdb->prefix . 'rg_recurly';
	}

	public static function get_table_schema() {

		global $wpdb;

		$sql = array();

		$recurly_table = GFRecurly_Data::get_recurly_table_name();

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		$sql[] = "CREATE TABLE $recurly_table (
	              id int(10) unsigned not null auto_increment,
	              method varchar(10) not null,
	              entry_id int(10) unsigned,
	              user_id int(10) unsigned,
	              transaction_type varchar(30) not null,
	              transaction_id varchar(50),
	              status varchar(20) not null,
	              amount decimal(19,2),
	              currency varchar(5),
	              date_created datetime,
	              data longtext,
	              PRIMARY KEY  (id),
	              KEY entry_id (entry_id),
	              KEY user_id (user_id ),
	              KEY transaction_type (transaction_type),
	              KEY transaction_id (transaction_id)
	            )$charset_collate;";

		return array_merge( $sql, apply_filters( 'gfrecurly_data_get_table_schema', array() ) );

	}

	public static function insert_transaction( $method, $entry_id, $user_id = null, $transaction_type, $transaction_id, $status, $amount, $currency, $data = '' ) {

		GFRecurly_Utils::log_debug( 'Running `insert_transaction`' );

		global $wpdb;
		$table_name = GFRecurly_Data::get_recurly_table_name();

		$wpdb->query(
			$wpdb->prepare(
				"
				INSERT INTO $table_name
				 (method, entry_id, user_id, transaction_type, transaction_id, status, amount, currency, date_created, data)
				  values (%s, %d, %d, %s, %s, %s, %f, %s, utc_timestamp(), %s)
				",
				$method,
				$entry_id,
				$user_id,
				$transaction_type,
				$transaction_id,
				$status,
				$amount,
				$currency,
				wp_json_encode( $data )
			)
		);
		$id = $wpdb->insert_id;

		do_action( 'gform_post_payment_transaction', $id, $entry_id, $transaction_type, $transaction_id, $amount, false );

		return $id;
	}

	public static function update_transaction( $entry_id, $property_arr ) {

		global $wpdb;

		$table_name = GFRecurly_Data::get_recurly_table_name();

		array_walk( $property_arr, function( &$aitem ) {
			$aitem = wp_json_encode( $aitem );
		} );

		GFRecurly_Utils::log_debug( 'Running `update_transaction`: ' . print_r( $property_arr, true ) );

		$result = $wpdb->update( $table_name, $property_arr, array( 'entry_id' => $entry_id ) );
	}

	public static function get_all_form_ids() {
		global $wpdb;
		$table   = GFFormsModel::get_form_table_name();
		$sql     = "SELECT id from $table";
		$results = $wpdb->get_col( $sql );

		return $results;
	}

	public static function get_transaction_by( $type, $value ) {

		global $wpdb;

		$table_name  = GFRecurly_Data::get_recurly_table_name();
		$transaction = null;

		if ( 'entry' == $type || 'user_id' == $type || 'transaction_id' == $type ) {

			switch ( $type ) {

				case 'entry':

					$sql = $wpdb->prepare( "SELECT * FROM  {$table_name} WHERE entry_id=%d", $value );

					$transaction = $wpdb->get_row( $sql, ARRAY_A );

					if ( ! empty( $transaction ) ) {

						$transaction['data'] = GFFormsModel::unserialize( $transaction['data'] );

					}

					break;

				case 'user_id':

					$sql = $wpdb->prepare( "SELECT * FROM  {$table_name} WHERE user_id=%d", $value );

					$transaction = $wpdb->get_results( $sql, ARRAY_A );

					foreach ( $transaction as $key => $t ) {

						$transaction[ $key ]['data'] = GFFormsModel::unserialize( $t['data'] );

					}

					break;

				case 'transaction_id':

					$sql = $wpdb->prepare( "SELECT * FROM  {$table_name} WHERE transaction_id=%s", $value );

					$transaction = $wpdb->get_row( $sql, ARRAY_A );

					if ( ! empty( $transaction ) ) {

						$transaction['data'] = GFFormsModel::unserialize( $transaction['data'] );

					}

					break;

				case 'id':

					$sql = $wpdb->prepare( "SELECT * FROM  {$table_name} WHERE id=%d", $value );

					$transaction = $wpdb->get_row( $sql, ARRAY_A );

					if ( ! empty( $transaction ) ) {

						$transaction['data'] = GFFormsModel::unserialize( $transaction['data'] );

					}

						break;

			}
		}

		return $transaction;
	}

	public static function drop_tables() {

		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . GFRecurly_Data::get_recurly_table_name() );
	}
}
?>
