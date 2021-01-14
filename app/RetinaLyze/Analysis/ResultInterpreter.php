<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Analysis;

use RetinaLyze\Database\DatabaseHandler;

/**
 * Description of newPHPClass
 *
 * @author mom
 */
class ResultInterpreter {

    /**
     * This method should be used to get a color response from a the number of lesions.
     * 
     * @param array $imgInfo
     * @return string AMD: "green", "yellow", "ungradable" or NULL (if analysis is not runned). DR: "green", "yellow", "red", "ungradable" or NULL (if analysis is not runned). Glaucoma: "green", "yellow", "red", "ungradable" or NULL (if analysis is not runned)
     */
    public static function getResultColorsStandard($imgInfo, $userSettings = null, $extendedGlaucomaInfo = false) {
        
        //Get offset values
        if($userSettings == null){
            $dbh = new DatabaseHandler();
            $userInfoDB = $dbh->getUserSettingsFromID($imgInfo['userID']);
            $userInfo = $userInfoDB->fetch_assoc();
        }else{
            $userInfo = $userSettings;
        }
        $drOffset = empty($userInfo["drOffset"]) || !is_numeric($userInfo["drOffset"]) ? 0 : (int)$userInfo["drOffset"];
        $amdOffset = empty($userInfo["amdOffset"]) || !is_numeric($userInfo["amdOffset"]) ? 0 : (int)$userInfo["amdOffset"];
        $annotationsDR = $imgInfo['annotationsDR'] === NULL ? NULL : (int)$imgInfo['annotationsDR'] - $drOffset;
        $annotationsAMD = $imgInfo['annotationsAMD'] === NULL ? NULL : (int)$imgInfo['annotationsAMD'] - $amdOffset;
        $glaucomaResult = $imgInfo['glaucomaResult'] === NULL ? NULL : (int)$imgInfo['glaucomaResult'];
        
        //Get the color results
        $dr = self::getDRResult($imgInfo['refID'], $annotationsDR, $imgInfo["answerTypeDR"], $imgInfo["status"], $imgInfo);
        $amd = self::getAMDResult($imgInfo['refID'], $annotationsAMD, $imgInfo["answerTypeAMD"], $imgInfo["status"], $imgInfo);
         
        if($extendedGlaucomaInfo){
            $glaucoma = self::getGlaucomaResultExtended($glaucomaResult, $imgInfo);
            return array("dr" => $dr, "amd" => $amd, "glaucoma" => $glaucoma['result'], "glaucomaUngrableReason" => $glaucoma['ungradableReason']);
        }else{
            $glaucoma = self::getGlaucomaResult($glaucomaResult, $imgInfo);
            return array("dr" => $dr, "amd" => $amd, "glaucoma" => $glaucoma);
        }
        
    }
    
    private static function getDRResult($refID, $annotationsDR, $answerTypeDR, $refStatus, $imgInfo) {
        $dr = null;
        if ($refID == NULL || $refStatus == 0) {
            if($imgInfo['quality'] == '0'){
                $dr = 'ungradable';
            }else{
               if ($annotationsDR === NULL) {
                    $dr = null;
                } else if ($annotationsDR > 3) {
                    $dr = "red";
                } else if ($annotationsDR > 0 && $annotationsDR <= 3) {
                    $dr = "yellow";
                } else {
                    $dr = "green";
                } 
            }
        } else {
            if ($answerTypeDR === NULL && $annotationsDR === NULL) {
                $dr = null;
            }else if ($answerTypeDR === NULL) { //Must check if specialist have check NULL (nothing was choosen) since this can indicate that the couldn't be analyzed (badimage quality)
                $dr = "ungradable";
            } else if ($answerTypeDR == 2) {
                $dr = "red";
            } else if ($answerTypeDR == 1) {
                $dr = "yellow";
            } else {
                $dr = "green";
            }
        }
        return $dr;
    }
    
