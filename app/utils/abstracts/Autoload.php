<?php

class Autoload {
    public function __construct(){
        include_once realpath(__DIR__ . '/../../../vendor/autoload.php');
    }
}
new \Autoload();