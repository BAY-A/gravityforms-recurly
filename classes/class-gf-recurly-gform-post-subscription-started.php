<?php

class GFRecurly_Post_Subscription_Started{

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

	public function gform_post_subscription_started( $entry, $subscription ) {

		/* Add Subscription data from Recurly to Entry */
		$recurly_subscription = rgar( $subscription, 'recurly_subscription' );
		$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: 'gform_post_subscription_started' hook running with 'recurly_subscription' and 'entry': ".print_r( $recurly_subscription, true ).print_r( $entry, true ) );
		GFRecurly_Utils::add_new_subscription_data( $recurly_subscription, $entry );
	}
}
