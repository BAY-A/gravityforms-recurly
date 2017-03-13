# Gravity Forms + Recurly (Gravity Forms Add-on)
Gravity Forms + Recurly payment add-on

## What is this?
This is a Gravity Forms Add-on, created with the [GFPaymentAddOn class](https://www.gravityhelp.com/documentation/article/gfpaymentaddon/ "GFPaymentAddOn") for [Backlinko](http://backlinko.com/ "Backlinko"), where I am the CTO.

## What can it do?
**Currently:**
- Create a Recurly plan from a subscription: Integrated with the Gravity Forms User Registration Add-on, allowing WP user account creation on successful payment, and storing relevant Recurly account data with the WordPress user
- Create a one-off Recurly payment: Integrated with the Gravity Forms User Registration Add-on, allowing WP user account creation on successful payment, and storing relevant Recurly account data with the WordPress user
- Update Recurly billing/account/address information: Integrated with the Gravity Forms User Registration Add-on

**Future:**
- Update Recurly subscription: Allow user to change their Recurly subscription; the result of which is then stored/updated as user meta

## Specific to-dos:
- [ ] Implement listener for Recurly webhooks (ie, to suspend a WP user account for non-payment, etc.)
- [ ] Neaten up returned Recurly data that's saved in the `gf_recurly` database table / standardise it / store it in a useful and meaningful way
- [ ] Allow storage of users' cards' last four digits (I believe only one payment method can be stored at a time for Recurly user accounts)

## Credits:
- [Naomi Bush](https://gravityplus.pro/ "GravityPlus") of GravityPlus: Some user account sign-in code in the `GFRecurly_Utils` class, and some database transaction code in the `GFRecurly_Data` inspired by code found in her `Gravity Forms + Stripe` and `Gravity Forms + (More) Stripe` plugins
