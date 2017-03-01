<?php

class GFRecurly_Feed_Settings_Fields extends GFPaymentAddOn{

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function do_feed_settings_fields() {

		$core_feed_settings_fields = array(

			array(
				'description' => '',
				'fields'      => array(
					array(
						'name'     => 'feedName',
						'label'    => esc_html__( 'Name', 'gravityforms-recurly' ),
						'type'     => 'text',
						'class'    => 'medium',
						'required' => true,
						'tooltip'  => '<h6>' . esc_html__( 'Name', 'gravityforms-recurly' ) . '</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityforms-recurly' ),
					),
					array(
						'name'     => 'transactionType',
						'label'    => esc_html__( 'Transaction Type', 'gravityforms-recurly' ),
						'type'     => 'select',
						'onchange' => "jQuery(this).parents('form').submit();",
						'choices'  => array(
							array(
								'label' => esc_html__( 'Select a transaction type', 'gravityforms-recurly' ),
								'value' => '',
							),
							array(
								'label' => esc_html__( 'Single Payment', 'gravityforms-recurly' ),
								'value' => 'product',
							),
							array(
								'label' => esc_html__( 'Subscription Payment', 'gravityforms-recurly' ),
								'value' => 'subscription',
							),
							array(
								'label' => esc_html__( 'Update Billing Information', 'gravityforms-recurly' ),
								'value' => 'updateBilling',
							),
							array(
								'label' => esc_html__( 'Update Subscription', 'gravityforms-recurly' ),
								'value' => 'updateSubscription',
							),
						),
						'tooltip'  => '<h6>' . esc_html__( 'Transaction Type', 'gravityforms-recurly' ) . '</h6>' . esc_html__( 'Select a transaction type.', 'gravityforms-recurly' ),
					),
				),
			),
			array(
				'title'      => 'Subscription Payment Settings',
				'dependency' => array(
					'field'  => 'transactionType',
					'values' => array( 'subscription' ),
				),
				'fields'     => array(
					array(
						'name'     => 'subscriptionPlan',
						'label'    => esc_html__( 'Subscription Plan Name', 'gravityforms-recurly' ),
						'type'     => 'select',
						'choices'  => $this->text_value_choices(),
						'required' => true,
						'tooltip'  => '<h6>' . esc_html__( 'Subscription Plan Name', 'gravityforms-recurly' ) . '</h6>' . esc_html__( "Select which field determines the Recurly subscription plan name.", 'gravityforms-recurly' ),
					),
				),
			),
			array(
				'title'      => 'Single Payment Settings',
				'dependency' => array(
					'field'  => 'transactionType',
					'values' => array( 'product' ),
				),
				'fields'     => array(
					array(
						'name'          => 'paymentAmount',
						'label'         => esc_html__( 'Payment Amount', 'gravityforms-recurly' ),
						'type'          => 'select',
						'choices'       => $this->product_amount_choices(),
						'required'      => true,
						'default_value' => 'form_total',
						'tooltip'       => '<h6>' . esc_html__( 'Payment Amount', 'gravityforms-recurly' ) . '</h6>' . esc_html__( "Select which field determines the payment amount, or select 'Form Total' to use the total of all pricing fields as the payment amount.", 'gravityforms-recurly' ),
					),
					array(
						'name'          => 'paymentDesc',
						'label'         => esc_html__( 'Payment Description', 'gravityforms-recurly' ),
						'type'          => 'select',
						'choices'       => $this->text_value_choices(),
						'required'      => false,
						'tooltip'       => '<h6>' . esc_html__( 'Payment Description', 'gravityforms-recurly' ) . '</h6>' . esc_html__( "Select which field determines the payment description, so that your customers can better understand the charge, and see it in their records.", 'gravityforms-recurly' ),
					),
				),
			),
			array(
				'title'      => esc_html__( 'Customer Information', 'gravityforms-recurly' ),
				'dependency' => array(
					'field'  => 'transactionType',
					'values' => array( 'subscription', 'product', 'updateBilling' ),
				),
				'fields' => array(
					array(
						'name'      => 'billingInformation',
						'label'     => esc_html__( 'Billing Information', 'gravityforms-recurly' ),
						'type'      => 'field_map',
						'field_map' => $this->recurly_billing_info_fields(),
						'tooltip'   => '<h6>' . esc_html__( 'Billing Information', 'gravityforms-recurly' ) . '</h6>' . esc_html__( 'Map your Form Fields to the available listed fields.', 'gravityforms-recurly' )
					),
				),
			),

		);

