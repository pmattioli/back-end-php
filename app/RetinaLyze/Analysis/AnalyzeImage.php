<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Analysis;

use RetinaLyze\Utils\Config;
use RetinaLyze\Analysis\AnalyzeRPCRequester;
use RetinaLyze\Analysis\AnalyzeQueueRequester;
use RetinaLyze\Analysis\AnalysisFileHandler;
use RetinaLyze\Analysis\ResultFileHandler;
use RetinaLyze\Images\FundusPhoto;
use RetinaLyze\Images\AnnotationPhoto;
use RetinaLyze\Exception\UploadCouldNotComplete;
use RetinaLyze\Exception\DownloadCouldNotComplete;
use RetinaLyze\Exception\AnalysisDidNotComplete;
use RetinaLyze\Exception\DeletionNotCompleted;
use RetinaLyze\Exception\FileDoesNotExist;
use RetinaLyze\Exception\ImageDoesNotExist;
use RetinaLyze\Authentication\CheckAuthentication;
use RetinaLyze\Users\DemoStatusChecker;
use RetinaLyze\Database\DatabaseHandler;

/**
 * Description of AnalyzeImage
 *
 * @author mom
 */
class AnalyzeImage {
    private $config;
    private $dbHandler;
    private $errorMsg;
    private $errorCode;
    private $analysisFileHandler;
    private $rpc;
    private $username;
    private $imageInfo;
    private $imgID;
    private $type;
    private $userID;
    private $reqID;
    private $demoPassby;
    private $dsc;
    private $s3usingUserID;
    
    /**
     * Construct an object which can perform DR and AMD analysis on images found in the database
     * @param int $imgID
     * @param int $type
     * @param boolean $demoPassby
     * @param boolean $rpc
     * @param object $dbh
     * @throws ImageDoesNotExist
     */
    
