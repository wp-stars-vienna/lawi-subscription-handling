<?php

namespace wps\lawi;

class Plugin
{

    public string $path = '';

    public function __construct(string $path){
        $this->path = $path;
    }
}