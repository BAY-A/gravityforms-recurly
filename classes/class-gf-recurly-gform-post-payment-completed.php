<?php

class GFRecurly_Post_Payment_Completed{

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

	public function gform_post_payment_completed( $entry, $action ) {

		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Starting gform_post_payment_completed function' );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: entry: '.print_r( $entry, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: action: '.print_r( $action, true ) );

		if ( 'payment' === rgar( $action, 'transaction_type' ) && rgar( $action, 'recurly_transaction' ) ) {

			$recurly_transaction = rgar( $action, 'recurly_transaction' );
			$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: 'gform_post_payment_completed' hook running with 'recurly_transaction' ".print_r( $recurly_transaction, true ) );
			GFRecurly_Utils::add_new_single_payment_data( $recurly_transaction, $entry );
		}
	}
}
