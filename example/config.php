<?php

/**
* Include vit
*/
require_once __DIR__.'/../VIT/VITAutoload.php';

use VIT\VIT;

/**
* Set directory to load templates from
*/
$vit_config = ['dir' => __DIR__ .'/tpl'];

try {

    /**
    * Start vit
    */
    $vit = new VIT($vit_config);

} catch(VIT\Exception\Config $e) {

    /**
    * Kill the page if there's a configuration error
    */
    die($e->getMessage());
} 