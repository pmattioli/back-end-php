<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Images;

use RetinaLyze\Database\DatabaseHandler;
use RetinaLyze\Utils\Config;
use RetinaLyze\Files\FilesystemFactory;
use RetinaLyze\Exception\RuntimeException;
use RetinaLyze\Exception\FileDoesNotExist;
use RetinaLyze\Exception\ExtensionNotSupported;
use RetinaLyze\Exception\NameChangeDetected;
use RetinaLyze\Dicom\DicomExtractor;
/**
 * A class which handles fundus images.
 *
 * @author mom
 */
class FundusPhoto {
    private $photo;
    private $photoPath;
    private $resizedPhoto;
    private $width;
    private $height;
    private $config;
    private $photoStorageFilesystem;
    
    /**
     * 
     * @param string $photoPath the path for the photo
     * @throws FileDoesNotExist
     * @throws RuntimeException
     */
    
    function __construct($photoPath = null) {
        
        //Get config
        $this->config = Config::getConfig();
        $this->photoStorageFilesystem = FilesystemFactory::create($this->config['RetinaLyzeWebPath'], $this->config['photo_storage_filesystem']);
        if($photoPath != null){
            $photoPathWOORG = str_replace("_org", "", $photoPath);
            if($this->photoStorageFilesystem->has($photoPath)){
                $photoData = $this->photoStorageFilesystem->read($photoPath);
            } elseif ($this->photoStorageFilesystem->has($photoPathWOORG)) {
                $photoData = $this->photoStorageFilesystem->read($photoPathWOORG);
                $photoPath = $photoPathWOORG;
            }else{
                throw new RuntimeException("No photos found on either of these paths: " . $photoPath . ", and: " . $photoPathWOORG);
            }
            if($photoData === false){
                throw new RuntimeException("Could not get photo from path: " . $photoPath);
            }
            $this->photo = imagecreatefromstring($photoData);
            if($this->photo === false){
                throw new RuntimeException("Could not create photo from path: " . $photoPath);
            }
            // Determine the size of the photo
            list($this->width, $this->height) = getimagesizefromstring($photoData);

            $this->photoPath = $photoPath;
        }
    }
    
    private function getAnalyzingPhotoSize() {
        if ($this->width > 1536) {
            $newWidth = 1536;
            $widthFactor = $this->width / $newWidth;
            $newHeight = $this->height / $widthFactor;
        } else { //If the image is smaller.
            $newWidth = $this->width;
            $newHeight = $this->height;
        }
        return array("width" => $newWidth, "height" => $newHeight);
    }
    
    /**
     * Get the size of the fundus photo
     * 
     * @return array
     */
    
    public function getPhotoSize() {
        return array("width" => $this->width, "height" => $this->height);
    }
    
    /**
     * Get the color values at a coordinate
     * 
     * @param int $x x coordinate
     * @param int $y y coordinate
     * @return int the index of the color.
     */
    
    public function imagecolorat($x, $y) {
        return \imagecolorat($this->photo, $x, $y);
    }
    
    /**
     * Resize the photo
     * 
     * @throws RuntimeException
     */
    
    public function resizePhoto(){
        $newSize = $this->getAnalyzingPhotoSize();
            if($newSize["width"] != $this->width){
                   $this->resizedPhoto = imagecreatetruecolor($newSize["width"], $newSize["height"]);
            if($this->resizedPhoto === false){
                throw new RuntimeException("Could not create canvas for resized photo");
            }
            $resizeStatus = imagecopyresampled($this->resizedPhoto, $this->photo, 0, 0, 0, 0, $newSize["width"], $newSize["height"], $this->width, $this->height);
            if($resizeStatus === false){
                throw new RuntimeException("Could not resize photo");
            }
        }else{
            $this->resizedPhoto = $this->photo;
        }
    }
    
    /**
     * Add horus overlay to resized photo
     * 
     * @throws RuntimeException
     */
    
    public function addHorusOverlay(){
        $overlayPath = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR . "overlay_horus_small_v2.png";	
        $overlayImage = imagecreatefrompng($overlayPath);
        if($overlayImage === false){
            throw new RuntimeException("Could not get overlay photo");
        }
        $saveAlphaStatus = imagesavealpha($overlayImage, true);
        if($saveAlphaStatus === false){
            throw new RuntimeException("Could not save alpha overlay photo");
        }
        $copyStatus = imagecopyresampled($this->resizedPhoto, $overlayImage, 0, 0, 0, 0, imagesx($this->resizedPhoto), imagesy($this->resizedPhoto), imagesx($overlayImage), imagesy($overlayImage));
        imagedestroy($overlayImage);
        if($copyStatus === false){
            throw new RuntimeException("Could not save resulting photo with overlay");
        }
    }
    
