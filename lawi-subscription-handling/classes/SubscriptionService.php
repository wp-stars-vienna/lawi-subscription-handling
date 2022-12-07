<?php

namespace wps\lawi;

use \DateTime;
use DateTimeZone;
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

        //add_filter('woocommerce_subscriptions_registered_statuses', array( $this, 'register_new_post_status'), 100, 1);
        //add_filter('wcs_subscription_statuses', array($this, 'add_new_subscription_statuses'), 100, 1);
        //add_filter('woocommerce_can_subscription_be_updated_to', array( $this, 'extends_can_be_updated'), 100, 3);
    }

    public function extends_can_be_updated($can_be_updated, $new_status, $subscription){
        if ($new_status=='waiting') {
            if ($subscription->payment_method_supports('subscription_suspension') && $subscription->has_status(array('active', 'pending', 'on-hold'))) {
                $can_be_updated = true;
            } else {
                $can_be_updated = false;
            }
        }
	    return $can_be_updated;
    }

    public function register_new_post_status($registered_statuses){
        dump($registered_statuses);

        $registered_statuses['wc-waiting'] = _nx_noop(
            'Warten <span class="count">(%s)</span>',
            'Warten <span class="count">(%s)</span>',
            'post status label including post count',
            ''
        );

        return $registered_statuses;
    }

    public function add_new_subscription_statuses($subscription_statuses){
        $subscription_statuses['wc-waiting'] = __('Warte auf Abostart', '');
        return $subscription_statuses;
    }

    public function wps_upate_next_payment($order_id) {
        if(wcs_order_contains_subscription($order_id)) {
            $order = new WC_Order( $order_id );

            $start_date = $order->get_meta('_epaper_startdate');

            $next_payment = new Datetime('now', new DateTimeZone('europe/vienna'));
            $next_payment->setTimestamp(strtotime($start_date . " +1 month"));
            $next_payment->setTime(0, 0);
            $next_payment = $next_payment->format('Y-m-d H:i:s');

            $start_date = new DateTime($start_date, new DateTimeZone('europe/vienna'));
            $start_date = $start_date->format('Y-m-d H:i:s');

            $subscriptionsOfOrder = wcs_get_subscriptions_for_order( $order_id );
            $subscription = $subscriptionsOfOrder[array_key_first($subscriptionsOfOrder)];

            if($subscription) {
                $dates = array();
                $dates['start'] = $start_date;
                $dates['next_payment'] = $next_payment;

                try {
                    $subscription->update_dates( $dates, 'gmt' );
                    $subscription->update_status('waiting');
                    wp_cache_delete( $order_id, 'posts' );
                } catch ( Exception $e ) {
                    wcs_add_admin_notice( $e->getMessage(), 'error' );
                }

            }
        }
    }

}