    private static function getGlaucomaResultExtended($glaucomaResult, $imgInfo) {
        $glaucoma = self::getGlaucomaResult($glaucomaResult, $imgInfo);
        $extendedGlaucomaResult = array();
        $extendedGlaucomaResult['result'] = $glaucoma;
        $extendedGlaucomaResult['ungradableReason'] = null;
        if($glaucoma == 'ungradable'){
            if($imgInfo['isSaturated'] == '1'){
                $extendedGlaucomaResult['ungradableReason'] = 'saturated';
            }else if($imgInfo['glaucomaAnalysisError'] == '1' && !empty($imgInfo['analysisErrorCode']) && $imgInfo['analysisErrorCode'] == '2'){
                $extendedGlaucomaResult['ungradableReason'] = 'no onh';
            }else{
                $extendedGlaucomaResult['ungradableReason'] = 'ungradable';
            }
        }
        return $extendedGlaucomaResult;
    }


    private static function getGlaucomaResult($glaucomaResult, $imgInfo) {
        $refID = $imgInfo['refID'];
        $refStatus = $imgInfo["status"];
        $answerTypeGlaucoma = $imgInfo["answerTypeGlaucoma"];
        $glaucoma = null;
        if ($refID == NULL || $refStatus == 0) {
            if($imgInfo['isSaturated'] == '1' || $imgInfo['glaucomaAnalysisError'] == '1' || $imgInfo['glaucomaSegmentationError'] == '1' || $imgInfo['glaucomaBorderRejected'] == '1'){
                $glaucoma = 'ungradable';
            }else{
                if ($glaucomaResult === NULL) {
                    $glaucoma = null;
                } else if ($glaucomaResult == 3) {
                    $glaucoma = "red";
                } else if ($glaucomaResult == 2) {
                    $glaucoma = "yellow";
                } else {
                    $glaucoma = "green";
                }
            }
        } else {
            if ($answerTypeGlaucoma === NULL && $glaucomaResult === NULL) {
                $glaucoma = null;
            }else if ($answerTypeGlaucoma === NULL) { //Must check if specialist have check NULL (nothing was choosen) since this can indicate that the couldn't be analyzed (badimage quality)
                $glaucoma = "ungradable";
            } else if ($answerTypeGlaucoma == 2) {
                $glaucoma = "red";
            } else if ($answerTypeGlaucoma == 1) {
                $glaucoma = "yellow";
            } else {
                $glaucoma = "green";
            }
        }
        return $glaucoma;
    }
    
    public static function getGlaucomaResultWithoutESBResult($glaucomaResult){
        $glaucoma = null;
        if ($glaucomaResult === NULL) {
            $glaucoma = null;
        } else if ($glaucomaResult == 3) {
            $glaucoma = "red";
        } else if ($glaucomaResult == 2) {
            $glaucoma = "yellow";
        } else {
            $glaucoma = "green";
        }
        return $glaucoma;
    }


    private static function getAMDResult($refID, $annotationsAMD, $answerTypeAMD, $refStatus, $imgInfo) {
        $amd = null;
        if ($refID == NULL || $refStatus == 0) {
            if($imgInfo['quality'] == '0'){
                $amd = 'ungradable';
            }else{
                if ($annotationsAMD === NULL) {
                    $amd = null;
                } else if ($annotationsAMD > 0) {
                    $amd = "yellow";
                } else {
                    $amd = "green";
                }
            }
        } else {
            if ($answerTypeAMD === NULL && $annotationsAMD === NULL) {
                $amd = null;
            }else if ($answerTypeAMD === NULL) { //Must check if specialist have check NULL (nothing was choosen) since this can indicate that the couldn't be analyzed (badimage quality)
                $amd = "ungradable";
            } else if ($answerTypeAMD == 1) {
                $amd = "yellow";
            } else {
                $amd = "green";
            }
        }
        return $amd;
    }

}