    public function addONHBorder($pointArray){
        $col_ellipse = imagecolorallocate($this->photo, 255, 255, 255);
        $last_point = null;
        foreach ($pointArray as $value) {
            if($last_point == null){
                $last_point = $value;
                continue;
            }
            imageline($this->photo, $last_point[0], $last_point[1], $value[0], $value[1], $col_ellipse);
            $last_point = $value;
        }
    }
    
    /**
     * 
     * @param string $toPath Path to where the copy should be
     * @throws RuntimeException
     */
    
    public function copyPhoto($toPath){
        if (!$this->photoStorageFilesystem->has($toPath)) {
            $copyStatus = $this->photoStorageFilesystem->copy($this->photoPath, $toPath);
            if($copyStatus === false){
                throw new RuntimeException("Could copy photo to path: " . $toPath);
            }
        }
    }
    
    /**
     * 
     * @param string $toPath Path to where the photo should be saved
     * @throws RuntimeException
     */
    
    public function savePhoto($toPath){
        // Save image
        $putStream = tmpfile();
            
        imagejpeg($this->photo, $putStream);
        rewind($putStream);
        $extraConfig = array('ServerSideEncryption' => 'AES256');
        $saveStatus = $this->photoStorageFilesystem->putStream($toPath, $putStream, $extraConfig);

        if (is_resource($putStream)) {
            fclose($putStream);
        }
        if($saveStatus === false){
            throw new RuntimeException("Could not save photo to path: " . $toPath);
        }
    }
    
    /**
     * 
     * @param string $toPath Path to where the photo should be saved
     * @throws RuntimeException
     */
    
    public function saveResizedPhoto($toPath){
        // Save image as string
        $putStream = tmpfile();
        if($putStream === false){
            throw new RuntimeException("Could not create tmp stream.");
        }
        $saveImageToStream = imagejpeg($this->resizedPhoto, $putStream);
        if($saveImageToStream === false){
            throw new RuntimeException("Could not save file to stream.");
        }
        rewind($putStream);
        $extraConfig = array('ServerSideEncryption' => 'AES256');
        $saveStatus = $this->photoStorageFilesystem->updateStream($toPath, $putStream, $extraConfig);

        if (is_resource($putStream)) {
            fclose($putStream);
        }
        if($saveStatus === false){
            throw new RuntimeException("Could not save resized photo to path: " . $toPath);
        }
    }
    
    public function getResizedPhoto(){
        return $this->resizedPhoto;
    }
    
    public function getFundusPhotoStream(){
        return $this->photoStorageFilesystem->readStream($this->photoPath);
    }
    
