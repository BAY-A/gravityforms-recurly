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

			return array(

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
							'choices'  => $this->subscription_plan_choices(),
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
	}

	public function subscription_plan_choices() {
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
