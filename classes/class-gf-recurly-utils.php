<?php

class GFRecurly_Utils{

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {
	}

	public static function random_string( $length = 10 ) {

		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen( $characters );
		$randomString = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$randomString .= $characters[ rand( 0, $charactersLength - 1 ) ];
		}

		return $randomString;
	}

	public static function add_note( $entry_id, $note, $note_type = null ) {

		$user_id   = 0;
		$user_name = 'Recurly';

		GFFormsModel::add_note( $entry_id, $user_id, $user_name, $note, $note_type );
	}

	public static function cents_to_dollars( $cents = 0, $decimals = 0 ) {

		return number_format( ( $cents / 100 ), $decimals, '.', '' );
	}

	public static function dollars_to_cents( $dollars = 0, $decimals = 0 ) {

		return number_format( ( $dollars * 100 ), $decimals, '.', '' );
	}

	public static function log_debug( $message ) {

		if ( class_exists( 'GFLogging' ) ) {

			GFLogging::include_logger();

			GFLogging::log_message( 'gravityforms-recurly', $message, KLogger::DEBUG );

		}
	}

	public static function notify_internal_error( $e = null, $notification, $form, $lead ) {

		if ( ! empty( $e ) ) {

			$error_class   = get_class( $e );
			$error_message = $e->getMessage();
			$response      = $error_class . ': ' . $error_message;

			GFRecurly_Utils::log_debug( print_r( $response, true ) );
		}

		$notification = GFCommon::send_notification( $notification, $form, $lead );

	}

	public static function objectToArray( $object ) {

		if ( ! is_object( $object ) && ! is_array( $object ) ) {
			return $object;
		}

		return array_map( 'GFRecurly_Utils::objectToArray', (array) $object );
	}

	public static function add_new_subscription_data( $subscription, $entry ) {

		$subscription = GFRecurly_API_Utils::recurly_subscription_object_to_array( $subscription );

		$transaction_id = rgar( $subscription, 'plan_code' );
		$transaction_amount = GFRecurly_Utils::cents_to_dollars( rgar( $subscription, 'unit_amount_in_cents' ), 2 );
		$transaction_currency = rgar( $subscription, 'currency' );
		$entry_id = rgar( $entry, 'id' );

		GFRecurly_Utils::log_debug( "Gravity Forms + Recurly: Adding new subscription data: entry, {$entry_id}, false, subscription_payment, {$transaction_id}, active, {$transaction_amount}, {$transaction_currency}, ".print_r( $subscription, true ) );

		/* Insert into GF Recurly DB table */
		$gf_recurly_id = GFRecurly_Data_IO::insert_transaction(
			'entry',
			$entry_id,
			false,
			'subscription_payment',
			$transaction_id,
			'active',
			$transaction_amount,
			$transaction_currency,
			$subscription
		);

		if ( $gf_recurly_id ) {

			/* Link Entry with the DB table unique ID */
			gform_update_meta( $entry_id, 'gf_recurly_entry', $gf_recurly_id );
		}
	}

	public static function add_new_single_payment_data( $transaction, $entry ) {

		$transaction = GFRecurly_API_Utils::recurly_transaction_object_to_array( $transaction );

		$transaction_id = rgar( $transaction, 'uuid' );
		$transaction_amount = GFRecurly_Utils::cents_to_dollars( rgar( $transaction, 'amount_in_cents' ), 2 );
		$transaction_currency = rgar( $transaction, 'currency' );
		$entry_id = rgar( $entry, 'id' );

		GFRecurly_Utils::log_debug( "Gravity Forms + Recurly: Adding new transaction data: entry, {$entry_id}, false, single_payment, {$transaction_id}, paid, {$transaction_amount}, {$transaction_currency}, ".print_r( $transaction, true ) );

		/* Insert into GF Recurly DB table */
		$gf_recurly_id = GFRecurly_Data_IO::insert_transaction(
			'entry',
			$entry_id,
			false,
			'single_payment',
			$transaction_id,
			'paid',
			$transaction_amount,
			$transaction_currency,
			$transaction
		);

		if ( $gf_recurly_id ) {

			/* Link Entry with the DB table unique ID */
			gform_update_meta( $entry_id, 'gf_recurly_entry', $gf_recurly_id );
		}
	}

	public static function create_wp_user( $first_name, $last_name, $user_login, $user_email = false ) {

		$user_args = array(
			'role'       => apply_filters( 'gfrecurly_user_role', 'recurly_customer' ),
			'user_pass'  => wp_generate_password(),
			'user_login' => $user_login,
			'first_name' => $first_name,
			'last_name'  => $last_name,
		);

		if ( $user_email ) {
			$user_args['user_email'] = $user_email;
		}

		GFRecurly_Utils::log_debug( sprintf( __( 'Inserting new user — user_login: %s, first_name: %s, last_name: %s, user_email: %s', 'gravityforms-recurly' ), $user_login, $first_name, $last_name, $user_email ) );

		$user_id = wp_insert_user( $user_args );

		if ( is_wp_error( $user_id ) ) {

			$user_id = ( 'existing_user_login' == $user_id->get_error_code() ) ? username_exists( $user_login ) : $user_id;

		}

		return $user_id;
	}

	private static function create_user( $entry, $form, $recurly_data ) {

		$account_array = rgar( $recurly_data, 'account' );
		$entry_id = rgar( $entry, 'id' );

		$email = rgar( $account_array, 'email' );
		$first_name = rgar( $account_array, 'first_name' );
		$last_name = rgar( $account_array, 'last_name' );
		$account_code = rgar( $account_array, 'account_code' );

		$user_login = apply_filters( 'gfrecurly_new_user_login', $email, $entry, $form );

		GFRecurly_Utils::log_debug( "Creating WordPress user {$first_name} {$last_name} {$user_login}" );

		$user_id = GFRecurly_Utils::create_wp_user( $first_name, $last_name, $user_login, $email );

		if ( is_wp_error( $user_id ) ) {

			$error_message = $user_id->get_error_message( $user_id->get_error_code() );

			GFRecurly_Utils::log_debug( "User creation failed: {$error_message}" );

			//notify admin
			$notification['subject'] = sprintf( __( 'Unable to Create WordPress User for New Recurly Customer: %s', 'gravityforms-recurly' ), $name );
			$notification['message'] = sprintf( __( "Form: %s\\%s\r\n", 'gravityforms-recurly' ), $form['id'], $form['title'] );
			$notification['message'] .= sprintf( __( "Entry ID: %s\r\n", 'gravityforms-recurly' ), $entry['id'] );
			$notification['message'] .= sprintf( __( "Customer ID: %s\r\n", 'gravityforms-recurly' ), $account_code );
			$notification['message'] .= __( "Error message: {$error_message}\r\n", 'gravityforms-recurly' );
			$notification['to'] = $notification['from'] = get_option( 'admin_email' );

			GFRecurly_Utils::notify_internal_error( null, $notification, $form, $entry );

		} else {

			RGFormsModel::update_lead_property( $entry_id, 'created_by', $user_id );

			GFRecurly_Data_IO::update_transaction( $entry_id, array(
				'user_id' => $user_id,
			) );

		}
	}

	public static function are_plugin_settings_entered( $gfpaymentaddon ) {

		$gf_recurly_subdomain = $gfpaymentaddon->get_plugin_setting( 'gf_recurly_subdomain' );
		$gf_recurly_api_key = $gfpaymentaddon->get_plugin_setting( 'gf_recurly_api_key' );

		if ( rgblank( $gf_recurly_subdomain ) || rgblank( $gf_recurly_api_key ) ) {
			return false;
		}

		return true;
	}

	public static function are_any_feeds_active( $active_feeds = array() ) {

		$found_any = false;
		if ( ! empty( $active_feeds ) ) {

			foreach ( $active_feeds as $a_feed ) {

				if ( $a_feed['is_active'] ) {

					$found_any = true;
					break;
				}
			}
		}

		return $found_any;
	}

	public static function save_recurly_info_wp_user( $user_id, $entry_id, $transaction_type = 'single_payment', $recurly_object, $last_four = '' ) {

		GFRecurly_Utils::save_recurly_account_code( $user_id, rgars( $recurly_object, 'account/account_code' ) );
		GFRecurly_Utils::maybe_save_recurly_account_has_billing_info( $user_id, rgar( $recurly_object, 'account' ), $last_four );
		GFRecurly_Utils::maybe_save_recurly_account_currency( $user_id, rgar( $recurly_object, 'currency' ) );

		switch ( $transaction_type ) {
			case 'single_payment':
				//Silly to record individual transaction ids to user? Would also need to do the same for subscription transaction ids -- Any benefit?
				//GFRecurly_Utils::maybe_save_recurly_transaction( $user_id, $recurly_object );
			case 'subscription_payment':
				GFRecurly_Utils::maybe_save_recurly_subscription( $user_id, $recurly_object );
			break;
		}

		gform_update_meta( $entry_id, 'gfrecurly_user_id', $user_id );

		do_action( 'gf_recurly_gform_user_registered_save_recurly_info_to_wp_user', $user_id, $entry_id, $recurly_object );
	}

	public static function save_recurly_account_code( $user_id, $account_code ) {

		update_user_meta( $user_id, 'recurly_account_code', $account_code );
	}

	public static function maybe_save_recurly_account_has_billing_info( $user_id, $account, $last_four = '' ) {

		$has_billing_info = rgar( $account, 'billing_info' ) ? true : false;
		update_user_meta( $user_id, 'recurly_account_has_billing_info', array(
			'has_billing_info' => $has_billing_info,
			'last_four' => $last_four
		) );
	}

	public static function maybe_save_recurly_account_currency( $user_id, $currency = 'USD' ) {

		update_user_meta( $user_id, 'recurly_account_currency', $currency );
	}

	public static function maybe_save_recurly_subscription( $user_id, $sub = array(), $sub_state = 'active' ) {

		$acc = rgar( $sub, 'account' );

		if ( $sub_state === rgar( $acc, 'state' ) ) {

			$transaction = rgar( $sub, 'plan_code' );
			if ( ! in_array( $transaction, GFRecurly_Utils::get_active_recurly_subscriptions( $user_id ) ) ) {

				GFRecurly_Utils::add_active_recurly_subscription_to_user( $user_id, $transaction );
			}
		}
	}

	public static function get_active_recurly_subscriptions( $user_id ) {

		return get_user_meta( $user_id, 'recurly_account_transactions' ) ?: array();
	}

	public static function add_active_recurly_subscription_to_user( $user_id, $transaction ) {

		$existing_transactions = GFRecurly_Utils::get_active_recurly_subscriptions( $user_id );
		$existing_transactions[] = $transaction;
		update_user_meta( $user_id, 'recurly_account_transactions', $existing_transactions );
	}

	public static function add_customer_metadata( $user_id, $entry_id, $recurly_object ) {

		$recurly_object['user_id'] = $user_id;
		$recurly_object['entry_id'] = $entry_id;

		$recurly_object = apply_filters( 'gf_recurly_gform_user_registered_add_customer_metadata', $recurly_object, $user_id, $entry_id );

		GFRecurly_Utils::log_debug( "Updating DB with 'data' : " . print_r( $recurly_object, true ) . " and 'user_id' : " . print_r( (int) $user_id, true ) . '.' );

		GFRecurly_Data_IO::update_transaction( $entry_id, array(
			'data' => $recurly_object,
			'user_id' => (int) $user_id,
		) );

		do_action( 'gf_recurly_gform_user_registered_add_customer_metadata', $user_id, $entry_id, $recurly_object );
	}
}
