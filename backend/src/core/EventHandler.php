<?php
namespace App;



class EventHandler extends PDO_CON{
    public function __construct(){
        if(!isset($this->pdo))parent::__construct();
    }


    public function createEvent(){
        
    }
}