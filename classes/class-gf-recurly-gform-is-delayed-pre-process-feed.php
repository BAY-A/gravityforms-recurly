<?php

class GFRecurly_Is_Delayed_Pre_Process_Feed{

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

	public function gform_is_delayed_pre_process_feed( $is_delayed, $form, $entry, $slug ) {

		$form_id = rgar( $form, 'id' );
		$active_recurly_feeds = $this->gfpaymentaddon->get_active_feeds( $form_id );

		$prevent_user_reg_if_logged_in = GFRecurly_Utils::findFeedMetaKeyAndValue( $active_recurly_feeds, 'preventUserRegistrationIfLoggedIn', 1 );

		if ( $prevent_user_reg_if_logged_in && is_user_logged_in() && $slug === 'gravityformsuserregistration' ) {

			return gf_user_registration()->has_feed_type( 'create', $form );
		}

		return $is_delayed;
	}
}
