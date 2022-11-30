<?php

namespace wps\lawi\permissions;

class PermissionService
{

    private string $subscriptionsJsonFile='';
    private array $subscriptionsArray=[];

    public function __construct(string $subscriptionsJsonFile){
        $this->subscriptionsJsonFile = $subscriptionsJsonFile;
        $this->readJsonData();
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

            if(!!$subscriptionsArray && is_array($subscriptionsArray)){
                $this->subscriptionsArray = $subscriptionsArray;
                return true;
            }
        }

        return false;
    }

}