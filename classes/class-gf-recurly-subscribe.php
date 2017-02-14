<?php

class GFRecurly_Subscribe{

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

		add_action( 'gform_post_subscription_started', array( $this, 'subscription_started' ), 10, 2 );
	}

	public function subscribe( $feed, $submission_data, $form, $entry ) {

		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Starting subscribe function" );
		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: feed: ".print_r( $feed, true ) );
		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: submission_data: ".print_r( $submission_data, true ) );
		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: form: ".print_r( $form, true ) );
		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: entry: ".print_r( $entry, true ) );

		$feed_meta = rgar( $feed, 'meta' );

		$recurly_plan_entry_id = rgar( $feed_meta, 'subscriptionPlan' );
		$first_name_entry_id = rgar( $feed_meta, 'billingInformation_first_name' );
		$last_name_entry_id = rgar( $feed_meta, 'billingInformation_last_name' );

		$recurly_plan_code = trim( rgar( $entry, $recurly_plan_entry_id ) );
		$recurly_plan_code = strpos( $recurly_plan_code, '|' ) !== false ? explode( '|', $recurly_plan_code )[0] : $recurly_plan_code;

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
		$account_code = $this->random_string( 49 );

		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Trying creation of subscription" );

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
				'zip' => $address_zip
			);

			/* Create Account */
			$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Trying to create account with: {$account_code}, {$user_email}, {$user_first_name}, {$user_last_name}, ".print_r( $billing_info, true ) );
			$account = $this->gfrecurlyapi->create_account( $account_code, $user_email, $user_first_name, $user_last_name, $billing_info );


			if( $account ){

				$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Account exists" );
				$subscription = $this->gfrecurlyapi->create_subscription( $account_code, $recurly_plan_code, 'USD' );
				$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Subscription: ".print_r( $subscription, true ) );

				return array(
					'is_success' => true,
					'error_message' => '',
					'subscription_id' => $subscription->plan_code,
					'amount' =>	$payment_amount,
					'recurly_subscription' => $subscription
				);
			}
			else{
				return array(
					'is_success' => false,
					'error_message' => 'Could not create account',
					'subscription_id' => '',
					'amount' => 0
				);
			}
		} catch (Recurly_ValidationError $e) {}

		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Account doesn't exist" );
		return array(
			'is_success' => false,
			'error_message' => 'Could not create subscription and account',
			'subscription_id' => '',
			'amount' => 0
		);
	}

	public function subscription_started( $entry, $subscription ){

		/* Add Subscription data from Recurly to Entry */
		$recurly_subscription = rgar( $subscription, 'recurly_subscription' );
		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: 'gform_post_subscription_started' hook running with 'recurly_subscription' and 'entry': ".print_r( $recurly_subscription, true ).print_r( $entry, true ) );
		GFRecurly_Utils::add_new_subscription_data( $recurly_subscription, $entry );
	}

	public function random_string( $length = 10 ) {

		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen( $characters );
		$randomString = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$randomString .= $characters[ rand( 0, $charactersLength - 1 ) ];
		}

		return $randomString;
	}
}
