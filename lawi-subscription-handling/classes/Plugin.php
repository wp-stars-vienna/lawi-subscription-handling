<?php

namespace wps\lawi;

class Plugin
{

    public string $path = '';

    public function __construct(string $path){
        $this->path = $path;

        add_action('init', array( $this, 'epaper_landingpage_sc') );
    }

    public function epaper_landingpage_sc( array $attr = [] ): string
    {
        // do something

        // use datetime
        // create dropdown select

        //
        $string = "";
        return $string;
    }
}