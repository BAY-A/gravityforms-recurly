<?php
class GFRecurly_Process_Update_Billing {

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

	public function process_update_billing( $authorization, $feed, $submission_data, $form, $entry ) {

		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Running `process_update_billing` function' );

		$this->gfpaymentaddon->log_error( "(): Updating entry #{$entry['id']} with result => " . print_r( $authorization, 1 ) );

		if ( $authorization['is_authorized'] ) {

			$entry['is_fulfilled']     = '1';
			$authorization['billing_update_status'] = 'Updated';
			$authorization['billing_update_date']   = gmdate( 'Y-m-d H:i:s' );
			$authorization['type']           = 'update_billing';
			$this->complete_update_billing( $entry, $authorization, $feed );

		} else {

			$entry['billing_update_status'] = 'Failed';
			$authorization['type']         = 'fail_update_billing';
			$authorization['note']         = sprintf( esc_html__( 'Billing information failed to be updated. Reason: %s', $this->gfpaymentaddon->slug ), $authorization['error_message'] );
			$this->fail_update_billing( $entry, $authorization, $feed );

		}

		return $entry;
	}

	public function complete_update_billing( $entry, $authorization, $feed ) {

		$this->gfpaymentaddon->log_error( __METHOD__ . '(): Processing request.' );
		if ( ! rgar( $authorization, 'billing_update_status' ) ) {
			$authorization['billing_update_status'] = 'Updated';
		}

		if ( ! rgar( $authorization, 'transaction_type' ) ) {
			$authorization['transaction_type'] = 'update_billing';
		}

		if ( ! rgar( $authorization, 'billing_update_date' ) ) {
			$authorization['billing_update_date'] = gmdate( 'y-m-d H:i:s' );
		}

		$entry['is_fulfilled']     = '1';
		$entry['billing_update_status']   = $authorization['billing_update_status'];
		$entry['billing_update_date']     = $authorization['billing_update_date'];
		$entry['billing_update_method']   = rgar( $authorization, 'billing_update_method' );

		if ( ! rgar( $authorization, 'note' ) ) {
			$authorization['note']   = esc_html__( 'Billing information updated.', $this->gfpaymentaddon->slug );
		}

		GFAPI::update_entry( $entry );
		$this->gfpaymentaddon->insert_transaction( $entry['id'], $authorization['transaction_type'], $authorization['transaction_id'], $authorization['amount'] );
		$this->gfpaymentaddon->add_note( $entry['id'], $authorization['note'], 'success' );

		do_action( 'gfrecurly_post_update_billing_completed', $entry, $authorization, $feed );
		if ( has_filter( 'gfrecurly_post_update_billing_completed' ) ) {
			$this->gfpaymentaddon->log_error( __METHOD__ . '(): Executing functions hooked to gfrecurly_post_update_billing_completed.' );
		}
		$this->post_update_billing_action( $entry, $authorization );

		return true;
	}

	public function fail_update_billing( $entry, $action, $feed ) {

		$this->gfpaymentaddon->log_error( __METHOD__ . '(): Processing request.' );

		if ( empty( $action['billing_update_status'] ) ) {
			$action['billing_update_status'] = 'Failed';
		}

		if ( empty( $action['note'] ) ) {
			$action['note']   = esc_html__( 'Billing information update has failed.', $this->gfpaymentaddon->slug );
		}

		GFAPI::update_entry_property( $entry['id'], 'billing_update_status', $action['payment_status'] );
		$this->gfpaymentaddon->add_note( $entry['id'], $action['note'] );
		$this->post_update_billing_action( $entry, $action );

		return true;
	}

	public function post_update_billing_action( $entry, $action ) {

		do_action( 'gfrecurly_post_update_billing_action', $entry, $action );
		if ( has_filter( 'gfrecurly_post_update_billing_action' ) ) {
			$this->gfpaymentaddon->log_error( __METHOD__ . '(): Executing functions hooked to gfrecurly_post_update_billing_action.' );
		}



		$form             = GFAPI::get_form( $entry['form_id'] );
		$supported_events = $this->gfpaymentaddon->supported_notification_events( $form );
		if ( ! empty( $supported_events ) ) {
			GFAPI::send_notifications( $form, $entry, rgar( $action, 'type' ) );
		}
	}
}
?>
