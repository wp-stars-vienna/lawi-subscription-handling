<?php

namespace wps\lawi\permissions;

use DateTime;
use DateTimeZone;
use \WC_Subscription;
use wps\lawi\Plugin;

class SubscriptionWatcher
{

    public function __construct(){
        add_action('init', [$this, 'init']);
        add_action('woocommerce_subscription_status_updated', [$this, 'checkAndUpdateUserRolesAndPermissions']);
        add_action('daily_lawi_subscription_check_cron_event', [$this, 'dailyCronEvent']);
    }

    public function init(){
        if (! wp_next_scheduled ( 'daily_lawi_subscription_check_cron_event' )) {
            wp_schedule_event(time(), 'daily', 'daily_lawi_subscription_check_cron_event');
        }

        $this->dailyCronEvent();
    }

    public function dailyCronEvent(){
        $activeSubscriptionIdList = $this->getActiveSubscriptions();
        if(!!$activeSubscriptionIdList && is_array($activeSubscriptionIdList) && count($activeSubscriptionIdList)>0){
            foreach ($activeSubscriptionIdList as $item){
                $id = (int) $item->ID;
                $subscription = new WC_Subscription($id);

                if(!!$subscription){
                    $this->checkAndUpdateUserRolesAndPermissions($subscription);
                }
            }
        }
    }

    private function getActiveSubscriptions(): array
    {
        global $wpdb;

        $sql = "SELECT posts.ID 
                FROM {$wpdb->prefix}posts as posts 
                WHERE posts.post_status = 'wc-active' 
                AND posts.post_type = 'shop_subscription'";

        return $wpdb->get_results($sql);
    }

    public function checkAndUpdateUserRolesAndPermissions(WC_Subscription $subscription){

        // state list for subscriptions:
        // 1. active
        // 2. pending-cancel
        // 3. on-hold
        // 4. cancelled
        // 5. switched
        // 6. expired
        // 7. trash

        $now = new DateTime('today', new DateTimeZone('europe/vienna'));
        $startDate = new DateTime($subscription->get_date( 'start' ), new DateTimeZone('europe/vienna'));
        $permissions = $this->getPermissionDataBySubscription($subscription);

        if(isset($permissions['userRoles']) && count($permissions['userRoles'])>0){

            // When the start date has been reached
            //if($now >= $startDate){
            if($now < $startDate){

                // and the new status is
                if(in_array($subscription->get_status(), array('active', 'pending-cancel'))){
                    foreach ($permissions['userRoles'] as $userRole){
                        $this->addPermissions($userRole['slug'], $subscription);
                    }
                    return;
                }
            }else{
                    // if not remove permissions
                    foreach ($permissions['userRoles'] as $userRole){
                        $this->removePermissions($userRole['slug'], $subscription);
                    }
                    return;
                }
        }else{
            wc_add_notice('userRoles not found in permission | configuration', 'error');
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
        return Plugin::get_instance()->permissionService->getSubscriptionsArray()['ePaperSubscriptions'][$subscriptionProduct->get_product_id()];
    }
}