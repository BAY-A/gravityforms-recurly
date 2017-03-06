<?php
class GFRecurly_Entry_Post_Save {

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

	public function gfrecurly_entry_post_save( $entry, $form ) {

		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Running `gfrecurly_entry_post_save` function' );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: entry: ' . print_r( $entry, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: form: ' . print_r( $form, true ) );

		if ( ! $this->gfpaymentaddon->getIsPaymentGateway() ) {
			return $entry;
		}

		$feed = $this->gfpaymentaddon->getCurrentFeed();

		if ( ! empty( $this->gfpaymentaddon->getAuthorization() ) ) {

			if ( 'updateBilling' === $feed['meta']['transactionType'] ) {

				$entry = $this->gfpaymentaddon->process_update_billing( $this->gfpaymentaddon->getAuthorization(), $feed, $this->gfpaymentaddon->getCurrentSubmissionData(), $form, $entry );

			} else if ( 'updateSubscription' === $feed['meta']['transactionType'] ) {

				$entry = $this->gfpaymentaddon->process_update_subscription( $this->gfpaymentaddon->getAuthorization(), $feed, $this->gfpaymentaddon->getCurrentSubmissionData(), $form, $entry );

			}
		}

		return $entry;
	}
}
?>
