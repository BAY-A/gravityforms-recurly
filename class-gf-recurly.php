<?php
if ( method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {

	require_once GF_RECURLY_DIR . 'includes/recurly-api/lib/recurly.php';
	require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-data-io.php';
	require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-api-utils.php';
	require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-utils.php';

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

		//Init
		public function init() {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-init.php';
			GFRecurly_Init::instance( $this );

			add_action( 'gform_post_subscription_started', array( $this, 'gform_post_subscription_started' ), 10, 2 );
			add_action( 'gform_user_registered', array( $this, 'gform_user_registered' ), 10, 4 );
			add_action( 'gform_post_payment_completed', array( $this, 'gform_post_payment_completed' ), 10, 2 );

			parent::init();
		}

		//Scripts
		public function scripts() {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-scripts.php';
			$scripts = GFRecurly_Scripts::instance( $this )->scripts();

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

		//GFPaymentAddOn Subscribe
		public function subscribe( $feed, $submission_data, $form, $entry ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-subscribe.php';
			return GFRecurly_Subscribe::instance( $this )->subscribe( $feed, $submission_data, $form, $entry );
		}

		//GFPaymentAddOn Authorize
		public function authorize( $feed, $submission_data, $form, $entry ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-authorize.php';
			return GFRecurly_Authorize::instance( $this )->authorize( $feed, $submission_data, $form, $entry );
		}

		//GFPaymentAddOn Process Feed
		public function process_feed( $feed, $entry, $form ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-process-feed.php';
			return GFRecurly_Process_Feed::instance( $this )->process_feed( $feed, $entry, $form );
		}

		//Post Subscription started
		public function gform_post_subscription_started( $entry, $subscription ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-gform-post-subscription-started.php';
			return GFRecurly_Post_Subscription_Started::instance( $this )->gform_post_subscription_started( $entry, $subscription );
		}

		//User Registered
		public function gform_user_registered( $user_id, $feed, $entry, $password ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-gform-user-registered.php';
			return GFRecurly_User_Registered::instance( $this )->gform_user_registered( $user_id, $feed, $entry, $password );
		}

		//Post Payment Completed
		public function gform_post_payment_completed( $entry, $action ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-gform-post-payment-completed.php';
			return GFRecurly_Post_Payment_Completed::instance( $this )->gform_post_payment_completed( $entry, $action );
		}

		//JS Response
		public function get_recurly_js_response() {

			return json_decode( rgpost( 'recurly_response' ) );

		}

		//Add-on Version
		public function get_version() {

			return $this->_version;
		}
	}
}
?>