    function __construct($imgID, $type, $demoPassby = false, $rpc = true, $dbh = NULL, $reqID = NULL) {
        //Get config
        $this->config = Config::getConfig();
        
        if($dbh == NULL){
            $this->dbHandler = new DatabaseHandler();
        }else{
            $this->dbHandler = $dbh;
        }
        
        //Get the file handler
        $this->analysisFileHandler = new AnalysisFileHandler();
        $this->rpc = $rpc;
        
        //Get photo information
        $notAnaInfo = $this->dbHandler->getNotAnalyzedImg($imgID);
        if($notAnaInfo->num_rows == 0){
            throw new ImageDoesNotExist('The image does no longer exists');
        }
        
        $this->imageInfo = $notAnaInfo->fetch_assoc();
        $this->username = $this->imageInfo["username"];
        $this->s3usingUserID = $this->imageInfo["s3usingUserID"];
        
        $this->imgID = $imgID;
        $this->type = $type;
        $this->userID = $this->imageInfo["userID"];
        $this->demoPassby = $demoPassby;
        if($reqID === NULL){
            $this->reqID = md5(uniqid(rand(), true));
        }else{
            $this->reqID = $reqID;
        }
        
        $this->dsc = new DemoStatusChecker($this->userID, $this->dbHandler);
    }

    
    public function startAnalyzingPhoto() {
        $auth = new CheckAuthentication($this->dbHandler);
        $auth->checkUserAccessToAnalyzeImage($this->imgID, $this->type);
        
        if($this->checkImageStatusBeforeAnalysis() === false){
            return false;
        }

        //Set analysis status of the photo to running
        $this->dbHandler->updateRunningAnalysis($this->imgID, $this->type);

        //Get the path of the image going to be analyzed
        if($this->s3usingUserID){
            $imageAnalyzinDir = $this->userID . '/RetImages/';
        }else{
            $imageAnalyzinDir = '/Analyzed/' . $this->username . '/RetImages/';
        }
        
        $orgImageFile = $imageAnalyzinDir . $this->imgID . "_org.jpg";
        
        try {
            $fundusPhoto = new FundusPhoto($orgImageFile);
            $originalFundusPhotoSize = $fundusPhoto->getPhotoSize();
            if($originalFundusPhotoSize['width'] > 499 && $originalFundusPhotoSize['height'] > 499){
                $fundusPhoto->resizePhoto();
            }else{
                $this->dbHandler->updateNotRunningAnalysis($this->imgID, $this->type);
                error_log('Could not analyze image because it is to small. ImageID: ' . $this->imgID);
                $this->errorMsg = "Could not analyze image because it is to small";
                $this->errorCode = 8;
                return false;
            }
        } catch (FileDoesNotExist $ex) {
            $this->dbHandler->updateNotRunningAnalysis($this->imgID, $this->type);
            error_log("Couldn't analyze this file doesn't exist: " . $orgImageFile . ", " . $ex->getMessage());
            $this->errorMsg = "Couldn't analyze this file doesn't exist";
            $this->errorCode = 1;
            return false;
        } catch (\Exception $ex) {
            $this->dbHandler->updateNotRunningAnalysis($this->imgID, $this->type);
            error_log("Error: Could not create fundus image error in, " . $ex->getFile() . ",Line: " .  $ex->getLine() . "\n Message: " . $ex->getMessage() . "\n" . $ex->getTraceAsString());
            $this->errorMsg = "Could not create fundus image";
            $this->errorCode = 7;
            return false;
        }
        
        $cameraID = $this->getAndSetCameraID();

        if($cameraID == 1 || $cameraID == 9 || $cameraID == 33){
            if($this->addHorusOverlay($fundusPhoto) === false){
                return false;
            }
        }

        $resizedimageAnalyzinFile = $imageAnalyzinDir . $this->imgID . ".jpg";
        
        try {
            //Store the resized file
            $fundusPhoto->saveResizedPhoto($resizedimageAnalyzinFile);
        } catch (\Exception $ex) {
            $this->dbHandler->updateNotRunningAnalysis($this->imgID, $this->type);
            error_log('Error: Could not save resized image: ' . $ex->getMessage());
            $this->errorMsg = "Something went wrong";
            $this->errorCode = 8;
            return false;
        }

        try {
            //Upload photo to S3
            $this->analysisFileHandler->addPhotoToExchange($this->reqID . "_" . $this->imgID . "_" . $this->type . ".jpg", $fundusPhoto->getResizedPhoto());
        } catch (UploadCouldNotComplete $ex) {
            $this->dbHandler->updateNotRunningAnalysis($this->imgID, $this->type);
            error_log("Error: Could not put the objects: " . $ex->getMessage());
            return false;
        }

        try {
            //Send the message that the photo should be analyzed
            $this->sendAnalyzeRequest($this->reqID, $this->imgID, $this->type);
        } catch (AnalysisDidNotComplete $ex) {
            error_log("Error: Analyze did not complete: " . $ex->getMessage());
            $this->dbHandler->updateNotRunningAnalysis($this->imgID, $this->type);
            return false;
        }
        if ($this->rpc == false) {
            try {
                $this->dbHandler->updateNotRunningAnalysis($this->imgID, $this->type);
                return $this->reqID;
            } catch (Exception $ex) {
                error_log("Error: " . $ex->getMessage());
                return false;
            }
        }
        return true;
    }
    
    public function checkIfAnalysisResultIsReady() {
        return $this->analysisFileHandler->checkS3File($this->reqID . "_" . $this->imgID . "_" . $this->type . "_result.txt");
    }

