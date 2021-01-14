<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Analysis;

use RetinaLyze\Database\DatabaseHandler;
use RetinaLyze\Utils\Config;
use RetinaLyze\Files\FilesystemFactory;
use GuzzleHttp\Client;
use RetinaLyze\Exception\ImageIsSaturatedException;
use RetinaLyze\Exception\GlaucomaAnalysisException;
use RetinaLyze\Exception\GlaucomaSegmentationException;

/**
 * Description of GlaucomaAnalysis
 *
 * @author mom
 */
class GlaucomaAnalysis {
    private $photoStorageFilesystem;
    private $originalPhoto;
    private $imageID;
    private $config;
    private $dbHandler;
    private $httpClient;
    private $apiID;
    private $baseDir;
    private $urlPrefix;
    private $userID;
    private $checkEyeReturned;
    
    
    function __construct($username, $imageID, $userID) {
        $this->config = Config::getConfig();
        $this->photoStorageFilesystem = FilesystemFactory::create($this->config['RetinaLyzeWebPath'], $this->config['photo_storage_filesystem']);
        $this->username = $username;
        $this->userID = $userID;
        $this->imageID = $imageID;
        $this->checkEyeReturned = null;
        $this->urlPrefix = !empty($this->config['insoft_prefix']) ? $this->config['insoft_prefix'] : "";
        $this->dbHandler = new DatabaseHandler();
        $userSettings = $this->dbHandler->getUserSettingsFromID($userID)->fetch_assoc();
        if(isset($userSettings["s3usingUserID"]) && $userSettings["s3usingUserID"] == 1){
            $this->baseDir = DIRECTORY_SEPARATOR . $this->userID . DIRECTORY_SEPARATOR . "RetImages" . DIRECTORY_SEPARATOR;
        }else{
            $this->baseDir = DIRECTORY_SEPARATOR . "Analyzed" . DIRECTORY_SEPARATOR . $username . DIRECTORY_SEPARATOR . "RetImages" . DIRECTORY_SEPARATOR;
        }
        $fullname = $this->baseDir . $this->imageID . "_org.jpg";
        $this->originalPhoto = new \RetinaLyze\Images\FundusPhoto($fullname);
        $this->httpClient = new Client([
            // Base URI is used with relative requests
            'base_uri' => $this->config['insoft_url'],
        ]);
    }
    
    /**
     * Check if laterality is set on the image
     * 
     * @return bool True if the laterality is set on the image, and false if the laterality is not set.
     */
    
