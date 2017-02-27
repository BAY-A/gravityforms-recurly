<?php

class GFRecurly_Process_Feed{

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

	public function process_feed( $feed, $entry, $form ) {

		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Begin process_feed' );

		if ( GFRecurly_Utils::are_plugin_settings_entered( $this->gfpaymentaddon ) ) {

			switch ( rgars( $feed, 'meta/transactionType' ) ) {

				case 'product':
				case 'subscription':
					$this->maybe_process_product_related( $feed, $entry, $form );
					break;

				default:
					$this->standard_processing( $feed, $entry, $form );
					break;

			}
		} else {

			$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Conditions not met to process feed' );
			return;
		}
	}

	public function maybe_process_product_related( $feed, $entry, $form ) {

		$entry_id = rgar( $entry, 'id' );
		$form_id = rgar( $form, 'id' );

		if ( 1 == rgars( $feed, 'meta/autoLoginNewUsers' ) ) {

			gform_update_meta( $entry_id, 'autoLoginNewUsers', true );
		}

		return $this->standard_processing( $feed, $entry, $form );
	}

	public function standard_processing( $feed, $entry, $form ) {

		$entry_id = rgar( $entry, 'id' );

		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Feeds processed: ' . print_r( $feed, true ) . print_r( $entry, true ) . print_r( $form, true ) );

		gform_update_meta( $entry_id, 'transactionType', rgars( $feed, 'meta/transactionType' ) );

		return $entry;
	}
}
