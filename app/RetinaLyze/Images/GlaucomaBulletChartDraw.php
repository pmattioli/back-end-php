<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Images;

use RetinaLyze\Database\DatabaseHandler;

/**
 * Description of AnnDrawPhoto
 *
 * @author mom
 */
class GlaucomaBulletChartDraw {
    private $height;
    private $width;
    private $labelSideWidth;
    private $borderSize;
    private $thresholdFontSize;
    private $arrowFontSize;
    private $fontPathArrow;
    private $fontPathThreshold;
    private $arrowWidthPixels;
    private $arrowHeightPixels;
    private $minValue;
    private $maxValue;
    private $redThreshold;
    private $orangeThreshold;
    private $yellowThreshold;
    private $numberOfDigitsToDisplay;
    private $numberOutputter;
    private $pixelRatio;
    private $value;

    /**
     * Create a glaucoma bullet draw class
     * Type 1 = GDF
     * Type 2 = Vert C/D Ratio
     * Type 3 = C/D Area Ratio
     */
    
    public function __construct($type, $locale, $imageID, $dbh = null) {
        //General settings for the bullet char
        $this->height = 500;
        $this->width = 100;
        $this->labelSideWidth = 55;
        $this->borderSize = 0;
        $this->thresholdFontSize = 10;
        $this->arrowFontSize = 15;
        
        $this->fontPathArrow = __DIR__ . "/../../../pdf/fonts/GothamMedium.woff";
        $this->fontPathThreshold = __DIR__ . "/../../../pdf/fonts/GothamBook.woff";

        $this->arrowWidthPixels = 15;
        $this->arrowHeightPixels = 15;
        
        if($dbh == null){
            $dbh = new DatabaseHandler();
        }
        
        $imageInfoDB = $dbh->getSingleAnalyzedImgInfoWithUsernameAndGlaucoma($imageID);
        if(empty($imageInfoDB) || $imageInfoDB->num_rows != 1){
            throw new \RuntimeException('Could not find image');
        }
        $imageInfo = $imageInfoDB->fetch_assoc();
        if($type == 1){
            $this->minValue = -100;
            $this->maxValue = 100;
            $this->redThreshold = $imageInfo['gdfRedThreshold'];
            $this->orangeThreshold = $imageInfo['gdfOrangeThreshold'];
            $this->yellowThreshold = $imageInfo['gdfYellowThreshold'];
            $this->value = $imageInfo['gdf'];
            $this->numberOfDigitsToDisplay = 2;
        }else if($type == 2){
            $this->minValue = 1;
            $this->maxValue = 0;
            $this->redThreshold = $imageInfo['cdVertRedThreshold'];
            $this->orangeThreshold = $imageInfo['cdVertOrangeThreshold'];
            $this->yellowThreshold = $imageInfo['cdVertYellowThreshold'];
            $this->value = $imageInfo['cdRatioVert'];
            $this->numberOfDigitsToDisplay = 2;
        }else if($type == 3){
            $this->minValue = 1;
            $this->maxValue = 0;
            $this->redThreshold = $imageInfo['cdAreaRedThreshold'];
            $this->orangeThreshold = $imageInfo['cdAreaOrangeThreshold'];
            $this->yellowThreshold = $imageInfo['cdAreaYellowThreshold'];
            $this->value = $imageInfo['cdRatioArea'];
            $this->numberOfDigitsToDisplay = 2;
        }else{
            throw new \RuntimeException('Type not supported');
        }
        $this->pixelRatio = $this->height / abs($this->maxValue-$this->minValue);
        
        $formatStyle=\NumberFormatter::DECIMAL;
        $this->numberOutputter= new \NumberFormatter($locale, $formatStyle);
        $this->numberOutputter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $this->numberOfDigitsToDisplay);
    }
    
    public function generate($value){
        $handle = imagecreate ($this->width + $this->borderSize * 2 + $this->labelSideWidth, $this->height + $this->borderSize * 2);
        $bgColor = ImageColorAllocate ($handle, 255, 255, 255);  
        //Set colors
        $greenColor = ImageColorAllocate ($handle, 156, 210, 56);
        $yellowColor = ImageColorAllocate ($handle, 255, 255, 0);
        $orangeColor = ImageColorAllocate ($handle, 244, 176, 132);
        $redColor = ImageColorAllocate ($handle, 255, 0, 0);

        $txtColor = ImageColorAllocate ($handle, 0, 0, 0);

        $redTopYPos = $this->height-abs($this->redThreshold-$this->minValue)*$this->pixelRatio;
        imagefilledrectangle($handle, $this->borderSize, $redTopYPos, $this->width, $this->height, $redColor);

        $OrangeTopYPos = $this->height-abs($this->orangeThreshold-$this->minValue)*$this->pixelRatio;
        imagefilledrectangle($handle, $this->borderSize, $OrangeTopYPos, $this->width, $redTopYPos, $orangeColor);

        $YellowTopYPos = $this->height-abs($this->yellowThreshold-$this->minValue)*$this->pixelRatio;
        imagefilledrectangle($handle, $this->borderSize, $OrangeTopYPos, $this->width, $YellowTopYPos, $yellowColor);

        $greenTopYPos = $this->borderSize;
        imagefilledrectangle($handle, $this->borderSize, $greenTopYPos, $this->width, $YellowTopYPos, $greenColor);

        //Write minimum value text
        imagettftext ($handle, $this->thresholdFontSize, 0, $this->borderSize + $this->width + 5, $this->height-$this->borderSize-2, $txtColor, $this->fontPathThreshold, $this->numberOutputter->format($this->minValue));
        //Write max value text
        imagettftext ($handle, $this->thresholdFontSize, 0, $this->borderSize + $this->width + 5, $this->borderSize+$this->thresholdFontSize, $txtColor, $this->fontPathThreshold, $this->numberOutputter->format($this->maxValue));

        $xPosPointerHead = $this->borderSize+$this->width;
        $yPosPointerHead = $this->height-abs($value-$this->minValue)*$this->pixelRatio;
        $this->drawArrow($handle, $xPosPointerHead, $yPosPointerHead, $this->arrowWidthPixels, $this->arrowHeightPixels);

        //Write arrow value text
        $dimensions = imagettfbbox($this->arrowFontSize, 0, $this->fontPathArrow, $this->numberOutputter->format($value));
        $textWidth = abs($dimensions[4] - $dimensions[0]);
        $xPosArrowText = $this->borderSize+$this->width-$this->arrowWidthPixels-$textWidth-3;
        $yPosArrowText = $yPosPointerHead+$this->arrowFontSize/2;
        imagettftext ($handle, $this->arrowFontSize, 0, $xPosArrowText, $yPosArrowText, $txtColor, $this->fontPathArrow, $this->numberOutputter->format($value));

        return $handle;
    }
    
    private function drawArrow($handle, $xPosPointerHead, $yPosPointerHead, $arrowWidthPixels, $arrowHeightPixels){
    
        $arrowColor = ImageColorAllocate ($handle, 0, 0, 0);
        $values = array(
                $xPosPointerHead-$arrowWidthPixels,  $yPosPointerHead-$arrowHeightPixels/2,  // Point 1 (x, y)
                $xPosPointerHead,  $yPosPointerHead, // Point 2 (x, y)
                $xPosPointerHead-$arrowWidthPixels,  $yPosPointerHead+$arrowHeightPixels/2  // Point 3 (x, y)
                );
        imagefilledpolygon($handle, $values, count($values)/2, $arrowColor);


    }
}