    public function checkLateralityIsSet() {
        $imgInfo = $this->dbHandler->getSingleAnalyzedImgInfoWithUsernameAndRef($this->imageID);
        $imgInfoRow = $imgInfo->fetch_assoc();
        if($imgInfoRow['region'] == 'OS'){
            return true;
        }else if($imgInfoRow['region'] == 'OD'){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * Set the laterality on the image
     * 
     * @return bool True if the laterality is set on the image, and false if the laterality is not set.
     */
    
    public function setLateralityOfImage($laterality) {
        $this->dbHandler->updateLaterality($laterality, $this->imageID);
    }
    
    /**
     * Returns the OHN elipse details as json string from the API
     * 
     * @return string
     */
    
    public function getRecievedOHNElipse() {
        $imgInfo = $this->dbHandler->getSingleAnalyzedImgInfoWithUsernameAndGlaucoma($this->imageID);
        $imgInfoRow = $imgInfo->fetch_assoc();
        return $imgInfoRow['recevedOHNEllipse'];
    }
    
    /**
     * Returns the OHN coordinates details as json string from the API
     * 
     * @return string
     */
    
    public function getReceivedOHNCoordinates() {
        $imgInfo = $this->dbHandler->getSingleAnalyzedImgInfoWithUsernameAndGlaucoma($this->imageID);
        $imgInfoRow = $imgInfo->fetch_assoc();
        return $imgInfoRow['receivedOHNCoordinates'];
    }
    
    /**
     * Check if laterality is set on the image
     * 
     * @return bool True if the laterality is set on the image, and false if the laterality is not set.
     */
    
    public function checkIfSegmented() {
        $imgInfo = $this->dbHandler->getSingleAnalyzedImgInfoWithUsernameAndGlaucoma($this->imageID);
        $imgInfoRow = $imgInfo->fetch_assoc();
        if(!empty($imgInfoRow['receivedOHNCoordinates'])){
            return true;
        }else{
            return false;
        }
    }
    
    
    
    /**
     * Check if more than 5 % of the pixels in the optic nerve head area is saturated
     * 
     * @param int $inputted_coordinates_x The center x coordinate of the optic nerve disc
     * @param int $inputted_coordinates_y The center y coordinate of the optic nerve disc
     * @return boolean
     * @throws \Exception
     */
    
    private function isImageSaturated($inputted_coordinates_x, $inputted_coordinates_y){ 
        $w = $this->originalPhoto->getPhotoSize()['width'];
        $h = $this->originalPhoto->getPhotoSize()['height'];
       
        $radius = 150;
        $coordinates_x= round($inputted_coordinates_x);
        $coordinates_y= round($inputted_coordinates_y);
        
        if(!is_numeric($w) || !is_numeric($h) || !is_numeric($coordinates_x) || !is_numeric($coordinates_y) || !is_numeric($radius)){
            throw new \Exception('Some variable was expected to be a number but isnt');
        }
        $i = 0;

        $pixelsInONH = 0;
        for ($x = 0; $x < $w; $x++){
            for ($y = 0; $y < $h; $y++){
                $dx = $x - $coordinates_x;
                $dy = $y - $coordinates_y;  
                $distanceSquared = $dx * $dx + $dy * $dy;

                if ($distanceSquared <= ($radius*$radius)){
                    $pixelsInONH++;
                    $rgb = $this->originalPhoto->imagecolorat($x, $y);
                    $red = $rgb >> 16;
                    if($red == 255){
                        $i++;
                    }   
                }
            }
        }
        $procentageSaturation = $i/$pixelsInONH*100;
        if($procentageSaturation>5){
            return true;
        }else{
            return false;
        }
    
    }
    
    /**
     * 
     * @throws RuntimeException
     * @throws \RetinaLyze\Exception\LateralityNotAvailableException
     */

    public function startSegmentation() {
        $time_start = microtime(true); 
        
        $this->requestSegmentation();
        $body = $this->waitForStatusCode(2);
        try{
            $this->saveBorderInDB($body->content->discBorder, $body->content->ellipse, (microtime(true) - $time_start));
            //error_log('Total startSegmentation time in seconds: ' . (microtime(true) - $time_start) . ', APIID: ' . $this->apiID . ', ImgID: ' . $this->imageID);
        } catch (Exception $ex) {
            throw new \RuntimeException('Could not save border.');
        }
    }
    
    /**
     * 
     * @throws RuntimeException
     */

    public function rejectSegmentation() {
        try{
            $this->rejectBorderInDB();
        } catch (Exception $ex) {
            throw new \RuntimeException('Could not save border.');
        }
    }
    
    /**
     * Start running analysis on the image
     * @param string $post The post request from the user.
     * @throws Exception
     */

    public function startAnalysis() {
        $time_start = microtime(true); 
        //Get API ID
        $apiIDDBresponse = $this->dbHandler->getGlaucomaResult($this->imageID);
        $apiIDresponse = $apiIDDBresponse->fetch_assoc();
        if($apiIDresponse['glaucomaResult'] !== NULL) {
            throw new \RetinaLyze\Exception\ImageAlreadyAnalyzedException();
        }
        
        //Request segmentation
        $this->requestSegmentation();
        $glaucomaTableID = $this->saveBorderInDB();
        
        $responseBody = $this->waitForStatusCode(4);
        //error_log('Total startAnalysis time in seconds: ' . (microtime(true) - $time_start) . ', APIID: ' . $this->apiID . ', ImgID: ' . $this->imageID);

        $gdfRedThreshold = isset($responseBody->content->gdfRed) ? $responseBody->content->gdfRed : -14.2;
        $gdfOrangeThreshold = isset($responseBody->content->gdfOrange) ? $responseBody->content->gdfOrange : -6.7;
        $gdfYellowThreshold = isset($responseBody->content->gdfYellow) ? $responseBody->content->gdfYellow : 0;
        $cdVertRedThreshold = isset($responseBody->content->cdVertRed) ? $responseBody->content->cdVertRed : 0.63;
        $cdVertOrangeThreshold = isset($responseBody->content->cdVertOrange) ? $responseBody->content->cdVertOrange : 0.60;
        $cdVertYellowThreshold = isset($responseBody->content->cdVertYellow) ? $responseBody->content->cdVertYellow : 0.56;
        $cdAreaRedThreshold = isset($responseBody->content->cdAreaRed) ? $responseBody->content->cdAreaRed : 0.39;
        $cdAreaOrangeThreshold = isset($responseBody->content->cdAreaOrange) ? $responseBody->content->cdAreaOrange : 0.37;
        $cdAreaYellowThreshold = isset($responseBody->content->cdAreaYellow) ? $responseBody->content->cdAreaYellow : 0.33;
        $thresholds = array($gdfRedThreshold, $gdfOrangeThreshold, $gdfYellowThreshold, $cdVertRedThreshold, $cdVertOrangeThreshold, $cdVertYellowThreshold, $cdAreaRedThreshold, $cdAreaOrangeThreshold, $cdAreaYellowThreshold);
        //error_log('Thresholds: ' . print_r($thresholds, true));
        if(isset($responseBody->content->version)){
            $glaucomaVersion = $responseBody->content->version;
        }else{
            $glaucomaVersion = NULL;
        }
        $this->dbHandler->updateAfterGlaucomaAnalyze($this->imageID, $responseBody->content->diagnostic, $glaucomaTableID, (microtime(true) - $time_start), $this->checkEyeReturned, $responseBody->content->cdRatioArea, $responseBody->content->cdRatioVert, $responseBody->content->gdf, $responseBody->content->quality, $thresholds, $glaucomaVersion);
        
        $hbResponse = $this->httpClient->request('GET', $this->urlPrefix . 'hb?token=' . $this->config['insoft_token'] . '&id=' . $responseBody->content->id);
        //error_log($this->urlPrefix . 'hb?token=' . $this->config['insoft_token'] . '&id=' . $responseBody->content->id);
        $photoStorageFilesystem = FilesystemFactory::create($this->config['RetinaLyzeWebPath'], $this->config['photo_storage_filesystem']);
        $extraConfig = array('ServerSideEncryption' => 'AES256');
        $dir = $this->baseDir;
        $fullname = $dir . $this->imageID . "_hb.png";
        $photoStorageFilesystem->put($fullname, (string)$hbResponse->getBody(), $extraConfig);
        
        $hbSectorsResponse = $this->httpClient->request('GET', $this->urlPrefix . 'hbSectors?token=' . $this->config['insoft_token'] . '&id=' . $responseBody->content->id);
        $fullname = $dir . $this->imageID . "_hbSectors.png";
        $photoStorageFilesystem->put($fullname, (string)$hbSectorsResponse->getBody(), $extraConfig);
        
        $discCupResponse = $this->httpClient->request('GET', $this->urlPrefix . 'discCup?token=' . $this->config['insoft_token'] . '&id=' . $responseBody->content->id);
        $fullname = $dir . $this->imageID . "_discCup.png";
        $photoStorageFilesystem->put($fullname, (string)$discCupResponse->getBody(), $extraConfig);
        
    }
    
    public function getAPIID() {
        return $this->apiID;
    }

    private function requestSegmentation() {
        //Get image info
        $imgInfo = $this->dbHandler->getSingleAnalyzedImgInfoWithUsernameAndRef($this->imageID);
        $imgInfoRow = $imgInfo->fetch_assoc();
        $laterality = $imgInfoRow['region'] == 'OS' ? 'L' : ($imgInfoRow['region'] == 'OD' ? 'R' : NULL);
        $fp = $this->originalPhoto->getFundusPhotoStream();
        
        //Get camera identifier
        $cameraIdentifier = $this->getAndSetCameraID($imgInfoRow);

        if ($fp != false) {
            $attributes = [
                'multipart' => [
                    [
                        'name' => 'token',
                        'contents' => $this->config['insoft_token']
                    ],
                    [
                        'name' => 'autoConfirm',
                        'contents' => true
                    ],
                    [
                        'name' => 'eye',
                        'contents' => $laterality
                    ],
                    [
                        'name' => 'centerX',
                        'contents' => (int) 0
                    ],
                    [
                        'name' => 'centerY',
                        'contents' => (int) 0
                    ],
                    [
                        'name' => 'camera',
                        'contents' => $cameraIdentifier
                    ],
                    [
                        'name' => 'image',
                        'contents' => $fp,
                        'filename' => $this->imageID . '.jpg',
                        'headers' => [
                            'Content-Type' => 'image/jpeg'
                        ]
                    ]
                ]
            ];
            $response = $this->httpClient->request('POST', $this->urlPrefix . 'upload', $attributes);
        }else{
            throw new \RuntimeException('Could not get photostream.');
        }
        $body = json_decode($response->getBody());
        if(!empty($body->content->id)){
            $this->apiID = $body->content->id;
            return $body;
        }else{
            throw new \RuntimeException('Could not perform upload POST request. ' . $this->urlPrefix . 'upload' . print_r(json_decode($response->getBody()), true));
        }
    }

    private function waitForStatusCode($statusCode) {
        $tries = 0;
        while ($tries < 60) {
            sleep(1);
            try{
                $response = $this->httpClient->request('GET', $this->urlPrefix . 'status?token=' . $this->config['insoft_token'] . '&id=' . $this->apiID);
            } catch (Exception $ex) {
                throw new \RuntimeException('Could not get status. ImageID: ' . $this->imageID . '. APIID: ' . $this->apiID );
            }
            $body = json_decode($response->getBody());
            if ($body->content->statusCode == $statusCode) {
                if(isset($body->content->checkEye) && $body->content->checkEye === true){
                    $this->checkEyeReturned = true;
                }else if(isset($body->content->checkEye) && $body->content->checkEye === false){
                    $this->checkEyeReturned = false;
                }else{
                    $this->checkEyeReturned = NULL;
                }
                return $body;
            }else if ($body->content->statusCode == 6){
                if(!empty($body->content->errorCode)){
                    if($body->content->errorCode == 6){
                        $this->saveSaturationInDB();
                        throw new ImageIsSaturatedException('Image is saturated');
                    }else{
                        $this->saveAnalysisErrorInDB($body->content->errorCode);
                        throw new GlaucomaAnalysisException('Image cannot be analyzed');
                    }
                }else{
                    $this->saveAnalysisErrorInDB();
                    throw new GlaucomaAnalysisException('Image cannot be analyzed');
                }
            }else if ($body->content->statusCode == 5){
                $this->saveSegmentationErrorInDB();
                throw new GlaucomaSegmentationException('Image cannot be segmentated');
            }
            $tries++;
        }
        throw new \RuntimeException('Request timed out. ImageID: ' . $this->imageID . '. APIID: ' . $this->apiID);
    }

    private function createBorderImage($discCoordinates) {
        $pointArray = $discCoordinates;
        $this->originalPhoto->addONHBorder($pointArray);
        $fullnameBorderFile = $this->baseDir . $this->imageID . "_border.jpg";
        $this->originalPhoto->savePhoto($fullnameBorderFile);
    }
    
    private function saveBorderInDB(){
        return $this->dbHandler->initializeGlaucomaAnalysis($this->imageID, $this->apiID);
    }
    
    private function rejectBorderInDB(){
        $this->dbHandler->rejectGlaucomaBorder($this->imageID, 1);
    }
    
    private function saveSaturationInDB(){
        $this->dbHandler->updateIsSaturated($this->imageID, 1);
    }
    
    private function saveAnalysisErrorInDB($errorCode = null){
        $this->dbHandler->updateGlaucomaAnalysisError($this->imageID, 1, $errorCode);
    }
    
    private function saveSegmentationErrorInDB(){
        $this->dbHandler->updateGlaucomaSegmentationError($this->imageID, 1);
    }
    
    private function getAndSetCameraID($imgInfo) {        
        //Check if camera ID exists on camera. If it does then return it.
        if($imgInfo["cameraID"]==NULL){
            if($imgInfo["camera"]==NULL){
                throw new \RetinaLyze\Exception\CameraIdentifierNotAvailableException('Camera identifier not available');
            }else{
                $cameraIdentifierDB = $this->dbHandler->getGlaucomaCameraIdentifierFromUserID($this->userID);
                if(!empty($cameraIdentifierDB->num_rows) && $cameraIdentifierDB->num_rows === 1){
                    $cameraIdentifierArray = $cameraIdentifierDB->fetch_assoc();
                    $this->dbHandler->updateCameraOnImageInfo($this->imageID, $imgInfo["camera"]);
                    return $cameraIdentifierArray["glaucomaCameraIdentifier"];
                }else{
                    throw new \RetinaLyze\Exception\CameraIdentifierNotAvailableException('Camera identifier not available');
                }
            }
        }else{
            $cameraIdentifierDB = $this->dbHandler->getGlaucomaCameraIdentifierFromImgID($this->imageID);
            if(!empty($cameraIdentifierDB->num_rows) && $cameraIdentifierDB->num_rows === 1){
                $cameraIdentifierArray = $cameraIdentifierDB->fetch_assoc();
                return $cameraIdentifierArray["glaucomaCameraIdentifier"];
            }else{
                throw new \RetinaLyze\Exception\CameraIdentifierNotAvailableException('Camera identifier not available');
            }
        }
    }

}
