<?php
if ( method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {

	require_once GF_RECURLY_DIR . 'includes/recurly-api/lib/recurly.php';

	GFForms::include_payment_addon_framework();

	class GFRecurly extends GFPaymentAddOn {

		protected $_version = GF_RECURLY_VERSION;
		protected $_min_gravityforms_version = '2.1.2';
		protected $_slug = 'gravityforms-recurly';
		protected $_path = 'gravityforms-recurly/recurly.php';
		protected $_full_path = __FILE__;
		protected $_title = 'Gravity Forms Recurly Add-On';
		protected $_short_title = 'Recurly';
		protected $_supports_callbacks = true;
		protected $_requires_credit_card = true;

		//Members plugin integration
		protected $_capabilities = array( 'gravityforms_recurly', 'gravityforms_recurly_uninstall' );

		// Permissions
		protected $_capabilities_settings_page = 'gravityforms_recurly';
		protected $_capabilities_form_settings = 'gravityforms_recurly';
		protected $_capabilities_uninstall = 'gravityforms_recurly_uninstall';

		private static $_instance = null;

		public static function get_instance() {
			if ( self::$_instance == null ) {
				self::$_instance = new GFRecurly();
			}

			return self::$_instance;
		}

		private function __clone() {
		} /* do nothing */

		public function frontend_script_callback( $form ) {

			return $form && $this->has_feed( $form['id'] ) && $this->has_credit_card_field( $form );
		}

		//Scripts
		public function scripts() {

			$settings = $this->get_plugin_settings();

			$scripts = array(
			array(
				'handle'  => 'recurly_js',
				'src'     => 'https://js.recurly.com/v4/recurly.js',
				'version' => $this->_version,
				'deps'    => array(),
				'strings' => array(
					'subdomain'  => rgar( $settings, 'gf_recurly_subdomain' ),
										'api_key' => rgar( $settings, 'gf_recurly_api_key' ),
				),
				'enqueue' => array(
					array( $this, 'frontend_script_callback' ),
				),
			),
			);

			return array_merge( parent::scripts(), $scripts );
		}

		//Plugin Settings Fields
		public function plugin_settings_fields() {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-plugin-settings-fields.php';

			$settings = $this->get_plugin_settings();
			return GFRecurly_Plugin_Settings_Fields::instance()->do_plugin_settings_fields( $settings );
		}

		//Feed Settings Fields
		public function feed_settings_fields() {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-feed-settings-fields.php';
			return GFRecurly_Feed_Settings_Fields::instance()->do_feed_settings_fields();
		}

		//Authorize Single Payment
		public function authorize( $feed, $submission_data, $form, $entry ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-feed-settings-fields.php';
			return GFRecurly_Feed_Settings_Fields::instance()->do_feed_settings_fields();
		}

		public function subscribe( $feed, $submission_data, $form, $entry ) {

			$recurly_plan_code = trim( $feed['meta']['subscriptionPlan'] );

			$user_first_name = rgar( $submission_data, 'first_name' );
			$user_last_name = rgar( $submission_data, 'last_name' );
			$user_email = rgar( $submission_data, 'email' );

			try {
				$subscription = new Recurly_Subscription();
				$subscription->plan_code = $recurly_plan_code;
				$subscription->currency = 'USD';

				$account = new Recurly_Account();
				$account->account_code = $this->random_string( 49 );
				$account->email = $user_email;
				$account->first_name = $user_first_name;
				$account->last_name = $user_last_name;

				$billing_info = new Recurly_BillingInfo();
				$billing_info->token_id = 'XXXXXX'; // From Recurly.js

				$account->billing_info = $billing_info;
				$subscription->account = $account;

				$subscription->create();

				print "Subscription: $subscription";
			} catch (Recurly_ValidationError $e) {
				print "Invalid Plan, Subscription, Account, or BillingInfo data: $e";
			}
		}

		public function random_string( $length = 10 ) {

			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$charactersLength = strlen( $characters );
			$randomString = '';

			for ( $i = 0; $i < $length; $i++ ) {
				$randomString .= $characters[ rand( 0, $charactersLength - 1 ) ];
			}

			return $randomString;
		}
	}
}
?>
