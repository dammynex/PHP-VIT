<?php

    namespace VIT\Exception;
    
    use Exception;

    class Build extends Exception
    {
        
        public function __construct($msg) {
            
            parent::__construct('Vit Build Error: '. $msg);
        }
    }