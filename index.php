<?php

    require_once 'VIT/VITAutoload.php';

    use VIT\VIT;

    try {
        
        $vit = new VIT([
            'binder' => ['{{', '}}'],
            'dir' => __DIR__.'/tpl'
        ]);
        
        $vit->assign([
            
            'title' => 'VIT Template engine',
            
            'me' => [
                'name' => 'Dammy',
                'age' => '18',
                'level' => 'novice'
            ],
            
        ]);
        
        $vit->build('index');
        
        
    } catch (Exception\Build $e) {
        
        die($e->getMessage());
        
    }