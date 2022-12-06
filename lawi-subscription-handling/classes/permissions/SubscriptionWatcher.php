<?php

namespace wps\lawi\permissions;

use DateTime;
use DateTimeZone;
use \WC_Subscription;
use wps\lawi\Plugin;

class SubscriptionWatcher
{

    public function __construct(){

        global  $plugin;

        //dump($plugin->permissionService);

       add_action('woocommerce_subscription_status_updated', [$this, 'woocommerce_subscription_status_updated']);
    }

    public function woocommerce_subscription_status_updated(WC_Subscription $subscription){

        // state list for subscriptions:
        // 1. active
        // 2. pending-cancel
        // 3. on-hold

        $now = new DateTime('today', new DateTimeZone('europe/vienna'));
        $startDate = new DateTime($subscription->get_date( 'start' ), new DateTimeZone('europe/vienna'));
        $status = $subscription->get_status();
        $permissions = $this->getPermissionDataBySubscription($subscription);

        if(isset($permissions['userRoles']) && count($permissions['userRoles'])>0){

            // When the start date has been reached
            //if($now >= $startDate){

                // and the new status is
                if(in_array($subscription->get_status(), array('active'))){
                    foreach ($permissions['userRoles'] as $userRole){
                        $this->addPermissions($userRole['slug'], $subscription);
                    }
                    return;
                }else{
                    // if not remove permissions
                    foreach ($permissions['userRoles'] as $userRole){
                        $this->removePermissions($userRole['slug'], $subscription);
                    }
                    return;
                }
            //}
        }
    }

    private function addPermissions(string $userRole, WC_Subscription $subscription){
        $userRole = LawiRole::getInstanceByRole($userRole);
        Plugin::get_instance()->permissionService->add($subscription->get_user(), $userRole);
    }

    private function removePermissions(string $userRole, WC_Subscription $subscription){
        $userRole = LawiRole::getInstanceByRole($userRole);
        Plugin::get_instance()->permissionService->remove($subscription->get_user(), $userRole);
    }

    public function getPermissionDataBySubscription(WC_Subscription $subscription){
        $items = $subscription->get_items();
        $subscriptionProduct = $items[array_key_first($items)];
        return Plugin::get_instance()->permissionService->getSubscriptionsArray()['ePaperSubscriptions'][$subscriptionProduct->get_id()];
    }
}