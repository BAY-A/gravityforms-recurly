<?php

class GFRecurly_API_Wrapper {

	protected static $_instance = null;

	protected $client = null;
	protected $gfpaymentaddon = null;

	public static function instance( $gfpaymentaddon ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $gfpaymentaddon );
		}

		return self::$_instance;
	}

	/* Construct */
	public function __construct( $gfpaymentaddon ) {

		require_once GF_RECURLY_DIR . 'includes/recurly-api/lib/recurly.php';

		$this->gfpaymentaddon = $gfpaymentaddon;
		$this->client = new Recurly_Client();

		$this->client::$subdomain = rgar( $this->gfpaymentaddon->get_plugin_settings(), 'gf_recurly_subdomain' );
		$this->client::$apiKey = rgar( $this->gfpaymentaddon->get_plugin_settings(), 'gf_recurly_api_key' );
	}

	/* Create Subscription */
	public function create_subscription( $account_code, $plan_code = '', $currency = 'USD' ) {

		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: 'create_subscription' function. Params: {$account_code}, {$plan_code}, {$currency}" );
		if ( empty( $account_code ) || empty( $plan_code ) ) {

			return false;
		}

		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Account code and Plan code exist" );

		$account = $this->maybe_get_account( $account_code );

		if ( ! $account || ! is_object( $account->billing_info ) ) {

			return false;
		}

		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Account exists: ".print_r( $account, true ) );

		try {
			$subscription = new Recurly_Subscription( null, $this->client );
			$subscription->plan_code = $plan_code;
			$subscription->currency = $currency;

			$subscription->account = $account;
			$subscription->create();

			$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Created subscription" );
			return $subscription;

		} catch ( Recurly_ValidationError $e ) {}

		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Could not create subscription" );
		return false;
	}

	/* Create Account */
	public function create_account( $account_code = -1, $account_email = '', $account_first_name = '', $account_last_name = '', $billing_info = false ) {

		/* Make sure this account id doesn't already exist, amongst our other checks */
		if ( false !== $this->maybe_get_account( $account_code ) || -1 === $account_code || empty( $account_email ) || empty( $account_first_name ) || empty( $account_last_name ) ) {

			return false;
		}

		try {

			$account = new Recurly_Account( $account_code, $this->client );
			$account->email = $account_email;
			$account->first_name = $account_first_name;
			$account->last_name = $account_last_name;

			if ( is_array( $billing_info ) ) {

				$acc_address = new Recurly_Address( null, $this->client );
				$acc_address->address1 = $billing_info['address_one'];
				$acc_address->address2 = $billing_info['address_two'];
				$acc_address->city = $billing_info['city'];
				$acc_address->state = $billing_info['state'];
				$acc_address->zip = $billing_info['zip'];
				$acc_address->country = $billing_info['country'];
				$acc_address->phone = $billing_info['phone'];

				$account->address = $acc_address;

				$account->billing_info = $this->create_billinginfo(
																	$account_code,
																	$billing_info['first_name'],
																	$billing_info['last_name'],
																	$billing_info['number'],
																	$billing_info['verification_value'],
																	$billing_info['month'],
																	$billing_info['year'],
																	$billing_info['ip_address'],
																	$billing_info['address_one'],
																	$billing_info['address_two'],
																	$billing_info['city'],
																	$billing_info['state'],
																	$billing_info['country'],
																	$billing_info['zip'],
																	false
																);
			}

			$account->create();
			$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Created account" );

			return $account;

		} catch ( Recurly_ValidationError $e ) {
			//$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Error: {$e}" );
		}

		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Could not create account" );
		return false;
	}

	/* Update Account Billing Information */
	public function update_account_billing_information( $account_code, $first_name, $last_name, $number, $verification_value, $month, $year, $ip_address, $address_one, $address_two, $city, $state, $country, $zip ) {

		if ( ! $this->maybe_get_account( $account_code ) || ! $billing_info || ! is_object( $billing_info ) ) {

			return false;
		}

		try {

			$account = new Recurly_Account( $account_code, $this->client );
			$account->billing_info = $this->create_billinginfo(
																$account_code,
																$first_name,
																$last_name,
																$number,
																$verification_value,
																$month,
																$year,
																$ip_address,
																$address_one,
																$address_two,
																$city,
																$state,
																$country,
																$zip,
																false
															);

			$account->update();

			$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Updated account billing information" );
			return $account;

		} catch ( Recurly_ValidationError $e ) {}

		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Could not update account billing information" );
		return false;
	}

	public function create_billinginfo(
		$account_code = -1,
		$first_name = '',
		$last_name = '',
		$number = -1,
		$verification_value = -1,
		$month = -1,
		$year = -1,
		$ip_address = '',
		$address_one = '',
		$address_two = '',
		$city = '',
		$state = '',
		$country = '',
		$zip = '',
		$create_billing = false
	) {

		if ( -1 === $account_code || empty( $first_name ) || empty( $last_name ) || -1 === $number || -1 === $verification_value || -1 === $month || -1 === $year ) {

			return false;
		}

		try {

			$billing_info = new Recurly_BillingInfo( $account_code, $this->client );

			$billing_info->first_name = $first_name;
			$billing_info->last_name = $last_name;
			$billing_info->number = $number;
			$billing_info->verification_value = $verification_value;
			$billing_info->month = $month;
			$billing_info->year = $year;
			$billing_info->ip_address = $ip_address ?: '';
			$billing_info->address1 = $address_one ?: '';
			$billing_info->address2 = $address_two ?: '';
			$billing_info->city = $city ?: '';
			$billing_info->state = $state ?: '';
			$billing_info->country = $country ?: '';
			$billing_info->zip = $zip ?: '';

			if ( $create_billing ) {

				$billing_info->create();
			}

			$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Created account billing information" );
			return $billing_info;

		} catch ( Recurly_ValidationError $e ) {}

		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Could not create acconut billing information" );
		return false;
	}

	public function maybe_get_account( $account_code = -1 ) {

		if ( -1 === $account_code ) {

			return false;
		}

		try {

			$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Getting account" );
			return Recurly_Account::get( $account_code, $this->client );

		} catch ( Recurly_NotFoundError $e ) {}

		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Could not get account (doesn't exist)" );
		return false;
	}
}
