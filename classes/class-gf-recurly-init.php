<?php

class GFRecurly_Init{

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

		add_filter( 'gform_register_init_scripts', array( $this, 'register_init_scripts' ), 10, 3 );
		add_filter( 'gform_field_content', array( $this, 'add_recurly_attributes' ), 10, 5 );
	}

	public function register_init_scripts( $form, $field_values, $is_ajax ) {

		// If form does not have a Stripe feed and does not have a credit card field, exit.
		if ( ! $this->gfpaymentaddon->has_feed( $form['id'] ) ) {
			return;
		}

		$cc_field = $this->gfpaymentaddon->get_credit_card_field( $form );

		if ( ! $cc_field ) {
			return;
		}

		// Prepare Stripe Javascript arguments.
		$settings = $this->gfpaymentaddon->get_plugin_settings();
		$args = array(
			'subdomain'  => rgar( $settings, 'gf_recurly_subdomain' ),
			'api_key' => rgar( $settings, 'gf_recurly_api_key' ),
			'formId'     => $form['id'],
			'ccFieldId'  => $cc_field->id,
			'ccPage'     => $cc_field->pageNumber,
			'isAjax'     => $is_ajax
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
						$content = str_replace( "name='input_".$field_input['id']."'", "onchange='updateHiddenNumber( jQuery(this) )' name='input_".$field_input['id']."'", $content );
						$content = str_replace( "<label for='input_".$form_id.'_'.$field->id."_1", "<div data-recurly='number' id='input_".$field->id."_hidden' style='display: none;'></div><label for='input_".$form_id.'_'.$field->id."_1", $content );
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
}