		if ( class_exists( 'GFUser' ) ) {

			$core_feed_settings_fields[] = array(
				'title'      => esc_html__( 'User Registration', 'gravityforms-recurly' ),
				'dependency' => array(
					'field'  => 'transactionType',
					'values' => array( 'subscription', 'product' ),
				),
				'fields' => array(
					array(
						'name'      => 'autoLoginNewUsers',
						'label'     => esc_html__( 'Automatically log-in new users?', 'gravityforms-recurly' ),
						'type'      => 'select',
						'choices' => array(
						 	array(
								'label' => esc_html__( 'Yes', 'gravityforms-recurly' ),
								'value' => 1
							),
							array(
								'label' => esc_html__( 'No', 'gravityforms-recurly' ),
								'value' => 0
							),
						),
						'default_value' => 0,
						'tooltip'   => '<h6>' . esc_html__( 'Automatically log-in new users?', 'gravityforms-recurly' ) . '</h6>' . esc_html__( 'When a new user is created after paying via Recurly, should they be automatically logged-in? Great for upsells, etc..', 'gravityforms-recurly' )
					),
					array(
						'name'      => 'preventUserRegistrationIfLoggedIn',
						'label'     => esc_html__( 'Prevent User Registration if logged in?', 'gravityforms-recurly' ),
						'type'      => 'select',
						'choices' => array(
						 	array(
								'label' => esc_html__( 'Yes', 'gravityforms-recurly' ),
								'value' => 1
							),
							array(
								'label' => esc_html__( 'No', 'gravityforms-recurly' ),
								'value' => 0
							),
						),
						'default_value' => 0,
						'tooltip'   => '<h6>' . esc_html__( 'Prevent the User Registration add-on from running if the user using the form is logged in?', 'gravityforms-recurly' ) . '</h6>' . esc_html__( 'If enabled, the User Registration feed(s) for this form will only work if the user is not logged in to the site.', 'gravityforms-recurly' )
					),
				),
			);
		}

		$core_feed_settings_fields[] = array(
			'title'      => esc_html__( 'Conditional Logic', 'gravityforms-recurly' ),
			'dependency' => array(
				'field'  => 'transactionType',
				'values' => array( 'subscription', 'product', 'updateBilling', 'updateSubscription' ),
			),
			'fields' => array(
				array(
					'name'       => 'optin',
					'label'      => esc_html__( 'Activate Recurly Condition?', 'gravityforms-recurly' ),
					'tooltip'   => '<h6>' . esc_html__( 'Activate Recurly Condition?', 'gravityforms-recurly' ) . '</h6>' . esc_html__( 'Should payment only be sent to Recurly after a certain condition is met?', 'gravityforms-recurly' ),
					'type'       => 'feed_condition',
				),
			),
		);

		return $core_feed_settings_fields;
	}

	public function text_value_choices() {
		$form = $this->get_current_form();
		$string_choices = $this->get_possible_string_return_value_choices( $form );

		return $string_choices;
	}

	public function get_possible_string_return_value_choices( $form ) {

		$fields  = GFAPI::get_fields_by_type( $form, array( 'hidden', 'text', 'select', 'checkbox', 'product' ) );
		$choices = array(
			array( 'label' => esc_html__( 'Select a text field', 'gravityforms-recurly' ), 'value' => '' ),
		);

		foreach ( $fields as $field ) {
			$field_id    = $field->id;
			$field_label = RGFormsModel::get_label( $field );
			$choices[]   = array( 'value' => $field_id, 'label' => $field_label );
		}

		return $choices;
	}

	public function recurly_billing_info_fields() {

		$fields = array(
			array( 'name' => 'first_name', 'label' => esc_html__( 'First Name', 'gravityforms-recurly' ), 'required' => false ),
			array( 'name' => 'last_name', 'label' => esc_html__( 'Last Name', 'gravityforms-recurly' ), 'required' => false ),
			array( 'name' => 'email', 'label' => esc_html__( 'Email', 'gravityforms-recurly' ), 'required' => false ),
			array( 'name' => 'address', 'label' => esc_html__( 'Address', 'gravityforms-recurly' ), 'required' => false ),
			array( 'name' => 'address2', 'label' => esc_html__( 'Address 2', 'gravityforms-recurly' ), 'required' => false ),
			array( 'name' => 'city', 'label' => esc_html__( 'City', 'gravityforms-recurly' ), 'required' => false ),
			array( 'name' => 'state', 'label' => esc_html__( 'State', 'gravityforms-recurly' ), 'required' => false ),
			array( 'name' => 'zip', 'label' => esc_html__( 'Zip', 'gravityforms-recurly' ), 'required' => false ),
			array( 'name' => 'country', 'label' => esc_html__( 'Country', 'gravityforms-recurly' ), 'required' => false ),
		);

		return $fields;
	}
}
