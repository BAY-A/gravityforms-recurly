<?php

class GFRecurly_User_Registered{

	protected static $_instance = null;
	protected $gfpaymentaddon = null;

	public static function instance( $gfpaymentaddon ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $gfpaymentaddon );
		}

		return self::$_instance;
	}

	public function __construct( $gfpaymentaddon ) {

		$this->gfpaymentaddon = $gfpaymentaddon;
	}

	public function gform_user_registered( $user_id, $feed, $entry, $password ) {

		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: gform_user_registered action.' );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: feed ' . print_r( $feed, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: entry ' . print_r( $entry, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: user_id ' . print_r( $user_id, true ) );

		//Check whether it was successful (via Recurly table entry), and only proceed if so
		$entry_id = rgar( $entry, 'id' );
		$form_id = rgar( $entry, 'form_id' );
		$form = GFAPI::get_form( $form_id );

		$submission_data = $this->gfpaymentaddon->get_submission_data( $feed, $form, $entry );

		$card_number = rgar( $submission_data, 'card_number' );
		$last_four = $card_number ? substr( $card_number, -4 ) : '';

		$transaction_type = gform_get_meta( $entry_id, 'transactionType' );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: transaction_type ' . print_r( $transaction_type, true ) );

		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: form entry id: {$entry_id}" );
		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: form id: {$form_id}" );

		$transaction_type_label = '';

		switch ( $transaction_type ) {
			case 'subscription':
				$transaction_type_label = 'subscription_payment';
			case 'product':
				$transaction_type_label = 'single_payment';
			break;
		}

		$recurly_object = GFRecurly_Data_IO::get_transaction_by_entry_id( $entry_id );
		$recurly_object = rgar( $recurly_object, 'data' );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Got recurly object: ' . print_r( $recurly_object, true ) );

		if ( $recurly_object ) {

			$auto_login_users = gform_get_meta( $entry_id, 'autoLoginNewUsers' ) ?: false;
			$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: auto_login_users: {$auto_login_users}" );

			if ( $auto_login_users ) {

				$active_feeds = gf_user_registration()->get_active_feeds( $form_id );
				$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: active_feeds (user registration): ' . print_r( $active_feeds, true ) );

				//Perhaps not needed (below)?
				if ( GFRecurly_Utils::are_any_feeds_active( $active_feeds ) ) {

					$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Active feeds found, continuing' );

					//Run 'save_recurly_info_to_wp_user'
					GFRecurly_Utils::save_recurly_info_wp_user( $user_id, $entry_id, $transaction_type_label, $recurly_object, $last_four );

					//Then run 'add_customer_metadata'
					GFRecurly_Utils::add_customer_metadata( $user_id, $entry_id, $recurly_object );

					//IF the user is not logged in
					if ( ! is_user_logged_in() ) {

						$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: User is not logged on' );

						//Get the user id, and log them in
						$user = new WP_User( $user_id );
						$signon_result = wp_signon( array(
							'user_login'    => $user->user_login,
							'user_password' => $password,
						) );

						$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Tried signing-in user: ' . print_r( $signon_result, true ) );
					}

					do_action( 'gf_recurly_gform_user_registered_user_logged_in', $user_id, $feed, $entry, $password );
				}
			}
		}
	}
}
