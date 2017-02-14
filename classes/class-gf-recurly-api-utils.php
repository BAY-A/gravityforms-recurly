<?php

class GFRecurly_API_Utils{

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct() {}

	public static function recurly_subscription_object_to_array( Recurly_Subscription $subscription ){

		$subscription_array = array();

		if( $subscription->account && is_object( $subscription->account ) ){

			$subscription->account = $subscription->account->get();
			$subscription_array['account'] = GFRecurly_API_Utils::recurly_account_object_to_array( $subscription->account );
		}

		$subscription_array['plan_code'] = $subscription->plan_code;
		$subscription_array['unit_amount_in_cents'] = $subscription->unit_amount_in_cents;
		$subscription_array['quantity'] = $subscription->quantity;
		$subscription_array['currency'] = $subscription->currency;
		$subscription_array['starts_at'] = $subscription->starts_at;
		$subscription_array['trial_ends_at'] = $subscription->trial_ends_at;
		$subscription_array['total_billing_cycles'] = $subscription->total_billing_cycles;
		$subscription_array['first_renewal_date'] = $subscription->first_renewal_date;
		$subscription_array['timeframe'] = $subscription->timeframe;

		if( $subscription->subscription_add_ons && is_array( $subscription->subscription_add_ons ) ){

			$subscription_array['subscription_add_ons'] = $subscription->subscription_add_ons;
		}

		$subscription_array['net_terms'] = $subscription->net_terms;
		$subscription_array['po_number'] = $subscription->po_number;
		$subscription_array['collection_method'] = $subscription->collection_method;
		$subscription_array['cost_in_cents'] = $subscription->cost_in_cents;
		$subscription_array['remaining_billing_cycles'] = $subscription->remaining_billing_cycles;
		$subscription_array['bulk'] = $subscription->bulk;
		$subscription_array['terms_and_conditions'] = $subscription->terms_and_conditions;
		$subscription_array['bank_account_authorized_at'] = $subscription->bank_account_authorized_at;
		$subscription_array['revenue_schedule_type'] = $subscription->revenue_schedule_type;

		return $subscription_array;
	}

	public static function recurly_account_object_to_array( Recurly_Account $account ){

		$account_array = array();

		if( !is_object( $account ) ){

			return false;
		}

		$account_array['account_code'] = $account->account_code;
		$account_array['state'] = $account->state;
		$account_array['username'] = $account->username;
		$account_array['first_name'] = $account->first_name;
		$account_array['last_name'] = $account->last_name;
		$account_array['vat_number'] = $account->vat_number;
		$account_array['email'] = $account->email;
		$account_array['company_name'] = $account->company_name;
		$account_array['accept_language'] = $account->accept_language;

		if( $account->billing_info && is_object( $account->billing_info ) ){

			$account->billing_info = $account->billing_info->get();
			$account_array['billing_info'] = GFRecurly_API_Utils::recurly_billing_info_object_to_array( $account->billing_info );
		}

		if( $account->address && is_object( $account->address ) ){

			/* Don't need to `get` the address as it's already an address object, not a stub */
			$account_array['address'] = GFRecurly_API_Utils::recurly_address_object_to_array( $account->address );
		}

		$account_array['tax_exempt'] = $account->tax_exempt;
		$account_array['cc_emails'] = $account->cc_emails;
		$account_array['hosted_login_token'] = $account->hosted_login_token;
		$account_array['created_at'] = $account->created_at;
		$account_array['updated_at'] = $account->updated_at;
		$account_array['closed_at'] = $account->closed_at;

		return $account_array;
	}

	public static function recurly_billing_info_object_to_array( Recurly_BillingInfo $billing_info ){

		$billing_info_array = array();

		$billing_info_array['account_code'] = $billing_info->account_code ?: '';
		$billing_info_array['token_id'] = $billing_info->token_id ?: '';
		$billing_info_array['currency'] = $billing_info->currency ?: '';
		$billing_info_array['first_name'] = $billing_info->first_name ?: '';
		$billing_info_array['last_name'] = $billing_info->last_name ?: '';
		$billing_info_array['number'] = $billing_info->number ?: '';
		$billing_info_array['month'] = $billing_info->month ?: '';
		$billing_info_array['year'] = $billing_info->year ?: '';
		$billing_info_array['address1'] = $billing_info->address1 ?: '';
		$billing_info_array['address2'] = $billing_info->address2 ?: '';
		$billing_info_array['city'] = $billing_info->city ?: '';
		$billing_info_array['state'] = $billing_info->state ?: '';
		$billing_info_array['country'] = $billing_info->country ?: '';
		$billing_info_array['zip'] = $billing_info->zip ?: '';
		$billing_info_array['phone'] = $billing_info->phone ?: '';
		$billing_info_array['company'] = $billing_info->company ?: '';
		$billing_info_array['vat_number'] = $billing_info->vat_number ?: '';
		$billing_info_array['currency'] = $billing_info->currency ?: '';
		$billing_info_array['verification_value'] = $billing_info->verification_value ?: '';
		$billing_info_array['ip_address'] = $billing_info->ip_address ?: '';

		return $billing_info_array;
	}

	public static function recurly_address_object_to_array( Recurly_Address $address ){

		$address_array = array();

		$address_array['address1'] = $address->address1?: '';
		$address_array['address2'] = $address->address2?: '';
		$address_array['city'] = $address->city?: '';
		$address_array['state'] = $address->state?: '';
		$address_array['zip'] = $address->zip?: '';
		$address_array['country'] = $address->country?: '';
		$address_array['phone'] = $address->phone?: '';

		return $address_array;
	}
}