    /**
     * Upload a dicom fundus photo to storage and meta data to db
     * 
     * @param File $file
     * @param string $username
     * @param string $userID
     * @param boolean $failOnNameChange
     * @param boolean $overwriteName
     * @param string $imageKeynameUnescaped
     * @param boolean $useInstanceUUID
     * @throws ExtensionNotSupported
     * @throws \RuntimeException
     */
    public function uploadDicomFundusPhoto($file, $username, $userID, $failOnNameChange = false, $overwriteName = false, $imageKeynameUnescaped = 'image', $useInstanceUUID = false) {
        //If image key name contains . then it is escaped by PHP with replacing it with _
        $imageKeyname = str_replace('.', '_', $imageKeynameUnescaped);
        
        //Check extention
        if(!(strtolower(pathinfo($file[$imageKeyname]['name'], PATHINFO_EXTENSION)) == "dcm")){
            throw new ExtensionNotSupported("The extension of the file is not DCM.");
        }
        $dicomFilePath = $file[$imageKeyname]['tmp_name'];
        
        $jpgTempFile = tmpfile();
        $jpgTempFilePath = stream_get_meta_data($jpgTempFile)['uri'];
        DicomExtractor::convertToJpg($dicomFilePath, $jpgTempFilePath);
        $clientDetails = DicomExtractor::getTags($dicomFilePath);
        
        $dbh = new DatabaseHandler();
        
        if($useInstanceUUID){
            $dicomFileName = $clientDetails["instanceUUID"];
            $checkFileNameExistsDB = $dbh->checkIfFilenameExists($dicomFileName, $userID);
            if(!empty($checkFileNameExistsDB->num_rows)){
                $checkFileNameExists = $checkFileNameExistsDB->fetch_assoc();
                return $checkFileNameExists['id'];
            }
        }else{
            $dicomFileName = $file[$imageKeyname]['name'];
        }
        
        //Get user settings
        $userSettings = $dbh->getUserSettingsFromID($userID)->fetch_assoc();
        if(isset($userSettings['saveCustomerName']) && $userSettings['saveCustomerName'] == 0){
            $clientDetails['name'] = "";
        }
        $pid = md5($dicomFileName . time());
        
        if(empty($clientDetails['id'])){
            $id = $dbh->insertNotAnalyzedImgWithoutCustomer($dicomFileName, $userID, $pid, $clientDetails['region']);
        }else{
            try{
                $id = $dbh->insertNotAnalyzedImg($dicomFileName, $clientDetails['id'], $clientDetails['name'], $userID, $failOnNameChange, $overwriteName, $clientDetails['region'], $pid);
            } catch (NameChangeDetected $ex) {
                $nameChangeEx = $ex;
                $id = $dbh->insertNotAnalyzedImgWithoutCustomer($dicomFileName, $userID, $pid, $clientDetails['region']);
            }
        }
        if (empty($id)) {
            throw new \RuntimeException("Couldn't add data to database.");
        }
        
        //Upload jpg file
        if(isset($userSettings["s3usingUserID"]) && $userSettings["s3usingUserID"] == 1){
            $uploaddir = '/' . $userID . '/RetImages/';
        }else{
            $uploaddir = '/Analyzed/' . $username . '/RetImages/';
        }
        
        $uploadfile = $uploaddir . $id . "_org.jpg";
        $stream = fopen($jpgTempFilePath, 'r+');
        if($stream === false){
            $dbh->removeNotAnalyzedImg($id);
            $errorArray = error_get_last();
            throw new \RuntimeException("Could not open stream to the tmp file uploaded. Error: " . $errorArray['message'] . ", TMP name: " . $file[$imageKeyname]['tmp_name']);
        }
        $extraConfig = array('ServerSideEncryption' => 'AES256');
        $statusWrite = $this->photoStorageFilesystem->writeStream($uploadfile, $stream, $extraConfig);
        if (is_resource($stream)) {
            fclose($stream);
        }
        if($statusWrite === false){
            $dbh->removeNotAnalyzedImg($id);
            throw new \RuntimeException("Could not write stream for the file uploaded.");
        }
        if(isset($nameChangeEx)){
            $nameChangeEx->setImgID($id);
            throw $nameChangeEx;
        }
        
        $uploadfile = $uploaddir . $id . ".dcm";
        $streamDicomFile = fopen($dicomFilePath, 'r+');
        if($streamDicomFile === false){
            $dbh->removeNotAnalyzedImg($id);
            $errorArray = error_get_last();
            error_log("Error: Could not open stream to the dicom file uploaded. Error: " . $errorArray['message'] . ", TMP name: " . $file[$imageKeyname]['tmp_name']);
        }
        $this->photoStorageFilesystem->writeStream($uploadfile, $streamDicomFile, $extraConfig);
        if (is_resource($streamDicomFile)) {
            fclose($streamDicomFile);
        }
        
        return $id;
    }
    
