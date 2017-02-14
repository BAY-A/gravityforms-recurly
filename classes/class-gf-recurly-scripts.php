<?php

class GFRecurly_Scripts{

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

	public function frontend_script_callback( $form ) {

		return $form && $this->gfpaymentaddon->has_feed( $form['id'] ) && $this->gfpaymentaddon->has_credit_card_field( $form );
	}

	public function scripts() {

		return array(
			array(
				'handle'  => 'gf_recurly_frontend',
				'src'     => $this->gfpaymentaddon->get_base_url() . '/js/frontend.js',
				'version' => $this->gfpaymentaddon->get_version(),
				'deps'    => array( 'jquery' ),
				'enqueue' => array(
					array( $this, 'frontend_script_callback' ),
				),
			),
		);
	}
}
