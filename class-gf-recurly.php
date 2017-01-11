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

		public function init() {

			add_filter( 'gform_register_init_scripts', array( $this, 'register_init_scripts' ), 10, 3 );
			add_filter( 'gform_field_content', array( $this, 'add_recurly_attributes' ), 10, 5 );

			parent::init();

		}

		public function register_init_scripts( $form, $field_values, $is_ajax ) {

			// If form does not have a Stripe feed and does not have a credit card field, exit.
			if ( ! $this->has_feed( $form['id'] ) ) {
				return;
			}

			$cc_field = $this->get_credit_card_field( $form );

			if ( ! $cc_field ) {
				return;
			}

			// Prepare Stripe Javascript arguments.
			$settings = $this->get_plugin_settings();
			$args = array(
				'subdomain'  => rgar( $settings, 'gf_recurly_subdomain' ),
				'api_key' => rgar( $settings, 'gf_recurly_api_key' ),
				'formId'     => $form['id'],
				'ccFieldId'  => $cc_field->id,
				'ccPage'     => $cc_field->pageNumber,
				'isAjax'     => $is_ajax,
				'cardLabels' => $this->get_card_labels(),
			);

			// Initialize Stripe script.
			$script = 'new GFRecurly( ' . json_encode( $args ) . ' );';

			// Add Stripe script to form scripts.
			GFFormDisplay::add_init_script( $form['id'], 'recurly', GFFormDisplay::ON_PAGE_RENDER, $script );

		}

		public function add_recurly_attributes( $content, $field, $value, $lead_id, $form_id ){

			//print_r( '<pre>'.print_r( $content, true ).'</pre>' );
			//print_r( '<pre>'.print_r( $field, true ).'</pre>' );
			//print_r( '<pre>'.print_r( $value, true ).'</pre>' );
			//print_r( '<pre>'.print_r( $lead_id, true ).'</pre>' );
			//print_r( '<pre>'.print_r( $form_id, true ).'</pre>' );

			switch( $field->type ) {
		    case 'name':
					foreach( $field->inputs as $field_input ){

						if( $field->id.'.3' == $field_input['id'] ){
							$content = str_replace( "name='input_".$field_input['id']."'", "data-recurly='first_name' name='input_".$field_input['id']."'", $content );
						}
						if( $field->id.'.6' == $field_input['id'] ){
							$content = str_replace( "name='input_".$field_input['id']."'", "data-recurly='last_name' name='input_".$field_input['id']."'", $content );
						}
					}
		      break;
		    case 'address':
					foreach( $field->inputs as $field_input ){

						if( $field->id.'.1' == $field_input['id'] ){
							$content = str_replace( "name='input_".$field_input['id']."'", "data-recurly='address1' name='input_".$field_input['id']."'", $content );
						}
						if( $field->id.'.2' == $field_input['id'] ){
							$content = str_replace( "name='input_".$field_input['id']."'", "data-recurly='address2' name='input_".$field_input['id']."'", $content );
						}
						if( $field->id.'.3' == $field_input['id'] ){
							$content = str_replace( "name='input_".$field_input['id']."'", "data-recurly='city' name='input_".$field_input['id']."'", $content );
						}
						if( $field->id.'.4' == $field_input['id'] ){
							$content = str_replace( "name='input_".$field_input['id']."'", "data-recurly='state' name='input_".$field_input['id']."'", $content );
						}
						if( $field->id.'.5' == $field_input['id'] ){
							$content = str_replace( "name='input_".$field_input['id']."'", "data-recurly='postal_code' name='input_".$field_input['id']."'", $content );
						}
						if( $field->id.'.6' == $field_input['id'] ){
							$content = str_replace( "name='input_".$field_input['id']."'", "data-recurly='country' name='input_".$field_input['id']."'", $content );
						}
					}
		      break;
		    case 'creditcard':
					foreach( $field->inputs as $field_input ){

						if( $field->id.'.1' == $field_input['id'] ){
							$content = str_replace( "name='input_".$field_input['id']."'", "data-recurly='number' name='input_".$field_input['id']."'", $content );
						}
						if( $field->id.'.2_month' == $field_input['id'] ){
							$content = str_replace( "id='input_".$form_id.'_'.$field->id."_2_month'", "onchange='updateHiddenCCFieldVersions( jQuery(this) )' id='input_".$form_id.'_'.$field->id."_2_month'", $content );
							$content .= '<input data-recurly="month" type="hidden" name="input_'.$form_id.'_'.$field->id.'_2_month_hidden" id="input_'.$form_id.'_'.$field->id.'_2_month_hidden" value="'.( $value[$field_input['id']] ? $value[$field_input['id']] : 0 ).'" />';
						}
						if( $field->id.'.2_year' == $field_input['id'] ){
							$content = str_replace( "id='input_".$form_id.'_'.$field->id."_2_year'", "onchange='updateHiddenCCFieldVersions( jQuery(this) )' id='input_".$form_id.'_'.$field->id."_2_year'", $content );
							$content .= '<input data-recurly="year" type="hidden" name="input_'.$form_id.'_'.$field->id.'_2_year_hidden" id="input_'.$form_id.'_'.$field->id.'_2_year_hidden" value="'.( $value[$field_input['id']] ? $value[$field_input['id']] : 0 ).'" />';
						}
						if( $field->id.'.3' == $field_input['id'] ){
							$content = str_replace( "name='input_".$field_input['id']."'", "data-recurly='cvv' name='input_".$field_input['id']."'", $content );
						}
					}
		      break;
			}
			return $content;
		}

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
					'enqueue' => array(
						array( $this, 'frontend_script_callback' ),
					),
				),
				array(
					'handle'  => 'gf_recurly_frontend',
					'src'     => $this->get_base_url() . '/js/frontend.js',
					'version' => $this->_version,
					'deps'    => array( 'jquery' ),
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

				//print "Subscription: $subscription";
			} catch (Recurly_ValidationError $e) {
				$this->log_error( __METHOD__ . '(): Invalid Plan, Subscription, Account, or BillingInfo data; ' . $e->getMessage() );
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

		public function get_recurly_js_response() {

			return json_decode( rgpost( 'recurly_response' ) );

		}

		public function get_card_labels() {

			// Get credit card types.
			$card_types  = GFCommon::get_card_types();

			// Initialize credit card labels array.
			$card_labels = array();

			// Loop through card types.
			foreach ( $card_types as $card_type ) {

				// Add card label for card type.
				$card_labels[ $card_type['slug'] ] = $card_type['name'];

			}

			return $card_labels;

		}
	}
}
?>
