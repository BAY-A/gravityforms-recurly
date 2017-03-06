<?php
class GFRecurly_Process_Update_Subscription {

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

	public function process_update_subscription( $authorization, $feed, $submission_data, $form, $entry ) {

		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Running `process_update_subscription` function' );
	}
}
?>
