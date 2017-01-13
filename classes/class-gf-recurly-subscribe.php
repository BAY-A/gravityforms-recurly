<?php

class GFRecurly_Subscribe{

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

	public function subscribe( $feed, $submission_data, $form, $entry ) {

		$recurly_plan_code = trim( $feed['meta']['subscriptionPlan'] );

		$user_first_name = rgar( $submission_data, 'first_name' );
		$user_last_name = rgar( $submission_data, 'last_name' );
		$user_email = rgar( $submission_data, 'email' );

		try {
			$subscription = new Recurly_Subscription();
			$subscription->plan_code = $recurly_plan_code;
			$subscription->currency = 'USD';

			$account = new Recurly_Account();
			$account->account_code = $this->random_string( 49 );
			$account->email = $user_email;
			$account->first_name = $user_first_name;
			$account->last_name = $user_last_name;

			$billing_info = new Recurly_BillingInfo();
			$billing_info->token_id = 'XXXXXX'; // From Recurly.js

			$account->billing_info = $billing_info;
			$subscription->account = $account;

			$subscription->create();

			//print "Subscription: $subscription";
		} catch (Recurly_ValidationError $e) {
			$this->log_error( __METHOD__ . '(): Invalid Plan, Subscription, Account, or BillingInfo data; ' . $e->getMessage() );
		}
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
