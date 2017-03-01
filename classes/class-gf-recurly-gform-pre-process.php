<?php

class GFRecurly_Pre_Process{

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

	public function gform_pre_process( $form ) {

		$form_id = rgar( $form, 'id' );
		$active_recurly_feeds = $this->gfpaymentaddon->get_active_feeds( $form_id );

		$prevent_user_reg_if_logged_in = GFRecurly_Utils::findFeedMetaKeyAndValue( $active_recurly_feeds, 'preventUserRegistrationIfLoggedIn', 1 );

		if ( $prevent_user_reg_if_logged_in && is_user_logged_in() && function_exists( 'gf_user_registration' ) && gf_user_registration()->has_feed_type( 'create', $form ) ) {

			remove_filter( 'gform_validation', array( gf_user_registration(), 'validate' ) );
		}
	}
}
