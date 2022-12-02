<?php

namespace wps\lawi\permissions;

class PermissionService
{

    private string $subscriptionsJsonFile='';
    private array $subscriptionsArray=[];
    private array $userRoles=[];

    public function __construct(string $subscriptionsJsonFile){
        $this->subscriptionsJsonFile = $subscriptionsJsonFile;
        $this->readJsonData();

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
                    $productID = get_field('wps_lawi_subproduct_' . $subscription['SubscriptionProduct'], 'options') ?? null;
                    if(!!$productID){
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

}