<?php

    require_once 'VIT/VITAutoload.php';

    use VIT\VIT;

    try {
        
        $vit = new VIT([
            'binder' => ['{{', '}}'],
            'dir' => __DIR__.'/tpl'
        ]);
        
        /** Assign **/
        $vit->assign('project_name', 'VIT');
        
        /** Multi Assign **/
        $vit->assign([
            
            'title' => 'VIT Template engine',
            
            'me' => [
                'name' => 'Dammy',
                'age' => '18',
                'level' => 'novice'
            ],
            
        ]);
        
        $vit->build('index');
        
        
    } catch (\VIT\Exception\Build | \VIT\Exception\Config $e) {
        
        die($e->getMessage());
        
    }
