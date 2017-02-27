<?php

class GFRecurly_Authorize{

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

	public function authorize( $feed, $submission_data, $form, $entry ) {

		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Starting authorize function' );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: feed: '.print_r( $feed, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: submission_data: '.print_r( $submission_data, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: form: '.print_r( $form, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: entry: '.print_r( $entry, true ) );

		$feed_meta = rgar( $feed, 'meta' );

		$first_name_entry_id = rgar( $feed_meta, 'billingInformation_first_name' );
		$last_name_entry_id = rgar( $feed_meta, 'billingInformation_last_name' );
		$payment_desc_entry_id = rgar( $feed_meta, 'paymentDesc' ) ?: false;

		/* Payment Description (optional) */
		$payment_desc = $payment_desc_entry_id ? rgar( $entry, $payment_desc_entry_id ) : '';

		/* Payment Amount */
		$payment_amount = rgar( $submission_data, 'payment_amount' );

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

		/* Account Code */
		$account_code = GFRecurly_Utils::random_string( 49 );

		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Trying creation of charge' );

		try {

			/* Form Billing Information */
			$billing_info = array(
				'first_name' => $user_first_name,
				'last_name' => $user_last_name,
				'number' => $cc_number,
				'verification_value' => $cc_code,
				'month' => $cc_exp_date[0],
				'year' => $cc_exp_date[1],
				'ip_address' => $user_ip,
				'address_one' => $address_1,
				'address_two' => $address_2,
				'city' => $address_city,
				'country' => $address_country,
				'state' => $address_state,
				'zip' => $address_zip,
			);

			/* Create Account */
			$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Trying to create account with: {$account_code}, {$user_email}, {$user_first_name}, {$user_last_name}, ".print_r( $billing_info, true ) );
			$account = $this->gfrecurlyapi->create_account( $account_code, $user_email, $user_first_name, $user_last_name, $billing_info );

			if ( $account ) {

				$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Account exists' );

				try {

					$transaction = $this->gfrecurlyapi->create_transaction( $account_code, $payment_amount, 'USD', $payment_desc );
					$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Transaction: '.print_r( $transaction, true ) );

					return array(
						'is_authorized' => true,
						'transaction_id' => $transaction->uuid,
						'captured_payment' => array(
							'is_success' => true,
							'error_message' => '',
							'transaction_id' => $transaction->uuid,
							'amount' => $payment_amount,
							'payment_method' => $this->gfpaymentaddon->slug,
							'recurly_transaction' => $transaction
						),
					);
				} catch (Exception $e) {

					return array(
						'is_authorized' => false,
						'error_message' => $e->getMessage(),
						'captured_payment' => array(
							'is_success' => false,
							'error_message' => $e->getMessage(),
							'amount' => $payment_amount,
							'payment_method' => $this->gfpaymentaddon->slug,
						),
					);
				}
			} else {
				return array(
					'is_authorized' => false,
					'captured_payment' => array(
						'is_success' => false,
						'error_message' => 'Could not create account',
						'amount' => 0,
						'payment_method' => $this->gfpaymentaddon->slug,
					),
				);
			}
		} catch (Exception $e) {

			return array(
				'is_authorized' => false,
				'error_message' => $e->getMessage(),
				'captured_payment' => array(
					'is_success' => false,
					'error_message' => $e->getMessage(),
					'payment_method' => $this->gfpaymentaddon->slug,
				),
			);
		}
	}
}
