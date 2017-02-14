<?php

class GFRecurly_Init{

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

		add_filter( 'gform_register_init_scripts', array( $this, 'register_init_scripts' ), 10, 3 );
	}

	public function register_init_scripts( $form, $field_values, $is_ajax ) {

		// If form does not have a Recurly feed and does not have a credit card field, exit.
		if ( ! $this->gfpaymentaddon->has_feed( $form['id'] ) ) {
			return;
		}

		$cc_field = $this->gfpaymentaddon->get_credit_card_field( $form );

		if ( ! $cc_field ) {
			return;
		}

		// Prepare Recurly Javascript arguments.
		$settings = $this->gfpaymentaddon->get_plugin_settings();
		$args = array(
			'subdomain'  => rgar( $settings, 'gf_recurly_subdomain' ),
			'api_key' => rgar( $settings, 'gf_recurly_api_key' ),
			'formId'     => $form['id'],
			'ccFieldId'  => $cc_field->id,
			'ccPage'     => $cc_field->pageNumber,
			'isAjax'     => $is_ajax,
		);

		// Initialize Recurly script.
		$script = 'new GFRecurly( ' . json_encode( $args ) . ' );';

		// Add Recurly script to form scripts.
		GFFormDisplay::add_init_script( $form['id'], 'recurly', GFFormDisplay::ON_PAGE_RENDER, $script );

	}

	public function table_exists( $table_name ) {

		$table_exists = true;

		global $wpdb;

		// Check that the table exists
		if ( 0 == $wpdb->query( "SHOW TABLES LIKE '" . $table_name . "'" ) ) {

			$table_exists = false;
		}

		return $table_exists;

	}
}