    public function getAnalysisResult() {
        //Create path for annotaion files
        if ($this->type == 0) {
            if($this->s3usingUserID){
                $annDir = DIRECTORY_SEPARATOR . $this->userID . DIRECTORY_SEPARATOR  . 'AnnImages' . DIRECTORY_SEPARATOR;
            }else{
                $annDir = DIRECTORY_SEPARATOR . 'Analyzed' . DIRECTORY_SEPARATOR . $this->username . DIRECTORY_SEPARATOR . 'AnnImages' . DIRECTORY_SEPARATOR;
            }
            $annFile = $annDir . $this->imgID . '.png';
            $annFileJPG = $annDir . $this->imgID . '.jpg';
        } elseif ($this->type == 1) {
            if($this->s3usingUserID){
                $annDir = DIRECTORY_SEPARATOR . $this->userID . DIRECTORY_SEPARATOR  . 'WhiteAnnImages' . DIRECTORY_SEPARATOR;
            }else{
                $annDir = DIRECTORY_SEPARATOR . 'Analyzed' . DIRECTORY_SEPARATOR . $this->username . DIRECTORY_SEPARATOR . 'WhiteAnnImages' . DIRECTORY_SEPARATOR;
            }
            $annFile = $annDir . $this->imgID . '.png';
            $annFileJPG = $annDir . $this->imgID . '.jpg';
        }

        try {
            $this->analysisFileHandler->getS3File($this->reqID . "_" . $this->imgID . "_" . $this->type . "_ann.jpg", $annFileJPG);
            $annTxtFileStream = $this->analysisFileHandler->getS3File($this->reqID . "_" . $this->imgID . "_" . $this->type . "_ann.txt");
            $txtFileStream = $this->analysisFileHandler->getS3File($this->reqID . "_" . $this->imgID . "_" . $this->type . "_result.txt");
        } catch (DownloadCouldNotComplete $ex) {
            error_log('DownloadCouldNotComplete ' . $ex->getMessage());
            $this->dbHandler->updateNotRunningAnalysis($this->imgID, $this->type);
            return false;
        }

        try {
            $result = ResultFileHandler::extractResultData($txtFileStream, $this->type);
        } catch (\Exception $ex) {
            error_log("error: " . $ex->getMessage());
            $this->dbHandler->updateNotRunningAnalysis($this->imgID, $this->type);
            return false;
        }

        $annPhoto = new AnnotationPhoto($annFileJPG, $this->type);

        $annPhoto->createPhoto($annFile);

        try {
            $this->dbHandler->updateAfterAnalyzeNew($this->imgID, $result["annotations"], $this->type, $result["quality"], $annTxtFileStream, $result["opticnervehead"]);
            $this->dsc->useDRandAMDAnalysis($this->type, $this->imgID);
        } catch (Exception $ex) {
            error_log("Error: Could not update data in DB: " . $ex->getMessage());
            $this->dbHandler->updateNotRunningAnalysis($this->imgID, $this->type);
            return false;
        }

        //Insert into missing CSV table if user have it enabled
        //Get the API settings of the user
        $apiSettings = $this->dbHandler->getAPISettingsForUser($this->userID)->fetch_assoc();

        //Only run if CSV function is enabled
        if ($apiSettings != NULL && $apiSettings["CSVEnabled"] == "1") {
            //Check if the imgID already exists
            $imgIDExists = $this->dbHandler->getSpecificRowInCSVMissingTable($this->userID, $this->imgID);
            if ($imgIDExists->num_rows == 0) {
                $this->dbHandler->insertIDToCSVMissingTable($this->userID, $this->imgID);
            }
        }

        //Delete the files from the S3 bucket
        try {
            $this->cleanUpS3Files();
        } catch (DeletionNotCompleted $ex) {
            //Do not halt since the photo has been analyzed
            error_log("Error: Could not clean up S3 files: " . $ex->getMessage());
        }
        return true;
    }

