<?php

class GFRecurly_Post_Update_Billing_Completed{

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

	public function gform_post_update_billing_completed( $entry, $action, $feed ) {

		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Starting gform_post_update_billing_completed function' );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: entry: '.print_r( $entry, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: action: '.print_r( $action, true ) );

		if ( 'update_billing' === rgar( $action, 'transaction_type' ) && rgar( $action, 'billing_information' ) ) {

			$billing_information = rgar( $action, 'billing_information' );
			$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: 'gform_post_update_billing_completed' hook running with 'billing_information' ".print_r( $billing_information, true ) );
			$updated_billing_info_id = GFRecurly_Utils::add_new_update_billing_data( $billing_information, $entry );

			if ( $updated_billing_info_id ) {

				$billing_info_array = GFRecurly_Data_IO::get_transaction_by_table_id( $updated_billing_info_id );
				$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: billing_info_array' ".print_r( $billing_info_array, true ) );

				$entry_id = rgar( $entry, 'id' );
				$user_id = rgar( $entry, 'created_by' );
				$form_id = rgar( $entry, 'form_id' );
				$form = GFAPI::get_form( $form_id );

				$submission_data = $this->gfpaymentaddon->get_submission_data( $feed, $form, $entry );

				$card_number = rgar( $submission_data, 'card_number' );
				$last_four = $card_number ? substr( $card_number, -4 ) : '';

				//Run 'save_recurly_info_to_wp_user'
				GFRecurly_Utils::maybe_save_recurly_account_has_billing_info( $user_id, rgar( $billing_info_array, 'data' ), $last_four );

				//Then run 'add_customer_metadata'
				GFRecurly_Utils::add_customer_metadata( $user_id, $entry_id, rgar( $billing_info_array, 'data' ) );
			}
		}
	}
}