    /**
     * Upload a jpeg fundus photo to storage and meta data to db
     * 
     * @param File $file
     * @param string $customerID
     * @param string $customerName
     * @param string $username
     * @param string $userID
     * @throws ExtensionNotSupported
     * @throws \RuntimeException
     * @throws NameChangeDetected
     */
    public function uploadFundusPhoto($file, $customerID, $customerName, $username, $userID, $failOnNameChange = false, $overwriteName = false, $region = null, $cameraID = null) {
        //Check for file upload error
        if(!empty($file['image']['error']) && $file['image']['error'] !== 0){
            throw new \RuntimeException("Error when uploading image. Error in _FILES error code: " . $file['image']['error']);
        }

        //Check extention
        if(!(strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)) == "jpg" || 
                strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION)) == "jpeg")){
            throw new ExtensionNotSupported("The extension of the file is not JPG.");
        }
        
        //Check content type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $type = finfo_file($finfo, $file['image']['tmp_name']);
        finfo_close($finfo);
        
        if($type !== "image/jpeg"){
            throw new ExtensionNotSupported("The the content type is not image/jpeg.");
        }
        
        $filename = utf8_encode($file['image']['name']);

        if (pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) == "jpeg" || pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION) == "JPEG") {
            $filename = substr($filename, 0, -4) . "jpg";
        }

        if (strlen($filename) > 150) {
            throw new \RuntimeException("The filename is too long, please make it shorter.");
        }
        
        $dbh = new DatabaseHandler();
        
        //Get user settings
        $userSettings = $dbh->getUserSettingsFromID($userID)->fetch_assoc();
        
        if(isset($userSettings['saveCustomerName']) && $userSettings['saveCustomerName'] == 0){
            $customerName = "";
        }
        
        if(empty($cameraID)){
            $userInfo = $dbh->getUserAdditionalInfoFromID($userID)->fetch_assoc();
            if(isset($userInfo['camera']) && is_numeric($userInfo['camera'])){
                $cameraID = $userInfo['camera'];
            }else{
                $cameraID = null;
            }
        }
        
        
        
        $pid = md5($file['image']['name'] . time());
        
        if($customerID === NULL){
            $id = $dbh->insertNotAnalyzedImgWithoutCustomer($filename, $userID, $pid, $cameraID);
        }else{
            try{
                $id = $dbh->insertNotAnalyzedImg($filename, $customerID, $customerName, $userID, $failOnNameChange, $overwriteName, $region, $pid, $cameraID);
            } catch (NameChangeDetected $ex) {
                $nameChangeEx = $ex;
                $id = $dbh->insertNotAnalyzedImgWithoutCustomer($filename, $userID, $pid, $region, $cameraID);
            }
        }
        if (empty($id)) {
            throw new \RuntimeException("Couldn't add data to database.");
        }
        if(isset($userSettings["s3usingUserID"]) && $userSettings["s3usingUserID"] == 1){
            $uploaddir = '/' . $userID . '/RetImages/';
        }else{
            $uploaddir = '/Analyzed/' . $username . '/RetImages/';
        }
        $uploadfile = $uploaddir . $id . "_org.jpg";
        $stream = fopen($file['image']['tmp_name'], 'r+');
        if($stream === false){
            $dbh->removeNotAnalyzedImg($id);
            $errorArray = error_get_last();
            throw new \RuntimeException("Could not open stream to the tmp file uploaded. Error: " . $errorArray['message'] . ", TMP name: " . $file['image']['tmp_name']);
        }
        
        
        $extraConfig = array('ServerSideEncryption' => 'AES256');
        $statusWrite = $this->photoStorageFilesystem->writeStream($uploadfile, $stream, $extraConfig);
        if (is_resource($stream)) {
            fclose($stream);
        }
        
        if($cameraID == 11){
            $image = \imagecreatefromjpeg($file['image']['tmp_name']);
            if(\imagecolorat($image, 0, 0) === 16777215 && \imagecolorat($image, 2, 0) === 16777215){
                $dbh->updateCameraOnImageInfo($id, 26);
            }
        }
        
        if($statusWrite === false){
            $dbh->removeNotAnalyzedImg($id);
            throw new \RuntimeException("Could not write stream for the file uploaded.");
        }
        if(isset($nameChangeEx)){
            $nameChangeEx->setImgID($id);
            throw $nameChangeEx;
        }
        return $id;
    }
    
    public function addClientInfo($userID, $clientID, $clientName, $imgID, $failOnNameChange = false, $overwriteName = false) {
        $dbh = new DatabaseHandler();
        $customerID = $dbh->createCustomer($clientID, $clientName, $userID, $failOnNameChange, $overwriteName);
        $dbh->updateCustomerIDOnImg($customerID, $imgID);
    }
    
    public function getNameChangedPopup($imgID, $clientID, $newClientName, $oldClientName, $uploading = false){
        ob_start()
        ?>
                <div style="display:none">
                <div id="comfirmNameChange" class="band-default">
                    <form id="comfirmNameChangeForm" class="appnitro"  method="post" action="">
                       <input name="imgid" type="hidden" value="<?php echo $imgID ?>"/>
                       <input name="clientID" type="hidden" value="<?php echo $clientID ?>"/>
                       <input name="newClientName" type="hidden" value="<?php echo $newClientName ?>"/>
                       <input name="oldClientName" type="hidden" value="<?php echo $oldClientName ?>"/>
                       <input name="uploading" type="hidden" value="<?php echo $uploading === true ? "1" : "0"; ?>"/>
                       <input name="type" type="hidden" value="comfirmNameChange"/>
                       <ul>
                           <li>
                               <h3><?php echo _("New name detected") ?></h3>
                               <p><?php echo _("You have inputted a new name for an existing client. Do you want to change the name of the client?") ?></p>
                               <p><?php echo "<b>" . _("Client reference") . ":</b> " . $clientID ?></p>
                               <p><?php echo "<b>" . _("Existing name") . ":</b> " . $oldClientName; ?></p>
                               <p><?php echo "<b>" . _("New name") . ":</b> " . $newClientName ?></p>

                           </li>
                           <li class="buttons">
                               <input id="comfirmNameChangeForm" class="btn btn-success" type="submit" name="newname" value="<?php echo _("Use new name") ?>" />
                               <input id="comfirmNameChangeForm" class="btn btn-success" type="submit" name="oldname" value="<?php echo _("Use existing name") ?>" />
                               <input id="comfirmNameChangeForm" class="btn red" type="submit" name="cancel" value="<?php echo _("Cancel") ?>" />
                           </li>
                       </ul>
                    </form>
               </div>
               </div>
               <script type="text/javascript">
                   jQuery(document).ready(function () {
                       jQuery.fancybox.open(jQuery('#comfirmNameChange'));
                   });
               </script>
                <?php
                $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
    

}
