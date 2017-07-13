<?php

    namespace VIT\Exception;
    
    use Exception;

    class Config extends Exception
    {
        
        public function __construct($msg) {
            
            parent::__construct('Vit Configuration Error: '. $msg);
        }
        
    }