    private function sendAnalyzeRequest() {
        $MAX_NUM_OF_ATTEMPTS = 2;
        $attempts = 0;
        $status = false;
        do {
            try {
                if ($this->rpc) {
                    $retinalyzerpc = new AnalyzeRPCRequester();
                } else {
                    $retinalyzerpc = new AnalyzeQueueRequester();
                }
                $response = $retinalyzerpc->call($this->reqID . "_" . $this->imgID . "_" . $this->type . "_" . ($this->rpc ? 'true' : 'false'));
                if($response == "SUCCESS"){
                    $status = true;
                }
                break;
            } catch (\Exception $ex) {
                \error_log("Error: RabbitMQ error: " . $ex->getMessage());
                $attempts++;
                sleep(1);
                continue;
            }
        } while ($attempts < $MAX_NUM_OF_ATTEMPTS);
        
        if ($this->rpc && $status !== true) {
            $this->cleanUpS3Files($this->reqID, $this->imgID, $this->type);
            throw new AnalysisDidNotComplete("Error: Could not analyze photo");
        }
    }

    private function cleanUpS3Files() {
        //Delete photo from S3
        $this->analysisFileHandler->deleteS3File($this->reqID . "_" . $this->imgID . "_" . $this->type . ".jpg");

        //Delete annotation image from S3
        $this->analysisFileHandler->deleteS3File($this->reqID . "_" . $this->imgID . "_" . $this->type . "_ann.jpg");

        //Delete ann txt file from S3
        $this->analysisFileHandler->deleteS3File($this->reqID . "_" . $this->imgID . "_" . $this->type . "_ann.txt");

        //Delete result file from S3
        $this->analysisFileHandler->deleteS3File($this->reqID . "_" . $this->imgID . "_" . $this->type . "_result.txt");
    }
    
    /**
     * Get the camera ID. It start checking if a cameraID exists on the image info, if not then the user camera ID is returned and set as the camera ID on the image.
     * @return int return NULL if there is no camera set on either the image info or user.
     */
    
    private function getAndSetCameraID() {
        //Check if camera ID exists on camera. If it does then return it.
        if($this->imageInfo["cameraID"]==NULL){
            if($this->imageInfo["camera"]==NULL){
                return NULL;
            }else{
                $cameraID = (int)$this->imageInfo["camera"];
                $this->dbHandler->updateCameraOnImageInfo($this->imgID, $cameraID);
                return $cameraID;
            }
        }else{
            return (int)$this->imageInfo["cameraID"];
        }
    }

    public function getErrorMessage() {
        return $this->errorMsg;
    }

    public function getErrorCode() {
        return $this->errorCode;
    }
    
    private function checkImageStatusBeforeAnalysis() {
        //Check if the demo is expired
        if ($this->demoPassby === false) {
            if ($this->dsc->getDRAndAMDStatus() === false) {
                $this->errorMsg = "Demo expired";
                $this->errorCode = 3;
                return false;
            }
        }

        //Check type and the analysis is not already running
        if ($this->type == 0) {
            if ($this->imageInfo["runningAnalysisDR"] == 1) {
                $this->errorMsg = "Already running analysis on photo";
                $this->errorCode = 4;
                return false;
            }
        } else if($this->type == 1){
            if ($this->imageInfo["runningAnalysisAMD"] == 1) {
                $this->errorMsg = "Already running analysis on photo";
                $this->errorCode = 4;
                return false;
            }
        } else {
            $this->errorMsg = "Type not supported";
            $this->errorCode = 6;
            return false;
        }

        //Check if analysis already have runned
        if ($this->type == 0 && $this->imageInfo["annotationsDR"] !== NULL) {
            $this->errorMsg = "Analysis have already been runned";
            $this->errorCode = 5;
            return false;
        } else if ($this->type == 1 && $this->imageInfo["annotationsAMD"] !== NULL) {
            $this->errorMsg = "Analysis have already been runned";
            $this->errorCode = 5;
            return false;
        }
    }
    
    private function addHorusOverlay($fundusPhoto) {
        try {
            $fundusPhoto->addHorusOverlay();
        } catch (\Exception $ex) {
            $this->dbHandler->updateNotRunningAnalysis($this->imgID, $this->type);
            error_log("Could not put horus overlay: " . $ex->getMessage());
            $this->errorMsg = "Could not add overlay";
            $this->errorCode = 8;
            return false;
        }
    }
}
