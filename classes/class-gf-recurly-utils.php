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
		$transaction_amount = rgar( $subscription, 'unit_amount_in_cents' );
		$transaction_currency = rgar( $subscription, 'currency' );
		$entry_id = rgar( $entry, 'id' );

		GFRecurly_Utils::log_debug( "Gravity Forms + Recurly: Adding new subscription data: entry, {$entry_id}, false, subscription_payment, {$transaction_id}, active, {$transaction_amount}, {$transaction_currency}, ".print_r( $subscription, true ) );

		return GFRecurly_Data_IO::insert_transaction(
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
	}

	public static function create_wp_user( $first_name, $last_name, $user_login, $user_email = false ){

		$user_args = array(
			'role'       => apply_filters( 'gfrecurly_user_role', 'recurly_customer' ),
			'user_pass'  => wp_generate_password(),
			'user_login' => $user_login,
			'first_name' => $first_name,
			'last_name'  => $last_name
		);

		if ( $user_email ) {
			$user_args[ 'user_email' ] = $user_email;
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
			$notification[ 'subject' ] = sprintf( __( "Unable to Create WordPress User for New Recurly Customer: %s", 'gravityforms-recurly' ), $name );
			$notification[ 'message' ] = sprintf( __( "Form: %s\\%s\r\n", 'gravityforms-recurly' ), $form['id'], $form['title'] );
			$notification[ 'message' ] .= sprintf( __( "Entry ID: %s\r\n", 'gravityforms-recurly' ), $entry['id'] );
			$notification[ 'message' ] .= sprintf( __( "Customer ID: %s\r\n", 'gravityforms-recurly' ), $account_code );
			$notification[ 'message' ] .= __( "Error message: {$error_message}\r\n", 'gravityforms-recurly' );
			$notification[ 'to' ] = $notification[ 'from' ] = get_option( 'admin_email' );

			GFRecurly_Utils::notify_internal_error( null, $notification, $form, $entry );

		} else {

			RGFormsModel::update_lead_property( $entry[ 'id' ], 'created_by', $user_id );

			GFRecurly_Data_IO::update_transaction( $entry[ 'id' ], 'user_id', $user_id );

		}
	}
}
