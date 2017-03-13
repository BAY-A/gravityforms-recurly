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
			add_action( 'gfrecurly_post_update_billing_completed', array( $this, 'gfrecurly_post_update_billing_completed' ), 10, 3 );
			add_filter( 'gform_is_delayed_pre_process_feed', array( $this, 'gform_is_delayed_pre_process_feed' ), 10, 4 );
			add_action( 'gform_pre_process', array( $this, 'gform_pre_process' ) );
			add_filter( 'gform_entry_post_save', array( $this, 'gfrecurly_entry_post_save' ), 11, 2 );
			add_filter( 'gform_payment_methods', array( $this, 'gform_payment_methods' ), 10, 3 );

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

		//Post Update Billing Completed
		public function gfrecurly_post_update_billing_completed( $entry, $action, $feed ){

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-gform-post-update-billing-completed.php';
			return GFRecurly_Post_Update_Billing_Completed::instance( $this )->gform_post_update_billing_completed( $entry, $action, $feed );
		}

		//Is Delayed Pre Process Feed?
		public function gform_is_delayed_pre_process_feed( $is_delayed, $form, $entry, $slug ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-gform-is-delayed-pre-process-feed.php';
			return GFRecurly_Is_Delayed_Pre_Process_Feed::instance( $this )->gform_is_delayed_pre_process_feed( $is_delayed, $form, $entry, $slug );
		}

		//Pre Process
		public function gform_pre_process( $form ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-gform-pre-process.php';
			return GFRecurly_Pre_Process::instance( $this )->gform_pre_process( $form );
		}

		//Get Column Value Transaction Type (Child Function)
		public function get_column_value_transactionType( $feed ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-get-column-value-transaction-type.php';
			return GFRecurly_Get_Column_Value_TransactionType::instance( $this )->get_column_value_transactionType( $feed );
		}

		//Get Column Value Transaction Type Parent Function
		public function get_column_value_transactionTypeParent( $feed ) {

			return parent::get_column_value_transactionType( $feed );
		}

		//Validation (Child Function)
		public function validation( $validation_result ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-validation.php';
			return GFRecurly_Validation::instance( $this )->validation( $validation_result );
		}

		//Validation Parent
		public function validationParent( $validation_result ) {

			return parent::validation( $validation_result );
		}

		//Update Subscription
		public function update_subscription( $feed, $submission_data, $form, $entry ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-update-subscription.php';
			return GFRecurly_Update_Subscription::instance( $this )->update_subscription( $feed, $submission_data, $form, $entry );
		}

		//Update Billing Information
		public function update_billing_information( $feed, $submission_data, $form, $entry ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-update-billing-information.php';
			return GFRecurly_Update_Billing_Information::instance( $this )->update_billing_information( $feed, $submission_data, $form, $entry );
		}

		//Entry Post Save
		public function gfrecurly_entry_post_save( $entry, $form ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-entry-post-save.php';
			return GFRecurly_Entry_Post_Save::instance( $this )->gfrecurly_entry_post_save( $entry, $form );
		}

		//Process Update Billing
		public function process_update_billing( $authorization, $feed, $submission_data, $form, $entry ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-process-update-billing.php';
			return GFRecurly_Process_Update_Billing::instance( $this )->process_update_billing( $authorization, $feed, $submission_data, $form, $entry );
		}

		//Process Update Subscription
		public function process_update_subscription( $authorization, $feed, $submission_data, $form, $entry ) {

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-process-update-subscription.php';
			return GFRecurly_Process_Update_Subscription::instance( $this )->process_update_subscription( $authorization, $feed, $submission_data, $form, $entry );
		}

		//Payment Methods
		public function gform_payment_methods( $payment_methods, $field, $form_id ){

			require_once GF_RECURLY_DIR . 'classes/class-gf-recurly-payment-methods.php';
			return GFRecurly_Payment_Methods::instance( $this )->gform_payment_methods( $payment_methods, $field, $form_id );
		}

		//Update 'Is Payment Gateway?'
		public function isPaymentGateway( $is_gateway = false ) {

			$this->is_payment_gateway = $is_gateway;
		}

		//Get 'Is Payment Gateway?'
		public function getIsPaymentGateway() {

			return $this->is_payment_gateway;
		}

		//Update 'Single Submission Feed'
		public function singleSubmissionFeed( $feed = false ) {

			$this->_single_submission_feed = $feed;
		}

		//Update 'Current Feed'
		public function currentFeed( $feed = false ) {

			$this->current_feed = $feed;
		}

		//Get 'Current Feed'
		public function getCurrentFeed() {

			return $this->current_feed;
		}

		//Update 'Current Submission Data'
		public function currentSubmissionData( $submission_data = false ) {

			$this->current_submission_data = $submission_data;
		}

		//Get 'Current Submission Data'
		public function getCurrentSubmissionData() {

			return $this->current_submission_data;
		}

		//Get Authorization
		public function getAuthorization() {

			return $this->authorization;
		}

		//Update Authorization Property
		public function updateAuthorizationProperty( $auth_key, $auth_val ) {

			$this->authorization[ $auth_key ] = $auth_val;
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
