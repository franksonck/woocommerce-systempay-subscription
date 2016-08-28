<?php
/*
Plugin Name: Woocommerce Systempay Subscription
Plugin URI: https://github.com/jlm2017/woocommerce-systempay-subscription
Description: Subscriptions with the Systempay plateforms
Version: 1.0.0
Author: Guillaume Royer <perso@guilro.com>
Author URI: https://blog.guilro.com
Text Domain: woocommerce-systempay-subscription
Domain Path: /langs
Copyright: 2016 Guillaume Royer
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

if (!in_array('woocommerce-systempay/woocommerce-systempay.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

add_filter('woocommerce_available_payment_gateways', 'woocommerce_systempay_subscription_filter_gateways', 1);
function woocommerce_systempay_subscription_filter_gateways($gateways)
{
    // Remove multi gateway if normal product
    if (!woocommerce_systempay_subscription_is_subscription()) {
        unset($gateways['systempaymulti']);

        return $gateways;
    }

    // Remove all gateways but multi if product is a subscription
    foreach ($gateways as $gateway_name => $gateway) {
        if ($gateway_name !== 'systempaymulti') {
            unset($gateways[$gateway_name]);
        }
    }

    // if there is no method payment (multi is disabled)
    if (count($gateways) === 0) {
      return $gateways;
    }

    // We keep only one payment options and tweak it depending on date
    foreach ($gateways['systempaymulti']->settings['payment_options'] as $key => $fields) {
        $option = $gateways['systempaymulti']->settings['payment_options'][$key];
        $gateways['systempaymulti']->settings['payment_options'] = [
            $key => [
                'label' => $option['label'],
                'amount_min' => $option['amount_min'],
                'amount_max' => $option['amount_max'],
                'contract' => $option['contract'],
                'count' => strval(woocommerce_systempay_subscription_get_periods()),
                'period' => $option['period'],
                'first' => $option['first'],
            ],
        ];
    }

    return $gateways;
}

// Hide quantity on product line if product is a subscription
add_filter('woocommerce_checkout_cart_item_quantity', 'woocommerce_systempay_subscription_cart_item_quantity');
function woocommerce_systempay_subscription_cart_item_quantity($item_quantity)
{
    if (!woocommerce_systempay_subscription_is_subscription()) {
        return $item_quantity;
    }

    return '';
}

// If product is a subscription display the price per month on product
// subtotal line
add_filter('woocommerce_cart_product_subtotal', 'woocommerce_systempay_subscription_cart_product_subtotal', 10, 3);
function woocommerce_systempay_subscription_cart_product_subtotal($product_subtotal, $_product, $quantity)
{
    if (!woocommerce_systempay_subscription_is_subscription()) {
        return $product_subtotal;
    }

    $price = $_product->get_price();
    $product_subtotal = wc_price($price);

    return $product_subtotal;
}

// If product is a subscription display the price per month and the number
// of months on the cart subtotal line
add_filter('woocommerce_cart_subtotal', 'woocommerce_systempay_subscription_cart_subtotal', 10, 3);
function woocommerce_systempay_subscription_cart_subtotal($cart_subtotal, $compound, $cart)
{
    if (!woocommerce_systempay_subscription_is_subscription()) {
        return $cart_subtotal;
    }

    $periods = woocommerce_systempay_subscription_get_periods();

    return wc_price($cart->subtotal / $periods).' x '.$periods;
}

// If product is a subscription display a sentence explaining the amount per
// months and the number of months
add_filter('woocommerce_cart_total', 'woocommerce_systempay_subscription_cart_total');
function woocommerce_systempay_subscription_cart_total($cart_total)
{
    global $woocommerce;

    if (!woocommerce_systempay_subscription_is_subscription()) {
        return $cart_total;
    }

    $periods = woocommerce_systempay_subscription_get_periods();

    return wc_price($woocommerce->cart->total / $periods).' par mois durant '.$periods.' mois.';
}

// We allow only one product at the time in the cart
add_filter('woocommerce_add_cart_item_data', 'woocommerce_systempay_subscription_add_cart_item_data');
function woocommerce_systempay_subscription_add_cart_item_data($cart_item_data)
{
    global $woocommerce;
    $woocommerce->cart->empty_cart();

    return $cart_item_data;
}

// If the new product is a subscription, we change the quantity to the number
// of months
add_filter('woocommerce_add_cart_item', 'woocommerce_systempay_subscription_add_cart_item');
function woocommerce_systempay_subscription_add_cart_item($cart_item)
{
    if (woocommerce_systempay_subscription_is_subscription($cart_item['product_id'])) {
        $cart_item['quantity'] = woocommerce_systempay_subscription_get_periods();
    }

    return $cart_item;
}

// Utility function to know if the cart is composed only by the subscription
// product
function woocommerce_systempay_subscription_is_subscription($product_id = null)
{
    if ($product_id !== null) {
      return has_term('mensuel', 'product_tag', $product_id);
    }

    global $woocommerce;
    foreach ($woocommerce->cart->cart_contents as $key => $values) {
        if (!has_term('mensuel', 'product_tag', $values['product_id'])) {
            return false;
        }
    }

    return true;
}

// Utility function to determine number of periods
function woocommerce_systempay_subscription_get_periods()
{
    // 15 juin 2017
    $timestamp = 1497563999;
    $days_diff = intval(($timestamp - time()) / (24 * 60 * 60));

    return intval($days_diff / 30) + 1;
}
