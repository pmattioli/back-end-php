<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Images;

use RetinaLyze\Utils\Config;
use RetinaLyze\Files\FilesystemFactory;
use RetinaLyze\Exception\RuntimeException;
use RetinaLyze\Exception\FileDoesNotExist;

/**
 * Description of AnnotationPhoto
 *
 * @author mom
 */
class AnnotationPhoto {
    private $photo;
    private $width;
    private $height;
    private $type;
    private $config;
    private $photoStorageFilesystem;
    
    /**
     * 
     * @param string $photoPath the path for the photo
     * @throws FileDoesNotExist
     * @throws RuntimeException
     */
    
    function __construct($photoPath, $type) {
        //Get config
        $this->config = Config::getConfig();
        $this->photoStorageFilesystem = FilesystemFactory::create($this->config['RetinaLyzeWebPath'], $this->config['photo_storage_filesystem']);
        if (!$this->photoStorageFilesystem->has($photoPath)) {
            throw new FileDoesNotExist("The photo does not exit in this path: " . $photoPath);
        }
        $photoData = $this->photoStorageFilesystem->read($photoPath);
        $this->photo = imagecreatefromstring($photoData);
        if($this->photo === false){
            throw new RuntimeException("Could not create photo from path: " . $photoPath);
        }
        
        // Determine the size of the photo
        list($this->width, $this->height) = getimagesizefromstring($photoData);
        
        $this->type = $type;
    }
    
    public function createPhoto($toPath){
        
        if ($this->type == 1) {
            $colorImageStatus = imagefilter($this->photo, IMG_FILTER_COLORIZE, 102, 217, 239);
            if($colorImageStatus === false){
                throw new RuntimeException("Could color photo");
            }
            $alphablendingStatus = imagealphablending($this->photo, false);
            if($alphablendingStatus === false){
                throw new RuntimeException("Could not do alphablending");
            }
            $remove = imagecolorallocate($this->photo, 102, 217, 239);
        } else {
            $remove = imagecolorallocate($this->photo, 0, 0, 0);
        }
        if($remove === false){
            throw new RuntimeException("Could not allocate color from photo");
        }
        imagecolortransparent($this->photo, $remove);
        
        // Save image
        $putStream = tmpfile();
            
        $saveStatus = imagejpeg($this->photo, $putStream);
        rewind($putStream);
        $extraConfig = array('ServerSideEncryption' => 'AES256');
        $this->photoStorageFilesystem->putStream($toPath, $putStream, $extraConfig);

        if (is_resource($putStream)) {
            fclose($putStream);
        }
        if($saveStatus === false){
            throw new RuntimeException("Could not save resulting photo");
        }
    }
}
