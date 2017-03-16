<?php
class GFRecurly_Webhook_Process {

	protected static $_instance = null;

	protected $client = null;
	protected $gfpaymentaddon = null;

	public static function instance( $gfpaymentaddon ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $gfpaymentaddon );
		}

		return self::$_instance;
	}

	public function __construct( $gfpaymentaddon ) {

		require_once GF_RECURLY_DIR . 'includes/recurly-api/lib/recurly.php';

		$this->gfpaymentaddon = $gfpaymentaddon;

		$client = new Recurly_Client();
		$client::$subdomain = rgar( $this->gfpaymentaddon->get_plugin_settings(), 'gf_recurly_subdomain' );
		$client::$apiKey = rgar( $this->gfpaymentaddon->get_plugin_settings(), 'gf_recurly_api_key' );

		$this->client = $client;
	}

	public function process_event( $endpoint ){

		$post_xml = file_get_contents ( "php://input" );
		$notification = new Recurly_PushNotification( $post_xml, $this->client );
		//each webhook is defined by a type
		switch ($notification->type) {
		  case "successful_payment_notification":
		    /* process notification here */
		    break;
		  case "failed_payment_notification":
		    /* process notification here */
		    break;
		  /* add more notifications to process */
		}
	}
}
?>
