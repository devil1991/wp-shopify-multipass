<?php
/**
 * Plugin Name: WP-Shopify-Multipass
 * Description: Shopify Multipass through Wordpress.
 * Version: 1.0.0
 * Author: BarrelNY
 * Author URI: http://barrelny.com
 * License: BSD 3-Clause License
 */

//Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
   exit; 
}

/*
SHOPIFY MULTIPASS CLASS
*/
require_once( 'multipass.php');

/*
THIS FUNCTION WILL ADD TEXT BOXES FOR SETTING UNDER THE GENERAL TAB
*/
add_action('admin_init', function() {
  register_setting('general', 'shopify-domain', 'esc_attr');
  register_setting('general', 'shopify-api_key', 'esc_attr');
  register_setting('general', 'shopify-url', 'esc_attr');

  add_settings_section('shopify-section', 'Shopify Settings', function() {
    echo '<p>Specify the Multipasssettings for your Shopify Account.</p>';
  }, 'general');

  add_settings_field('shopify-domain', '<label for="shopify-domain">Domain</label>', function() {
    $value = get_option('shopify-domain');
    if(empty($value)){ $value = 'test.myshopify.com';}
    echo '<input type="url" name="shopify-domain" id="shopify-domain" value="' . $value . '" class="regular-text ltr">';
    echo '<p class="description">Replace your shopify domain with the dummy shopify domain</p>';
  }, 'general', 'shopify-section');

  add_settings_field('shopify-api_key', '<label for="shopify-api_key">Multipass Key</label>', function() {
    $value = get_option('shopify-api_key');
    echo '<input type="text" name="shopify-api_key" id="shopify-api_key" value="' . $value . '" class="regular-text ltr">';
    echo '<p class="description">This is your Multipass Key, you can find it in  Settings > Checkout page > Customer Accounts</p>';
  }, 'general', 'shopify-section');

  add_settings_field('shopify-url', '<label for="shopify-url">Redirect After Login</label>', function() {
    $value = get_option('shopify-url');
    echo '<input type="text" name="shopify-url" id="shopify-url" value="' . $value . '" class="regular-text ltr">';
    echo '<p class="description">This is store url where customer will be redirect after login</p>';
  }, 'general', 'shopify-section');
});

add_action( 'rest_api_init', function () {
  register_rest_route( 'shopify/', '/multipass-login', array(
    'methods' => 'POST',
    'callback' => 'shopify_multipass_login',
    'permission_callback' => 'is_user_logged_in',
    'args' => array(
      'return_to' => array(
        'required' => true
      )
    ),
  ) );
} );


/*
 THIS FUNCTION WILL WORK AS SHOPIFY MULTIPASS FUNCTION.
 */
function shopify_multipass_login( $request ) {
  $user = wp_get_current_user();
  $shop = get_option('shopify-domain');
  $api_key = get_option('shopify-api_key');
  $return_url = get_option('shopify-url');
  $customer_email = $user->user_email;
  $customer_first_name = get_user_meta($user->ID, 'first_name', true);
  $customer_last_name = get_user_meta($user->ID, 'last_name', true);

  $customer_data = array(
    "email" => $customer_email,
    "first_name" => $customer_first_name,
    "last_name" => $customer_last_name,
    'return_to' => $request['return_url'],
  );

  $multipass = new ShopifyMultipass($api_key);
  $token = $multipass->generate_token($customer_data);
  $url = "https://".$shop."/account/login/multipass/".$token;

  return [
    'url' => $url
  ];
}