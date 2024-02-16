<?php

/*
|--------------------------------------------------------------------------
| General settings
|--------------------------------------------------------------------------
*/

$config['donation_currency'] = "EUR";
$config['donation_currency_sign'] = "€";

/*
|--------------------------------------------------------------------------
| PayPal Donation (www.paypal.com)
|--------------------------------------------------------------------------
*/

$config['use_paypal'] = true;

/**
 * PayPal Mode
 *
 * Options Available:
 *
 * sandbox = Testing the code end-to-end
 * live    = Ready for production
*/
$config['paypal_mode'] = "sandbox";

/**
 * PayPal Client ID
 *
 * Check your client id in:
 * https://developer.paypal.com/developer/applications
*/
$config['paypal_userid'] = '22';

/**
 * PayPal Secret Password
 *
 * Check your secret password in:
 * https://developer.paypal.com/developer/applications
*/
$config['paypal_secretpass'] = '22';

/*
|--------------------------------------------------------------------------
| Stripe Donation (www.stripe.com)
|--------------------------------------------------------------------------
*/

$config['use_stripe'] = true;

/**
 * Stripe Publishable Key
 *
 * Check your publishable key in:
 * https://dashboard.stripe.com/apikeys
 */
$config['stripe_publishable_key'] = "";  

/**
 * Stripe Secret Key
 *
 * Check your secret key in:
 * https://dashboard.stripe.com/apikeys
 */
$config['stripe_secret_key'] = ""; 

?>
