<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Analysis;

use RetinaLyze\Analysis\AnalysisFileHandler;
use RetinaLyze\Analysis\AnalyzeImage;
use RetinaLyze\Database\DatabaseHandler;

/**
 * This class can be used for analysing images and ranges of images.
 *
 * @author mom
 */
class Analyze {

    private $dbHandler;
    private $errorMsg;
    private $errorCode;
    private $analysisFileHandler;
    private $rpc;

    public function __construct($rpc = true, $dbh = NULL) {
        //Get Database handler
        if($dbh == NULL){
            $this->dbHandler = new DatabaseHandler();
        }else{
            $this->dbHandler = $dbh;
        }
        //Get the file handler
        $this->analysisFileHandler = new AnalysisFileHandler();
        $this->rpc = $rpc;
    }
    
    /**
     * Analyze photo with DR or AMD analysis
     * @param type $imgID
     * @param type $type
     * @param int $userId
     * @param boolean $demoPassby
     * @return boolean
     * @throws ImageDoesNotExist
     */
    
    public function analyzePhoto($imgID, $type, $userID = null, $username = null, $demoPassby = false) {
        try{
            $imageAnalyzer = new AnalyzeImage($imgID, $type, $demoPassby, $this->rpc, $this->dbHandler);
        } catch (\RetinaLyze\Exception\ImageDoesNotExist $ex) {
            $this->errorMsg = "Image does not exist";
            $this->errorCode = 1;
            return false;
        }
        

        if(!($startAnaStatus = $imageAnalyzer->startAnalyzingPhoto())){
            $this->errorMsg = $imageAnalyzer->getErrorMessage();
            $this->errorCode = $imageAnalyzer->getErrorCode();
            return false;
        }

        $statusFinish = true;
        if ($this->rpc == true) {
            $statusFinish = $imageAnalyzer->getAnalysisResult();
            $this->errorMsg = $imageAnalyzer->getErrorMessage();
            $this->errorCode = $imageAnalyzer->getErrorCode();
        }else{
            $this->dbHandler->addAnalysisInitiated($startAnaStatus, $userID, $imgID, $type, $username);
        }

        return $statusFinish;
    }

    public function analyzePhotoRange($startID, $endID, $type, $demoPassby) {
        //Get photo information
        $notAnaInfo = $this->dbHandler->getAllImgsInRange($startID, $endID);
        while ($notAnaInfoRow = $notAnaInfo->fetch_assoc()) {
            $userId = $notAnaInfoRow["userID"];
            $imgID = $notAnaInfoRow["id"];
            $username = $notAnaInfoRow["username"];
            if ($this->analyzePhoto($imgID, $type, $userId, $username, $demoPassby)) {
                error_log("finished with: " . $imgID);
            } else {
                if($this->errorCode != 5){
                    error_log("Error starting analysis: " . $this->errorMsg);
                }
            }
        }
        return true;
    }

    public function getAnalysisResultThatWasInitiated($demoPassby) {
        $analysisDB = $this->dbHandler->getAnalysisInitiated();
        while ($row = $analysisDB->fetch_assoc()) {
            $imageAnalyzer = new AnalyzeImage($row['imgID'], $row['type'], $demoPassby, $this->rpc, $this->dbHandler);
            $imageAnalyzer->getAnalysisResult();
        }
        $this->dbHandler->cleanAnalysisInitiated();
        return true;
    }

    public function getErrorMessage() {
        return $this->errorMsg;
    }

    public function getErrorCode() {
        return $this->errorCode;
    }

}
