<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Images;

/**
 * Description of Thumbnail
 *
 * @author mom
 */
class Thumbnail {
    /*
     * Generates a thumbnail from an jpeg image at a path
     * 
     * @param string $imagePath
     * @param string $thumbnailPath
     * @param int $newWidth
     * @throws Exception
     */
    public static function generate($filesystem, $imagePath, $thumbnailPath, $newWidth = 50){
        //Check that image exists
        if($imagePath == "" || !$filesystem->has($imagePath)){
            throw new \Exception('File does not exist here: ' . $imagePath);
        }
        
        $sourceImageData = $filesystem->read($imagePath);
        
        // Get the width and height on the image.
        list($width, $height) = getimagesizefromstring($sourceImageData);
        
        //Calculate the the new height from the new width, where the ratio is taken into account
        $widthFactor = $width / $newWidth;
        $newHeight = $height / $widthFactor;

        //Create a new image which is going to contain the new resized image
        $resizedImage = imagecreate($newWidth, $newHeight);
        $sourceImage = imagecreatefromstring($sourceImageData);

        // Resize
        $imageIsResized = imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        if($imageIsResized){
            header('Content-Type: image/pjpeg');
            
            $putStream = tmpfile();
            
            $statusSavedImage = imagejpeg($resizedImage, $putStream);
            rewind($putStream);
            $extraConfig = array('ServerSideEncryption' => 'AES256');
            $filesystem->putStream($thumbnailPath, $putStream, $extraConfig);
            
            if (is_resource($putStream)) {
                fclose($putStream);
            }
            
            imagedestroy($resizedImage);
            imagedestroy($sourceImage);
            if(!$statusSavedImage){
                throw new \Exception("Could not save the the thumbnail from here: " . $imagePath);
            }
        }else{
            imagedestroy($resizedImage);
            imagedestroy($sourceImage);
            throw new \Exception("Could not resize image from image path: " . $imagePath);
        }
    }
}
