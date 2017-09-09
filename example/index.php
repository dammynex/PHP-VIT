<?php

/**
* include config.php
*/
require_once 'config.php';

/**
* Build a template
*/
try {

    
    $vit

        /**
        * Assign some variables
        */
        ->assign([

            //title
            'title' => 'VIT Examples',

            //my name
            'name' => 'Damilola Ezekiel',

            'height' => 45,

            //my skills
            'skills' => [

                'PHP' => '75%',

                'Javascript' => '60%',

                'jQuery' => '70%',

                'CSS' => '80%',

                'HTML5' => '85%'
            ]
        ])
    

        /**
        * This will build and render template content from {$dir/index.vit}
        */
        ->build('index');

} catch(VIT\Exception\Build $e) {

    /**
    * kill the page if there's a syntax/build error
    */
    die($e->getMessage());
}