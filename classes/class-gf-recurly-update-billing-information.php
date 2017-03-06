<?php
class GFRecurly_Update_Billing_Information {

	protected static $_instance = null;
	protected $gfpaymentaddon = null;
	protected $gfrecurlyapi = null;

	public static function instance( $gfpaymentaddon ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $gfpaymentaddon );
		}

		return self::$_instance;
	}

	public function __construct( $gfpaymentaddon ) {

		require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-api-wrapper.php';

		$this->gfpaymentaddon = $gfpaymentaddon;
		$this->gfrecurlyapi = GFRecurly_API_Wrapper::instance( $gfpaymentaddon );
	}

	public function update_billing_information( $feed, $submission_data, $form, $entry ) {

		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Starting update_billing_information function' );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: feed: '.print_r( $feed, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: submission_data: '.print_r( $submission_data, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: form: '.print_r( $form, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: entry: '.print_r( $entry, true ) );

		if ( ! is_user_logged_in() ) {

			$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: User not logged in - Aborting' );
			return array(
				'is_success' => false,
				'error_message' => 'You must be logged-in to be able to update your billing information.',
			);
		}

		$user_id = get_current_user_id();

		if ( ! GFRecurly_Utils::does_user_have_a_recurly_account( $user_id ) ) {

			$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: User has no Recurly account - Aborting' );
			return array(
				'is_success' => false,
				'error_message' => 'There isn\'t a Recurly account associated with your WordPress account.',
			);
		}

		/* Get Recurly Account Code */
		$recurly_account_code = GFRecurly_Utils::get_recurly_account_code( $user_id );

		/* Get Feed Meta */
		$feed_meta = rgar( $feed, 'meta' );

		/* Set entry IDs */
		$first_name_entry_id = rgar( $feed_meta, 'billingInformation_first_name' );
		$last_name_entry_id = rgar( $feed_meta, 'billingInformation_last_name' );

		/* User Information */
		$user_first_name = rgar( $entry, $first_name_entry_id );
		$user_last_name = rgar( $entry, $last_name_entry_id );
		$user_email = rgar( $submission_data, 'email' );
		$user_ip = rgar( $entry, 'ip' );

		/* User Address */
		$address_1 = rgar( $submission_data, 'address' );
		$address_2 = rgar( $submission_data, 'address2' );
		$address_city = rgar( $submission_data, 'city' );
		$address_state = rgar( $submission_data, 'state' );
		$address_zip = rgar( $submission_data, 'zip' );
		$address_country = rgar( $submission_data, 'country' );

		/* Credit Card */
		$cc_number = rgar( $submission_data, 'card_number' );
		$cc_exp_date = rgar( $submission_data, 'card_expiration_date' );
		$cc_code = rgar( $submission_data, 'card_security_code' );
		$cc_name = rgar( $submission_data, 'card_name' );

		try {

			$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Trying to update Billing Information' );
			$billing_information = $this->gfrecurlyapi->update_billing_information(
				$recurly_account_code,
				$user_first_name,
				$user_last_name,
				$user_email,
				$address_1,
				$address_2,
				$address_city,
				$address_state,
				$address_zip,
				$address_country,
				$user_ip,
				$cc_number,
				$cc_code,
				$cc_exp_date[0],
				$cc_exp_date[1]
			);

			if( $billing_information ){

				$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Returning billing_information object in form validation success array: ' . print_r( $billing_information, true ) );
				return array(
					'is_success' => true,
					'billing_information' => $billing_information
				);
			}

		} catch (Exception $e) {

			$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Error: ' . $e->getMessage() );
			return array(
				'is_success' => false,
				'error_message' => 'Recurly error: ' . $e->getMessage(),
			);
		}
	}
}
?>
