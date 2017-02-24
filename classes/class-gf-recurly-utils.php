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

	public static function add_note( $entry_id, $note, $note_type = null ) {

		$user_id   = 0;
		$user_name = 'Recurly';

		GFFormsModel::add_note( $entry_id, $user_id, $user_name, $note, $note_type );
	}

	public static function cents_to_dollars( $cents = 0 ) {

		return number_format( ( $cents / 100 ), 2, '.', ' ' );
	}

	public static function log_debug( $message ) {

		if ( class_exists( 'GFLogging' ) ) {

			GFLogging::include_logger();

			GFLogging::log_message( 'gravity-forms-recurly', $message, KLogger::DEBUG );

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
		$transaction_amount = GFRecurly_Utils::cents_to_dollars( rgar( $subscription, 'unit_amount_in_cents' ) );
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

			RGFormsModel::update_lead_property( $entry['id'], 'created_by', $user_id );

			GFRecurly_Data_IO::update_transaction( $entry['id'], 'user_id', $user_id );

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

	public static function save_recurly_info_wp_user( $user_id, $entry_id, $recurly_subscription ) {

		GFRecurly_Utils::save_recurly_account_code( $user_id, rgars( $recurly_subscription, 'account/account_code' ) );
		GFRecurly_Utils::maybe_save_recurly_account_has_billing_info( $user_id, rgar( $recurly_subscription, 'account' ) );
		GFRecurly_Utils::maybe_save_recurly_account_currency( $user_id, rgar( $recurly_subscription, 'currency' ) );
		GFRecurly_Utils::maybe_save_recurly_subscription( $user_id, $recurly_subscription );
		gform_update_meta( $entry_id, 'gfrecurly_user_id', $user_id );

		do_action( 'gf_recurly_gform_user_registered_save_recurly_info_to_wp_user', $user_id, $entry_id, $recurly_subscription );
	}

	public static function save_recurly_account_code( $user_id, $account_code ) {

		update_user_meta( $user_id, 'recurly_account_code', $account_code );
	}

	public static function maybe_save_recurly_account_has_billing_info( $user_id, $account ) {

		$has_billing_info = rgar( $account, 'billing_info' ) ? true : false;
		update_user_meta( $user_id, 'recurly_account_has_billing_info', $has_billing_info );
	}

	public static function maybe_save_recurly_account_currency( $user_id, $currency = 'USD' ) {

		update_user_meta( $user_id, 'recurly_account_currency', $currency );
	}

	public static function maybe_save_recurly_subscription( $user_id, $sub = array() ) {

		$acc = rgar( $sub, 'account' );

		if ( 'active' === rgar( $acc, 'state' ) ) {

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

	public static function add_customer_metadata( $user_id, $entry_id, $recurly_subscription ) {

		$recurly_subscription['user_id'] = $user_id;
		$recurly_subscription['entry_id'] = $entry_id;

		$recurly_subscription = apply_filters( 'gf_recurly_gform_user_registered_add_customer_metadata', $recurly_subscription, $user_id, $entry_id );

		GFRecurly_Data_IO::update_transaction( $entry_id, 'data', $recurly_subscription );

		do_action( 'gf_recurly_gform_user_registered_add_customer_metadata', $user_id, $entry_id, $recurly_subscription );
	}
}
