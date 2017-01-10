<?php

class GFRecurly_API_Wrapper {

	protected static $_instance = null;

	protected $client = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {

		require_once GF_RECURLY_DIR . 'includes/recurly-api/lib/recurly.php';

		$this->client = new Recurly_Client();

		$this->client::$subdomain = rgar( $settings, 'gf_recurly_subdomain' );
		$this->client::$apiKey = rgar( $settings, 'gf_recurly_api_key' );
	}
}
