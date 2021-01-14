<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Exception;

/**
 * Description of NameChangeDetected
 *
 * @author mom
 */
class NameChangeDetected extends RuntimeException {
    private $oldName;
    private $imgID;
    
    public function setOldName($name){
        $this->oldName = $name;
    }
    
    public function getOldName(){
        return $this->oldName;
    }
    
    public function setImgID($imgID){
        $this->imgID = $imgID;
    }
    
    public function getImgID(){
        return $this->imgID;
    }
}
