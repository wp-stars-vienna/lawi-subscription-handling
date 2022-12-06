<?php

namespace wps\lawi;

use \DateTime;
use \WC_Order;


class SubscriptionService
{

    public function __construct()
    {
        $this->init();
    }

    public function init(): void
    {
        // TODO: add functionality to prevent multiple subs of the same type
        // TODO: prevent subs & regular items being mixed in cart
        add_action('woocommerce_payment_complete', array($this, 'wps_upate_next_payment'));
    }

    public function wps_upate_next_payment($order_id) {
        if(wcs_order_contains_subscription($order_id)) {
            $order = new WC_Order( $order_id );

            $start_date = $order->get_meta('_epaper_startdate');

            $next_payment = new \Datetime();
            $next_payment->setTimestamp(strtotime($start_date . " +1 month"));
            $next_payment->setTime(0, 0);
            $next_payment = $next_payment->format('Y-m-d H:i:s');

            $start_date = new \DateTime($start_date);
            $start_date = $start_date->format('Y-m-d H:i:s');

            $subscriptionsOfOrder = wcs_get_subscriptions_for_order( $order_id );
            $subscription = $subscriptionsOfOrder[array_key_first($subscriptionsOfOrder)];

            if($subscription) {
                $dates = array();
                $dates['start'] = $start_date;
                $dates['next_payment'] = $next_payment;

                try {
                    $subscription->update_dates( $dates, 'gmt' );
                    wp_cache_delete( $order_id, 'posts' );
                } catch ( Exception $e ) {
                    wcs_add_admin_notice( $e->getMessage(), 'error' );
                }

            }
        }
    }

}