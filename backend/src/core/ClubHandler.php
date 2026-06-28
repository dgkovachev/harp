<?php

namespace App;


class ClubHandler extends PDO_CON{
    public function __construct(){
        if(!isset($this->pdo))parent::__construct();
    }

    public function createClub(){
        
    }
}