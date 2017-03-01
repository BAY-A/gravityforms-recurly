<?php
class GFRecurly_Get_Column_Value_TransactionType {

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

	public function get_column_value_transactionType( $feed ) {

		switch ( rgar( $feed['meta'], 'transactionType' ) ) {
			case 'updateBilling' :
				return esc_html__( 'Update Billing Information', 'gravityforms-recurly' );
			break;
			case 'updateSubscription' :
				return esc_html__( 'Update Subscription', 'gravityforms-recurly' );
			break;
		}

		return $this->gfpaymentaddon->get_column_value_transactionTypeParent( $feed );
	}
}
?>
