<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Images;

use RetinaLyze\Images\FundusPhoto;

/**
 * Description of AnnDrawPhoto
 *
 * @author mom
 */
class AnnDrawPhoto {
    private $data;
    private $image;
    private $color_lesions;
    private $photoSize;

    public function __construct($data, $imgID, $baseDir) {
        $this->data = $data;
        $imageDir = $baseDir . 'RetImages/';
        $imageFilePath = $imageDir . $imgID . ".jpg";
        $photo = new FundusPhoto($imageFilePath);
        $this->photoSize = $photo->getPhotoSize();
    }
    
    public function generate($drawONH, $type){
        $this->createBlankImage($this->photoSize["width"], $this->photoSize["height"]);
        if($type == 0){
            $this->color_lesions = imagecolorallocate($this->image, 0, 0, 0);
        }else {
            $this->color_lesions = imagecolorallocate($this->image, 108, 223, 245);
        }
        
        imagesetthickness($this->image, 3);
        
        if(isset($this->data[2])){
            if($this->data[2] == "2"){ 
                if($drawONH){
                    $this->drawOpticNerveHead();
                }
                array_splice($this->data, 0, 4);
            }else{
                array_splice($this->data, 0, 1);
            }
            $this->drawLesions();
        }
        return $this->image;
    }
    
    private function createBlankImage($width, $height){
        $this->image = imagecreatetruecolor($width, $height);
        imagesavealpha($this->image, true);

        $color = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
        imagefill($this->image, 0, 0, $color);
    }
    
    private function drawOpticNerveHead(){
        $value = explode(" ", $this->data[3]);
        $col_ellipse = imagecolorallocate($this->image, 255, 255, 255);
        imageellipse($this->image, $value[0], $value[1], $value[2]*2, $value[2]*2, $col_ellipse);
    }
    
    private function drawLesions() {
        $shapeStage = true;
        $typeStage = false;
        $dataStage = false;
        foreach ($this->data as $value) {
            if($shapeStage){
                $shapeStage = false;
                $typeStage = true;
                continue;
            }
            if($typeStage){
                $typeStage = false;
                $dataStage = true;
                continue;
            }
            $values = explode(" ", $value);

            $points = $values[0];
            array_splice($values, 0, 1);
            // Draw the polygon
            imagepolygon($this->image, $values,
            $points,
            $this->color_lesions);
            $dataStage = false;
            $shapeStage = true;
        }
    }
}
