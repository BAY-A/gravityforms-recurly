<?php
class GFRecurly_Validation {

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

	public function validation( $validation_result ) {

		if ( ! $validation_result['is_valid'] ) {
			return $validation_result;
		}

		$form  = $validation_result['form'];
		$entry = GFFormsModel::create_lead( $form );
		$feed  = $this->gfpaymentaddon->get_payment_feed( $entry, $form );

		if ( ! $feed ) {
			return $validation_result;
		}

		$submission_data = $this->gfpaymentaddon->get_submission_data( $feed, $form, $entry );

		$this->gfpaymentaddon->isPaymentGateway( true );
		$this->gfpaymentaddon->currentFeed( $feed );
		$this->gfpaymentaddon->singleSubmissionFeed( $feed );
		$this->gfpaymentaddon->currentSubmissionData( $submission_data );

		$performed_authorization = false;
		$feedType = $feed['meta']['transactionType'];

		if ( 'updateBilling' === $feedType ) {

			$updatedBillingInfo = $this->gfpaymentaddon->update_billing_information( $feed, $submission_data, $form, $entry );

			$this->gfpaymentaddon->updateAuthorizationProperty( 'is_authorized', rgar( $updatedBillingInfo,'is_success' ) );
			$this->gfpaymentaddon->updateAuthorizationProperty( 'error_message', rgar( $updatedBillingInfo,'error_message' ) );
			$this->gfpaymentaddon->updateAuthorizationProperty( 'billing_information', rgar( $updatedBillingInfo,'billing_information' ) );

			$performed_authorization = true;

		} elseif ( 'updateSubscription' === $feedType ) {

			$updatedSubscription = $this->gfpaymentaddon->update_subscription( $feed, $submission_data, $form, $entry );

			$this->gfpaymentaddon->updateAuthorizationProperty( 'is_authorized', rgar( $updatedSubscription,'is_success' ) );
			$this->gfpaymentaddon->updateAuthorizationProperty( 'error_message', rgar( $updatedSubscription,'error_message' ) );
			$this->gfpaymentaddon->updateAuthorizationProperty( 'recurly_subscription', rgar( $updatedSubscription,'recurly_subscription' ) );

			$performed_authorization = true;
		}

		if ( $performed_authorization ) {
			$this->gfpaymentaddon->log_debug( __METHOD__ . "(): Authorization result for form #{$form['id']} submission => " . print_r( $this->gfpaymentaddon->getAuthorization(), 1 ) );
		}

		if ( $performed_authorization && ! rgar( $this->gfpaymentaddon->getAuthorization(), 'is_authorized' ) ) {
			$validation_result = $this->gfpaymentaddon->get_validation_result( $validation_result, $this->gfpaymentaddon->getAuthorization() );

			//Setting up current page to point to the credit card page since that will be the highlighted field
			GFFormDisplay::set_current_page( $validation_result['form']['id'], $validation_result['credit_card_page'] );
		}

		return $this->gfpaymentaddon->validationParent( $validation_result );
	}
}
?>
