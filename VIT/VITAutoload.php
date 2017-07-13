<?php

    namespace VIT;

    spl_autoload_register(function($class) {
        
        $classname = str_replace('\\', '/', $class);
        
        $file_to_include = __DIR__ . '/../'.$classname.'.php';
        
        if(file_exists($file_to_include)) {
            
            require_once $file_to_include;
        }
        
    });