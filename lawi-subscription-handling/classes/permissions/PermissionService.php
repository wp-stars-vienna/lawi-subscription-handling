<?php

namespace wps\lawi\permissions;

use WP_User;

class PermissionService
{

    private string $subscriptionsJsonFile='';
    private array $subscriptionsArray=[];
    private array $userRoles=[];

    public function __construct(string $subscriptionsJsonFile){
        $this->subscriptionsJsonFile = $subscriptionsJsonFile;
        $result = $this->readJsonData();

        if($result === false) return;

        if(
            !!$this->subscriptionsArray['ePaperSubscriptions'] &&
            is_array($this->subscriptionsArray['ePaperSubscriptions']) &&
            count($this->subscriptionsArray['ePaperSubscriptions'])>0
        ){
            $this->installUserRoles($this->subscriptionsArray['ePaperSubscriptions']);
        }
    }

    private function installUserRoles(array $subscriptionsArray){
        if(count($subscriptionsArray) > 0){
            foreach ($subscriptionsArray as $subscription){
                $userRoles = $subscription['userRoles'];
                if(is_array($userRoles) && count($userRoles)>0){
                    foreach ($userRoles as $userRole){
                        $this->userRoles[] = new LawiRole($userRole['label'], $userRole['slug'], $subscription['permissions']);
                    }
                }
            }
        }
    }

    public function getSubscriptionsArray(): array
    {
        return $this->subscriptionsArray;
    }

    /**
     * @return bool
     */
    private function readJsonData(): bool
    {
        if(file_exists($this->subscriptionsJsonFile)){
            $jsonString = file_get_contents($this->subscriptionsJsonFile);
            $subscriptionsArray = json_decode($jsonString, true);

            if(
                !!$subscriptionsArray &&
                is_array($subscriptionsArray) &&
                isset($subscriptionsArray['ePaperSubscriptions']) &&
                is_array($subscriptionsArray['ePaperSubscriptions']) &&
                count($subscriptionsArray['ePaperSubscriptions'])>0)
            {
                $enrichData = [];
                foreach ($subscriptionsArray['ePaperSubscriptions'] as $subscription){

                    if(!function_exists('get_field')){
                        add_action( 'admin_notices', function(){
                            $html = '<div class="error notice">';
                            $html .= '<p>ACF ist nicht installiert - lawi-subscription-handling funktioniert nicht ohne ACF Pro.</p>';
                            $html .= '</div>';
                            echo $html;
                        } );
                        return false;
                    }

                    $productID = get_field('wps_lawi_subproduct_' . $subscription['SubscriptionProduct'], 'options') ?? null;
                    if(!!$productID){

                        if(isset($subscription['permissions']) && count($subscription['permissions'])>0){
                            $permissionArray = [];
                            foreach ($subscription['permissions'] as $permission){
                                $key = array_key_first($permission);
                                if(!!$key) $permissionArray[$key] = $permission[$key];
                            }

                            $subscription['permissions'] = $permissionArray;
                        }
                        $enrichData[$productID] = $subscription;
                    }
                }

                $subscriptionsArray['ePaperSubscriptions'] = $enrichData;
                    $this->subscriptionsArray = $subscriptionsArray;
                    return true;
            }
        }

        return false;
    }

    public function add(WP_User $user, LawiRole $role){
        $user->add_role( $role->slug );
    }

    public function remove(WP_User $user, LawiRole $role){
        $user->remove_role( $role->slug );
    }

}