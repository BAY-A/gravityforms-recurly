<?php
class GFRecurly_Webhook_Handler {

	protected static $_instance = null;
	protected $gfpaymentaddon = null;
	protected $recurly_webhook_ips = null;

	public static function instance( $gfpaymentaddon ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $gfpaymentaddon );
		}

		return self::$_instance;
	}

	public function __construct( $gfpaymentaddon ) {

		$this->gfpaymentaddon = $gfpaymentaddon;
		$this->recurly_webhook_ips = array(
			'50.18.192.88',
			'52.8.32.100',
			'52.9.209.233',
			'50.0.172.150',
			'52.203.102.94',
			'52.203.192.184'
		);
	}

	public function parse_request(){

		if ( class_exists( 'GFForms' ) ) {

			$endpoint = GFForms::get( 'page' );

			/*
			* @todo
			* !!NEED TO CHECK WHETHER SOURCE IP MATCHES THE LEGIT RECURLY IPS!!
			*/
			if ( 'recurly_listener' == $endpoint ) {

				require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-webhook-process.php';
				return GFRecurly_Webhook_Process::instance( $this->gfpaymentaddon )->process_event( $endpoint );
			}

		}

		return;
	}
}
?>
