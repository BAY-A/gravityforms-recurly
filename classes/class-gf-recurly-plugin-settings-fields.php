<?php

class GFRecurly_Plugin_Settings_Fields{

    protected static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __construct() {
    }

    public function do_plugin_settings_fields( $settings = null ) {

			$recurly_subdomain = rgar( $settings, 'gf_recurly_subdomain' );
			$recurly_api_key = '';

			$recurly_api_fields = array(
				array(
					'name'    => 'gf_recurly_subdomain',
					'label'   => esc_html__( 'Recurly subdomain', 'gravityforms-recurly' ),
					'type'    => 'text',
					'class'		=> 'small',
					'tooltip'   => esc_html__( 'This is found in your Recurly account\'s URL: http://YOUR_SUBDOMAIN.recurly.com', 'gravityforms-recurly' ),
				),
			);

			if ( $recurly_subdomain ) {

				$recurly_api_fields[] = array(
					'name'    => 'gf_recurly_api_key',
					'label'   => esc_html__( 'Recurly API key', 'gravityforms-recurly' ),
					'type'    => 'text',
					'class'		=> 'medium',
					'tooltip'   => esc_html__( 'An API key can be generated <a target="_blank" title="Recurly API keys" href="https://'.$recurly_subdomain.'.recurly.com/developer/api_keys">here</a>', 'gravityforms-recurly' ),
				);
			}

			$recurly_fields = array(
				array(
					'title'       => 'API Access',
					'description' => '',
					'fields'      => $recurly_api_fields,
				)
			);

			if( $recurly_subdomain ){

				$webhooks_desc = '
					<p style="text-align: left;">' .
					esc_html__( 'To receive information and send notifications about a customer subscription and other events from Recurly, you must create a webhook for this site in your Recurly \'Developers\' area. Follow the steps below to confirm.', 'gravityforms-recurly' ) .
					'</p>
					<ul>
						<li>' . sprintf( esc_html__( 'Navigate to your %sDevelopers > Webhooks page.%s', 'gravityforms-recurly' ), '<a target="_blank" href="https://'.$recurly_subdomain.'.recurly.com/configuration/endpoints" target="_blank">', '</a>' ) . '</li>' .
					'<li>' . sprintf( esc_html__( 'If a webhook is already set up for this site, you\'ll see a webhook listed for %s', 'gravityforms-recurly' ), '<strong>' . esc_url( add_query_arg( 'page', 'recurly_listener', get_bloginfo( 'url' ) . '/' ) ) . '</strong>' ) . '</li>' .
					'<li>' . sprintf( esc_html__( 'If it\'s not, select "New Endpoint" and create one, giving it the name %s and entering the following URL: %s', 'gravityformspaypal' ), '<strong>"' . get_bloginfo( 'name' ).' (Gravity Forms + Recurly)' . '"</strong>', '<strong>' . esc_url( add_query_arg( 'page', 'recurly_listener', get_bloginfo( 'url' ) . '/' ) ) . '</strong>' ) . '</li>' .
					'</ul>
						<br/>';

				$recurly_fields[] = array(
					'title'       => 'Webhooks',
					'description' => $webhooks_desc,
					'fields'      => array(),
				);
			}

			$recurly_fields[] = array(
				'title'       => '',
				'description' => '',
				'fields'      => array(
					array(
						'type' => 'save',
						'messages' => array(
							'success' => esc_html__( 'Settings have been updated.', 'gravityforms-recurly' ),
						),
					)
				),
			);

			return $recurly_fields;
    }
}
