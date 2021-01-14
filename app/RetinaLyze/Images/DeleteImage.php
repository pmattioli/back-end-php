<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Images;

use RetinaLyze\Database\DatabaseHandler;
use RetinaLyze\Utils\Config;
use RetinaLyze\Files\FilesystemFactory;

/**
 * Description of DeleteImage
 *
 * @author mom
 */
class DeleteImage {
    private $photoPath;
    private $config;
    private $photoStorageFilesystem;
    private $dbHandler;
    private $imgID;
    private $userID;
    
    function __construct($imgID, $username, $userID) {
        $this->dbHandler = new DatabaseHandler();
        $userSettings = $this->dbHandler->getUserSettingsFromID($userID)->fetch_assoc();
        if($userSettings["s3usingUserID"] == 1){
            $imageDir = '/' . $userID . '/RetImages/';
        }else{
            $imageDir = '/Analyzed/' . $username . '/RetImages/';
        }
        $this->photoPath = $imageDir . $imgID . "_org.jpg";
        $this->imgID = $imgID;
        $this->userID = $userID;
        //Get config
        $this->config = Config::getConfig();
        $this->photoStorageFilesystem = FilesystemFactory::create($this->config['RetinaLyzeWebPath'], $this->config['photo_storage_filesystem']);
        
    }
    
    /**
     * Delete the image data
     * @throws \RuntimeException
     */
    
    public function delete() {
        $this->dbHandler->removeNotAnalyzedImgWithUserID($this->imgID, $this->userID);
        $this->deletePhoto();
    }
    
    /**
     * Delete the photo from storage
     * @throws \RuntimeException
     */
    
    private function deletePhoto(){
        try{
            $this->photoStorageFilesystem->delete($this->photoPath);
        } catch (\Exception $ex) {
            throw new \RuntimeException("Could not delete file. ");
        }
    }
}
