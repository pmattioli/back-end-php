<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Analysis;

use RetinaLyze\Files\FilesystemFactory;
use RetinaLyze\Utils\Config;
use RetinaLyze\Exception\DeletionNotCompleted;
use RetinaLyze\Exception\UploadCouldNotComplete;
use RetinaLyze\Exception\DownloadCouldNotComplete;

/**
 * This class handle transfer for data with the S3 exchange bucket
 *
 * @author mom
 */
class AnalysisFileHandler {
    private $config;
    private $exchangeFilesystem;
    private $photoStorageFilesystem;
    
    function __construct() {
         //Get config
        $this->config = Config::getConfig();
        if(!empty($this->config['worker_s3_region'])){
            $this->exchangeFilesystem = FilesystemFactory::create($this->config['worker_exchange_path'], $this->config['worker_exchange_filesystem'], $this->config['worker_s3_region']);
        }else{
            $this->exchangeFilesystem = FilesystemFactory::create($this->config['worker_exchange_path'], $this->config['worker_exchange_filesystem']);
        }
        $this->photoStorageFilesystem = FilesystemFactory::create($this->config['RetinaLyzeWebPath'], $this->config['photo_storage_filesystem']);
    }

    /**
     * Delete an object in the S3 exchange bucket
     * 
     * @param string $key The key for the object that will be deleted
     * @throws DeletionNotCompleted
     */
    
    public function deleteS3File($key) {
        try {
            $this->exchangeFilesystem->delete($key);
        } catch (\Exception $ex) {
            throw new DeletionNotCompleted("Could not delete the objects, error: " . $ex->getMessage());
        }
    }
    
    /**
     * Get an object in the S3 exchange bucket. If saveas is specified then the object will be saved to that location, otherwise the filestream will be returned.
     * 
     * @param string $key The key for the object that will be downloaded
     * @param string $saveas The path where the object will be saved
     * @throws DownloadCouldNotComplete
     */
    
    public function getS3File($key, $saveas = null) {
        try {
            if ($saveas == null) {
                $fileStream = $this->exchangeFilesystem->read($key);
                return $fileStream;
            } else {
                $fileStream = $this->exchangeFilesystem->read($key);
                $extraConfig = array('ServerSideEncryption' => 'AES256');
                $this->photoStorageFilesystem->put($saveas, $fileStream, $extraConfig);
            }
        } catch (\Exception $ex) {
            throw new DownloadCouldNotComplete("Could not get the object, error: " . $ex->getMessage());
        }
    }
    
    /**
     * Check if an object exists in the S3 exchange bucket. 
     * 
     * @param string $key The key for the object that will be checked
     * @return boolean Returns true if exists false otherwise
     */
    
    public function checkS3File($key) {
        return $this->exchangeFilesystem->has($key);
    }
    
    
    /**
     * Put an object in the S3 exchange bucket. Using AES256 encryption.
     * 
     * @param string $key The key for the object that will be putted
     * @param string $source The path to the object that have to be uploaded
     * 
     * @throws UploadCouldNotComplete
     */
    
    public function addPhotoToExchange($key, $resizedPhoto) {
        $putStream = tmpfile();
        if($putStream === false){
            throw new UploadCouldNotComplete("Could not create tmp stream.");
        }
        $saveImageToStream = imagejpeg($resizedPhoto, $putStream);
        if($saveImageToStream === false){
            throw new UploadCouldNotComplete("Could not save file to stream.");
        }
        rewind($putStream);
        $config = array('ServerSideEncryption' => 'AES256');
        $saveStatus = $this->exchangeFilesystem->putStream($key, $putStream, $config);
        if (is_resource($putStream)) {
            fclose($putStream);
        }
        if($saveStatus === false){
            throw new UploadCouldNotComplete("Could not save resized photo to path: " . $key);
        }
    }
    
    public function deletePhoto($key){
        try {
            $this->photoStorageFilesystem->delete($key);
        } catch (\Exception $ex) {
            throw new UploadCouldNotComplete("Could not delete the object: " . $ex->getMessage());
        }
    }
    
    
}
