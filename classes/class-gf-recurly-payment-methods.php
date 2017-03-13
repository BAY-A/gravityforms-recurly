<?php

class GFRecurly_Payment_Methods{

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

	public function gform_payment_methods( $payment_methods, $field, $form_id ) {

		if ( is_user_logged_in() && GFRecurly_Utils::is_recurly_form( $form_id, $this->gfpaymentaddon ) ) {

			$skip       = false;
			$form_feeds = $this->gfpaymentaddon->get_active_feeds( $form_id );

			foreach ( $form_feeds as $feed ) {

				if ( 'updateBilling' == $feed[ 'meta' ][ 'type' ] ) {

					$skip = true;

				}

			}

			if ( ! $skip ) {

				$user_id             = get_current_user_id();
				$payment_method_list = GFP_More_Stripe_Helper::get_customer_payment_method_list( $user_id );

				if ( ! empty( $payment_method_list ) ) {

					$payment_methods = $payment_method_list;

				}

			}

		}

		return $payment_methods;
	}
}
