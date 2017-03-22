# :cyclone::zap:Gravity Forms + Recurly (Gravity Forms Add-on):zap::cyclone:
Gravity Forms + Recurly payment add-on

## What is this?
This is a Gravity Forms Add-on allowing you to interact with the [Recurly](https://recurly.com/ "Recurly") subscription billing management platform, created with the [GFPaymentAddOn class](https://www.gravityhelp.com/documentation/article/gfpaymentaddon/ "GFPaymentAddOn") as an internal project for [Backlinko](http://backlinko.com/ "Backlinko"), where I am the CTO. This is for WordPress websites.

## What can it do?
**Currently:**
- Create a Recurly plan from a subscription: Integrated with the Gravity Forms User Registration Add-on, allowing WP user account creation on successful payment, and storing relevant Recurly account data with the WordPress user
- Create a one-off Recurly payment: Integrated with the Gravity Forms User Registration Add-on, allowing WP user account creation on successful payment, and storing relevant Recurly account data with the WordPress user
- Update Recurly billing/account/address information: Integrated with the Gravity Forms User Registration Add-on

**Future:**
- Update Recurly subscription: Allow user to change their Recurly subscription; the result of which is then stored/updated as user meta

## How can I use this?
- Make sure you have [Gravity Forms](https://gravityforms.com/ "Gravity Forms") installed. This Add-on will not work without it.
- Make sure that you have a Recurly account (Live or Sandbox).
- After activating this Add-on, in the WordPress Dashboard, go to `Forms > Settings > Recurly`, and enter your Recurly subdomain and Recurly API key. Click Update Settings.
- For a particular Gravity Form, make sure you have at least a Credit Card field and an Address field. Certain Recurly actions, such as Create Recurly Plan From Subscription, require additional fields. These can be viewed under `Forms > <form> > Settings > Recurly`.
- After required fields are linked-up via `Forms > <form> > Settings > Recurly`, save, and it should be good to go.

## Why doesn't X work?
- That feature may not be finished yet.
- There may be an error. Please help fix it :)

## Excuse me, does this use [RecurlyJS](https://recurly.com/recurlyjs/ "RecurlyJS")?
No, this Add-on does not use RecurlyJS right now. I'd like to, but because of [an issue](https://github.com/recurly/recurly-js/issues/309 "RecurlyJS GitHub"), relating to the constraints of Gravity Forms' fields, I can't.

## Specific to-dos:
- [ ] Implement listener for Recurly webhooks (ie, to suspend a WP user account for non-payment, etc.)
- [ ] Neaten up returned Recurly data that's saved in the `gf_recurly` database table / standardise it / store it in a useful and meaningful way
- [ ] Allow storage of users' cards' last four digits (I believe only one payment method can be stored at a time for Recurly user accounts)

## Credits:
- [Naomi Bush](https://gravityplus.pro/ "GravityPlus") of GravityPlus: Some user account sign-in code in the `GFRecurly_Utils` class, and some database transaction code in the `GFRecurly_Data` inspired by code found in her `Gravity Forms + Stripe` and `Gravity Forms + (More) Stripe` plugins
