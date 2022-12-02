<?php

namespace wps\lawi\permissions;

use \WC_Subscription;

class SubscriptionWatcher
{

    public function __construct(){

        global  $plugin;

        //dump($plugin->permissionService);

       add_action('woocommerce_subscription_status_updated', [$this, 'woocommerce_subscription_status_updated']);
    }

    public function woocommerce_subscription_status_updated(WC_Subscription $subscription){

        global $plugin;

        $subscriptionStatus = $subscription->get_status();
        $subscriptionProducts = $subscription->get_items();

        $key = array_key_first($subscriptionProducts);
        $subscriptionProduct = $subscriptionProducts[$key];

        //$subscriptionProduct->get_name()
        //$subscriptionProduct->get_id()
        //$subscriptionProduct->get_data()


        //dump($plugin);
        //dump($subscriptionStatus);
        //dump($subscriptionProduct->get_id());
        //die();
    }


}