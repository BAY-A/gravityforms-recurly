<?php
class GFRecurly_Update_Subscription {

	protected static $_instance = null;
	protected $gfpaymentaddon = null;
	protected $gfrecurlyapi = null;

	public static function instance( $gfpaymentaddon ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $gfpaymentaddon );
		}

		return self::$_instance;
	}

	public function __construct( $gfpaymentaddon ) {

		require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-api-wrapper.php';

		$this->gfpaymentaddon = $gfpaymentaddon;
		$this->gfrecurlyapi = GFRecurly_API_Wrapper::instance( $gfpaymentaddon );
	}

	public function update_subscription( $feed, $submission_data, $form, $entry ) {

		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Starting update_subscription function' );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: feed: '.print_r( $feed, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: submission_data: '.print_r( $submission_data, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: form: '.print_r( $form, true ) );
		$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: entry: '.print_r( $entry, true ) );

		if ( ! is_user_logged_in() ) {

			$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: User not logged in - Aborting' );
			return array(
				'is_success' => false,
				'error_message' => 'You must be logged-in to be able to update your subscription.',
			);
		}

		$user_id = get_current_user_id();

		if ( ! GFRecurly_Utils::does_user_have_a_recurly_account( $user_id ) ) {

			$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: User has no Recurly account - Aborting' );
			return array(
				'is_success' => false,
				'error_message' => 'There isn\'t a Recurly account associated with your WordPress account.',
			);
		}

		/* Get Feed Meta */
		$feed_meta = rgar( $feed, 'meta' );

		/* Get Recurly Account Code */
		$recurly_account_code = GFRecurly_Utils::get_recurly_account_code( $user_id );

		/* Get Recurly Plan Code */
		$recurly_plan_entry_id = rgar( $feed_meta, 'subscriptionPlan' );
		$recurly_plan_code = trim( rgar( $entry, $recurly_plan_entry_id ) );
		$recurly_plan_code = strpos( $recurly_plan_code, '|' ) !== false ? explode( '|', $recurly_plan_code )[0] : $recurly_plan_code;

		/* Set entry IDs */
		$first_name_entry_id = rgar( $feed_meta, 'billingInformation_first_name' );
		$last_name_entry_id = rgar( $feed_meta, 'billingInformation_last_name' );

		/* User Information */
		$user_first_name = rgar( $entry, $first_name_entry_id );
		$user_last_name = rgar( $entry, $last_name_entry_id );
		$user_email = rgar( $submission_data, 'email' );
		$user_ip = rgar( $entry, 'ip' );

		/* User Address */
		$address_1 = rgar( $submission_data, 'address' );
		$address_2 = rgar( $submission_data, 'address2' );
		$address_city = rgar( $submission_data, 'city' );
		$address_state = rgar( $submission_data, 'state' );
		$address_zip = rgar( $submission_data, 'zip' );
		$address_country = rgar( $submission_data, 'country' );

		/* Credit Card */
		$cc_number = rgar( $submission_data, 'card_number' );
		$cc_exp_date = rgar( $submission_data, 'card_expiration_date' );
		$cc_code = rgar( $submission_data, 'card_security_code' );
		$cc_name = rgar( $submission_data, 'card_name' );

		try {

			$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Trying to update Subscription' );

			$subscription_ids = GFRecurly_Utils::get_active_recurly_subscriptions( $user_id );

			if ( empty( $subscription_ids ) ) {

				return array(
					'is_success' => false,
					'error_message' => 'Recurly error: No stored subscriptions found for WordPress user',
				);
			}

			reset( $subscription_ids );
			$first_sub_key = key( $subscription_ids );
			$subscription_id = $subscription_ids[ $first_sub_key ];

			$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Stored user subscriptions: ' . print_r( $subscription_ids, true ) );
			$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Stored user subscription ID: {$subscription_id}" );

			$recurly_subscription_terminated = $this->gfrecurlyapi->terminate_subscription( $subscription_id );

			if ( $recurly_subscription_terminated ) {

				unset( $subscription_ids[$first_sub_key] );
				update_user_meta( $user_id, 'recurly_account_transactions', $subscription_ids );

				$this->gfpaymentaddon->log_error( "Gravity Forms + Recurly: Recurly subscription {$first_sub_key} with ID {$subscription_id} terminated for user - Switching to new one" );

				try {
					$recurly_subscription = $this->gfrecurlyapi->create_subscription( $recurly_account_code, $recurly_plan_code, 'USD' );
					$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: recurly_subscription object: ' . print_r( $recurly_subscription, true ) );
					/*
					//Run 'save_recurly_info_to_wp_user'
					GFRecurly_Utils::save_recurly_info_wp_user( $user_id, $entry_id, $transaction_type_label, $recurly_object, $last_four );

					//Then run 'add_customer_metadata'
					GFRecurly_Utils::add_customer_metadata( $user_id, $entry_id, $recurly_object );
					*/
					return array(
						'is_success' => true,
						'recurly_subscription' => $recurly_subscription,
					);
				} catch (Exception $e) {

					$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Error: ' . $e->getMessage() );
					return array(
						'is_success' => false,
						'error_message' => 'Recurly error: ' . $e->getMessage(),
					);
				}
			}
		} catch (Exception $e) {

			$this->gfpaymentaddon->log_error( 'Gravity Forms + Recurly: Error: ' . $e->getMessage() );
			return array(
				'is_success' => false,
				'error_message' => 'Recurly error: ' . $e->getMessage(),
			);
		}
	}
}
?>
