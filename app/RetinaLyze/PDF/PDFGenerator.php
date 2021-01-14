<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\PDF;

use RetinaLyze\Database\DatabaseHandler;
use RetinaLyze\Internationalization\LanguageHandler;

use RetinaLyze\Utils\Config;
use RetinaLyze\Analysis\ResultInterpreter;
use mikehaertl\wkhtmlto\Pdf;
use RetinaLyze\Files\PDFLogo;
use RetinaLyze\Utils\DistributorInfo;

/**
 * Description of PDFGenerator
 *
 * @author mom
 */
class PDFGenerator {
    private $config;
    private $userType;
    private $userIDOfOwner;
    private $imgID;
    private $imgInfo;
    private $pdfSettings;
    private $dbh;
    private $userInfo;
    private $additionalUserInfo;
    private $userSettings;
    private $demoStatus = false;
    private $lh;
    private $pdf;


    public function __construct($userType, $userID, $imgID, $demoInfo = NULL) {
        $this->config = Config::getConfig();
        $this->userType = $userType;
        $this->imgID = $imgID;
        $this->dbh = new DatabaseHandler();
 
        //Get the image info
        if($demoInfo == NULL){    
            if($imgID !== NULL){
                $this->getImageInfo();
                $this->userIDOfOwner = $this->imgInfo['userID'];
            }
        }else{
            $this->getDemoImageInfo($demoInfo, $userID);
            $this->demoStatus = true;
            $this->userIDOfOwner = $userID;
        }
        //Get user info
        $this->getUserInfo();
        
        //Get pdf settings
        $this->getPDFSettings();
    }
    
    private function getImageInfo(){
        if($this->userType==3 || $this->userType==1){
            $imgInfo = $this->dbh->getSingleAnalyzedImgInfoWithUsernameAndRef($this->imgID);
	}else{
            $imgInfo = $this->dbh->getSingleAnalyzedImgInfoWithRef($this->imgID);
	}
        $imgInfoRow = $imgInfo->fetch_assoc();
        $this->imgInfo = $imgInfoRow;
    }
    
    private function getDemoImageInfo($demoData, $userID){
        $imgData = array();
        $imgData['timeAnalyzedDR'] = "2016-09-31 00:00:00";
        $imgData['timeAnalyzedAMD'] = "2016-09-31 00:00:00";
        $imgData['timeGlaucomaStarted'] = "2016-09-31 00:00:00";
        $imgData['pid'] = "1234";
        $imgData["name"] = "Snotra";
        $imgData["socialsecurity_no"] = "1001";
        $imgData['userID'] = $userID;
        $imgData['annotationsDR'] = $demoData["annotationsDR"];
        $imgData['annotationsAMD'] = $demoData["annotationsAMD"];
        $imgData['glaucomaResult'] = $demoData["glaucomaResult"];
        $imgData['refID'] = NULL;
        $imgData["status"] = $demoData["status"];
        $imgData["answerTypeDR"] = NULL;
        $imgData["answerTypeAMD"] = NULL;
        $imgData["answerComment"] = $demoData["answerComment"];
        $imgData["answerComment"] = $demoData["answerComment"];
        $imgData["isSaturated"] = 0;
        $imgData["quality"] = 1;
        $this->imgInfo = $imgData;
    }
    
    private function getUserInfo(){
        $userInfo = $this->dbh->getUserInfoFromID($this->userIDOfOwner);
        $userInfoRow = $userInfo->fetch_assoc();
        $this->userInfo = $userInfoRow;
        
        $this->additionalUserInfo = $this->dbh->getUserAdditionalInfoFromID($this->userIDOfOwner)->fetch_assoc();
        
        $this->userSettings = $this->dbh->getUserSettingsFromID($this->userIDOfOwner)->fetch_assoc();
        
    }
    
    private function getPDFSettings(){
        if(!empty($this->userInfo) && $this->userInfo["chainID"] != NULL){
            $pdfSettingsDB = $this->dbh->getChainPDFSettings($this->userInfo["chainID"]);
            $this->pdfSettings = $pdfSettingsDB->fetch_assoc();
        }else{
            $this->pdfSettings = NULL;
        }
    }
    
    public function startPDF($lang){
        $this->lh = new LanguageHandler($lang);
        //Create PDF and config
        $this->pdf = new Pdf(array(
            'no-outline',         // Make Chrome not complain
            'margin-top'    => 0,
            'margin-right'  => 0,
            'margin-bottom' => 0,
            'margin-left'   => 0,


            // Default page options
            'encoding' => 'UTF-8',
            'disable-smart-shrinking',
        ));
        $this->pdf->binary = $this->config["wkhtmltopdf_path"];
    }
    
    public function addPage($imageID = NULL) {
        if($imageID !== NULL){
            $this->imgID = $imageID;
            $this->getImageInfo();
            $this->userIDOfOwner = $this->imgInfo['userID'];
        }
        //Get template file
        $htmlTemplate = file_get_contents(__DIR__ . "/../../../pdf/pdftemplate.html");
        
        $html = $this->insertHTMLTag($htmlTemplate);
        $html = $this->insertURLs($html);
        $html = $this->insertCustomerLogoBlock($html);
        $html = $this->insertFooterText($html);
        $html = $this->insertPatientDataLabels($html);
        $html = $this->insertPatientData($html);
        $html = $this->insertClientInfo($html);
        $html = $this->insertOpticianInfo($html);
        $html = $this->insertDearText($html);
        $html = $this->insertResultRelatedData($html);
        $this->pdf->addPage($html);
    }
    
    public function getPDF(){
        ob_start();
        if($this->pdf->send()){
            header_remove(); 
            $pdfReturn = ob_get_clean();
            return $pdfReturn;
        }else{
            ob_get_clean();
            return NULL;
        }
    }
    
    /**
     * This method gerates a PDF
     * 
     * @param string $lang
     */
    
    public function generate($lang, $savePath = NULL, $makeBrowserSave = false){
        if(isset($this->imgInfo["quality"]) && $this->imgInfo["quality"] == "0" && empty($this->imgInfo["answerComment"])){
            echo _("Ungradable");
            die();
        }
        
        $this->startPDF($lang);
        
        $this->addPage(NULL);
        
        
        if($savePath == NULL){
            if($makeBrowserSave){
                if (!$this->pdf->send($this->imgInfo["socialsecurity_no"] . ".pdf")) {
                    echo _('Could not create PDF');
                    error_log('Error: Could not create PDF: '.$this->pdf->getError());
                }
            }else{
                if (!$this->pdf->send()) {
                    echo _('Could not create PDF');
                    error_log('Error: Could not create PDF: '.$this->pdf->getError());
                }
            }
        }else{
            if(!$this->pdf->saveAs($savePath)){
                echo _('Could not create PDF');
                error_log('Error: Could not create PDF: '.$this->pdf->getError());
            }
        }
    }
    
    private function insertHTMLTag($html) {
        if($this->lh->isRTL()){
            $htmlTag = '<html dir="rtl" lang="' . $this->lh->getCurrentLang() . '">';
        }else{
            $htmlTag = '<html lang="' . $this->lh->getCurrentLang() . '">';
        }                
        $html = str_replace("%html_tag%", $htmlTag, $html);        
        
        return $html;
    }
    
    private function insertURLs($html) {
        if($this->lh->getCurrentLang() == "zh_CN"){
            $html = str_replace("%css_url%", $this->config["base_url"] . 'pdf/pdf_opensans_SC.css', $html);
        }else{
            $html = str_replace("%css_url%", $this->config["base_url"] . 'pdf/pdf_opensans.css', $html);
        }
        if(empty($this->config['site']) || $this->config['site'] == 'retinalyze'){
            $html = str_replace("%retinalyze_logo_url%", $this->config["base_url"] . 'wp-content/themes/Metrolium/assets/images/retinalyze_print_logo.jpg', $html);
        }else{
            $html = str_replace("%retinalyze_logo_url%", $this->config["base_url"] . 'wp-content/themes/Optomed/assets/images/avenue-logo-blue.png', $html);
        }
        if($this->demoStatus){
            $html = str_replace("%fundus_image_url%", $this->config["base_url"] . 'pdf/demofundusimage.jpg', $html);
        }else{
            $html = str_replace("%fundus_image_url%", $this->config["base_url"] . 'images/image.php?type=ret&id=' . $this->imgID . '&username=' . $this->userInfo["username"] . '&paid=' . $this->imgInfo['pid'], $html);
        }
        
        return $html;
    }
    
    private function insertCustomerLogoBlock($html) {
        if($this->pdfSettings != NULL && $this->pdfSettings['logo'] == "1"){
            $logoBlock = '  <div id="right-box" class="logo">
                            <img alt="logo" src="%retinalyze_logo_url%">
                        </div>';
            $logoBlock = str_replace("%retinalyze_logo_url%", PDFLogo::getPublicLogoPath($this->pdfSettings['pdfChainID']), $logoBlock);
            $html = str_replace("%block_customer_logo%", $logoBlock, $html);
        }else{
            $html = str_replace("%block_customer_logo%", "", $html);
        }
        
        return $html;
    }
    
    private function insertFooterText($html) {
        $distributorInfoClass = new DistributorInfo();
        $distributorID = $distributorInfoClass->getDistributorID($this->additionalUserInfo["country"]);
        $distributorInfo = $distributorInfoClass->getDistributorInfo($distributorID);
        $htmlPrefooter = '<p class="bold_text">' . str_replace("\n", '<br>', _("The screening and information provided do not replace a consultation with an ophthalmologist, who will be able to give you a more complete assessment of your vision and general eye health. If you are already attending regular checkups with an ophthalmologist, have a scheduled appointment with an ophthalmologist and/or have problems with your vision, you should always keep attending your appointment(s) and/or seek medical attention.")); 
        $htmlPrefooter .= '</p>';
        $htmlFooter = '';
        if(empty($this->config['site']) || $this->config['site'] == 'retinalyze'){
            $htmlFooter .= '<p>' . _("Get more information about the RetinaLyze System at www.retinalyze.com") . '</p>';
        }
        $htmlFooter .= '<p class="smaller">' . $this->lh->replaceSystemName(_("%system% has been scientifically tested and CE-marked according to Medical Device Directive 93/42/EEC from the 14th of June 1993 and Declaration 1263 from December 2008. %company% or any supplier hereof is not liable for any damages arising out of or in connection with the use or performance of this information, whether in an action of contract, negligence, or other tortious action. In states or areas which do not allow some or all of the above limitations of liability, liability shall be limited to the greatest extent allowed by law.")) . '</p>';
        $html = str_replace("%block_prefooter%", $htmlPrefooter, $html);
        $html = str_replace("%block_footer%", $htmlFooter, $html);
        $companyInfo = $distributorInfo["companyName"];
        if($distributorInfo["mailAdress"] != NULL){
            $companyInfo .= " | " . $distributorInfo["mailAdress"];
        }
        if($distributorInfo["phoneNumber"] != NULL){
            $companyInfo .= " | " . $distributorInfo["phoneNumber"];
        }
        if(!empty($this->config['site']) && $this->config['site'] == 'optomed'){
            $html = str_replace("%company_info%", "", $html);
        }else{
            $html = str_replace("%company_info%", $companyInfo, $html);
        }
        return $html;
    }
    
    private function insertPatientData($html) {
        //Get time
        $timeDR = strtotime($this->imgInfo['timeAnalyzedDR']);
        $timeAMD = strtotime($this->imgInfo['timeAnalyzedAMD']);
        $timeGlaucoma = strtotime($this->imgInfo['timeGlaucomaStarted']);
        $heighestTime = max($timeDR, $timeAMD, $timeGlaucoma);
        $timeStamp = date('d/m/Y', $heighestTime);
        $html = str_replace("%timestamp%", $timeStamp, $html);
        $html = str_replace("%screening_id%", $this->config["region_prefix"] . $this->imgID, $html);
        return $html;
    }
    
    private function insertPatientDataLabels($html) {
        $html = str_replace("%date_screening_label%", _("Date of screening"), $html);
        $html = str_replace("%screening_id_label%", _("Screening id"), $html);
        return $html;
    }
    
    private function insertOpticianInfo($html) {
        if($this->pdfSettings != NULL && $this->pdfSettings['showShopName'] == "1"){
            $opticianInfoBlock = '  <div class="table-row">
                                        <div class="table-cell"><p class="table-label">%optician_label%:</p></div><div class="table-cell"><p class="table-data">%optician_name%</p></div>
                                    </div>';
            $opticianInfoBlock = str_replace("%optician_label%", _("Optician"), $opticianInfoBlock);
            $opticianNameBlock = $this->additionalUserInfo['companyName'];
            if($this->pdfSettings['showShopAddress'] == "1"){
                $opticianNameBlock .= '<br />' . $this->additionalUserInfo['address'] . '<br />' . $this->additionalUserInfo['zipcode'] . ' ' . $this->additionalUserInfo['city'];
            }
            if($this->pdfSettings['showShopPhonenumber'] == "1"){
                $opticianNameBlock .= '<br />' . $this->additionalUserInfo['phone_no'];
            }
            $opticianInfoBlock = str_replace("%optician_name%", $opticianNameBlock, $opticianInfoBlock);
            $html = str_replace("%optician_info%", $opticianInfoBlock, $html);
        }else{
            $html = str_replace("%optician_info%", "", $html);
        }
        
        return $html;
    }
    
    private function insertClientInfo($html){
        $clientInfoBlock = '<div class="table-row">
                                <div class="table-cell">
                                    <p class="table-label">%customer_info_label%:</p>
                                </div>
                                <div class="table-cell">
                                    <p class="table-data">%customer_info%</p>
                                </div>
                            </div>';
        if($this->pdfSettings != null){
            if($this->pdfSettings["showClientName"] == "1" && $this->pdfSettings["showClientID"] == "1"){
                $clientInfoBlockName = str_replace("%customer_info_label%", _("Name"), $clientInfoBlock);
                $clientInfoBlockName = str_replace("%customer_info%", $this->imgInfo["name"], $clientInfoBlockName);
                $clientInfoBlockID = str_replace("%customer_info_label%", _("Client reference"), $clientInfoBlock);
                $clientInfoBlockID = str_replace("%customer_info%", $this->imgInfo["socialsecurity_no"], $clientInfoBlockID);
                $html = str_replace("%client_info%", $clientInfoBlockName . $clientInfoBlockID, $html);
            }else if($this->pdfSettings["showClientName"] == "1"){
                $clientInfoBlockName = str_replace("%customer_info_label%", _("Name"), $clientInfoBlock);
                $clientInfoBlockName = str_replace("%customer_info%", $this->imgInfo["name"], $clientInfoBlockName);
                $html = str_replace("%client_info%", $clientInfoBlockName, $html);
            }else if($this->pdfSettings["showClientID"] == "1"){
                $clientInfoBlockID = str_replace("%customer_info_label%", _("Client reference"), $clientInfoBlock);
                $clientInfoBlockID = str_replace("%customer_info%", $this->imgInfo["socialsecurity_no"], $clientInfoBlockID);
                $html = str_replace("%client_info%", $clientInfoBlockID, $html);
            }else{
                $html = str_replace("%client_info%", "", $html);
            }
        }else{
            $clientInfoBlockID = str_replace("%customer_info_label%", _("Client reference"), $clientInfoBlock);
            $clientInfoBlockID = str_replace("%customer_info%", $this->imgInfo["socialsecurity_no"], $clientInfoBlockID);
            $html = str_replace("%client_info%", $clientInfoBlockID, $html);
        }
        
        return $html;
    }
    
    private function insertDearText($html) {
        $html = str_replace("%dear%", _("Dear customer"), $html);
        return $html;
    }
    
    private function insertResultRelatedData($html) {
        //Interpret the result
        $results = ResultInterpreter::getResultColorsStandard($this->imgInfo, $this->userSettings);
        $drResult = $results['dr'];
        $amdResult = $results['amd'];
        $glaucomaResult = $results['glaucoma'];
        
        $result_text_dr_green = $this->lh->replaceSystemName(_("%system% DR has not found indications of the above mentioned alterations on your retina."));
        $result_text_dr_yellow = $this->lh->replaceSystemName(_("%system% DR has found a few indications of the above mentioned alterations on your retina."));
        $result_text_dr_red = $this->lh->replaceSystemName(_("%system% DR has found a number of indications of the above mentioned alterations on your retina."));
        $result_text_amd_green = $this->lh->replaceSystemName(_("%system% AMD has not found indications of the above mentioned alterations on your retina."));
        $result_text_amd_yellow = $this->lh->replaceSystemName(_("%system% AMD has found indications of drusen on your retina."));
        $result_text_glaucoma_green = $this->lh->replaceSystemName(_("%system% Glaucoma has found that the hemoglobin level is within the normal range."));
        $result_text_glaucoma_yellow = $this->lh->replaceSystemName(_("%system% Glaucoma has found that the hemoglobin level is slightly outside the normal range."));
        $result_text_glaucoma_red = $this->lh->replaceSystemName(_("%system% Glaucoma has found that the hemoglobin level is outside the normal range."));
        
        $recommendation_text_green = _("Based on this analysis, there has not been found any reason for contacting an ophthalmologist earlier than already scheduled.");
        $recommendation_text_dr_yellow = _("On the basis of this analysis, it is recommended to have the analysis repeated in 3 months or to contact an ophthalmologist.");
        $recommendation_text_dr_red = _("On the basis of this analysis, it is recommended to contact an ophthalmologist in the near future (weeks) for further examination.");
        $recommendation_text_amd_yellow_short = _("it is recommended to be aware and notice any of the symptoms of AMD. The symptoms, which you should watch out for are: blurry or blind spots in your central/reading vision, straight lines appearing wavy, or reduced vision. If you experience any of these symptoms, see an ophthalmologist as soon as possible.");
        $recommendation_text_amd_yellow = _("On the basis of this analysis,") . " " . $recommendation_text_amd_yellow_short;
        $recommendation_text_glaucoma_yellow_short = _("it is recommended to contact an ophthalmologist within months for further examination if you have a family history of glaucoma. If you do not have a history of glaucoma you do not need to contact an ophthalmologist, but it is recommended to get the screening again in a year's time or discuss this with your ophthalmologist at your next routine visit.");
        $recommendation_text_glaucoma_yellow = _("On the basis of this analysis,") . " " . $recommendation_text_glaucoma_yellow_short;
        $recommendation_text_glaucoma_red = _("On the basis of this analysis, it is recommended to contact an ophthalmologist within months for further examination.");
        
        //Check if there is an assessment of the image
        if(!empty($this->imgInfo["status"]) && $this->imgInfo["status"] == "1"){
            $html = str_replace("%block_intro%", "", $html);
            $html = str_replace("%performed_analysis_on_image%", $this->lh->replaceSystemName(_("%system% has performed an analysis on a photo of your retina to detect possible changes.")), $html);
            $html = str_replace("%title%", $this->lh->replaceSystemName(_("Result from %system% analysis")), $html);
            
            $html = str_replace("%result_screening%", _("Eye specialist assessment"), $html);
            if(empty($this->imgInfo["answerComment"])){
                if($drResult == "red" || $drResult == "yellow" || $amdResult == "yellow"){
                    die(_("Could not create pdf document, please contact the support."));
                }elseif($drResult == "ungradable" || $amdResult == "ungradable"){
                    die(_("Could not create pdf document, because the photo was ungradable."));
                }else{
                    $comment = _("No indications were discovered during the assessment. Based on this assessment, no reason has been found for scheduling a (new) consultation with an ophthalmologist.");
                    $html = str_replace("%eyespecialist_end_comment%", '<p class="recommendation_text"><span class="bold_text">' .  _("Beside the above stated comments, no other pathological conditions are found nor registered based on the submitted fundus photos. Any already scheduled consultations with a physician and/or an ophthalmologist should therefore be honoured regardless of the above findings.") . '</span></p>', $html);
                }
            }else{
                $comment = $this->imgInfo["answerComment"];
                if(!empty($this->config['site']) && $this->config['site'] == 'optomed'){
                    $html = str_replace("%eyespecialist_end_comment%", '<p class="recommendation_text"><span class="bold_text">' .  _("Any already scheduled consultations with a physician and/or an ophthalmologist should be honoured regardless of the above findings.") . ' ' . _("However, if the recommendations require earlier or urgent attention, these should be carried out before any already scheduled consultations.") . '</span></p>', $html);
                }else{
                    $html = str_replace("%eyespecialist_end_comment%", '<p class="recommendation_text"><span class="bold_text">' .  _("Beside the above stated comments, no other pathological conditions are found nor registered based on the submitted fundus photos. Any already scheduled consultations with a physician and/or an ophthalmologist should therefore be honoured regardless of the above findings.") . ' ' . _("However, if the recommendations require earlier or urgent attention, these should be carried out before any already scheduled consultations.") . '</span></p>', $html);
                }
                
            }
            $html = str_replace("%result_text%", _("An eye specialist has reviewed the photo and gives the following response:") . "<br /> " . $comment, $html);
            $html = str_replace("%recommendation%", "", $html);
            $html = str_replace("%recommendation_text%", "", $html);
            return $html;
        }else{
            $html = str_replace("%eyespecialist_end_comment%", '', $html);
        }
        
        //Check if image is ungradable
        if($glaucomaResult == "ungradable" && $drResult == NULL && $amdResult == NULL){
            die(_("Could not create pdf document, because the photo was ungradable."));
        }
        if(($drResult == "ungradable" || $amdResult == "ungradable")  && $glaucomaResult == NULL){
            die(_("Could not create pdf document, because the photo was ungradable."));
        }
        
        if($drResult != NULL && $amdResult != NULL && $glaucomaResult != NULL && $glaucomaResult != "ungradable"){
            $intro = $this->lh->replaceSystemName(_("%system% DR screens the photo for indications of minor haemorrhages and microaneurysms."));
            $intro .= '<br />' . $this->lh->replaceSystemName(_("%system% AMD screens the photo for indications of drusen (presence of drusen can be an initial stage of AMD)."));
            $intro .= '<br />' . $this->lh->replaceSystemName(_("%system% Glaucoma measures the hemoglobin level in the optic disc. The measurement can be used as an indication of damage to a vital part of the eye (the optic disc)."));
            $html = str_replace("%block_intro%", $intro, $html);
            $html = str_replace("%performed_analysis_on_image%", $this->lh->replaceSystemName(_("%system% has performed an analysis on a photo of your retina to detect possible changes.")), $html);
            $html = str_replace("%title%", $this->lh->replaceSystemName(_("Results from %system% analysis")), $html);
            $html = str_replace("%result_screening%", _("Results of screening"), $html);
            $html = str_replace("%recommendation%", _("Recommendation"), $html);
            if($drResult == "green" && $amdResult == "green" && $glaucomaResult == "green"){
                $html = str_replace("%result_text%", $result_text_dr_green . "<br /> " . 
                                $result_text_amd_green . "<br /> " .
                                $result_text_glaucoma_green , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_green, $html);
            }else if($drResult == "green" && $amdResult == "green" && $glaucomaResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_dr_green . "<br /> " . 
                                $result_text_amd_green . "<br /> " .
                                $result_text_glaucoma_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_yellow, $html);
            }else if($drResult == "green" && $amdResult == "green" && $glaucomaResult == "red"){
                $html = str_replace("%result_text%", $result_text_dr_green . "<br /> " . 
                                $result_text_amd_green . "<br /> " .
                                $result_text_glaucoma_red , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_red, $html);
            }else if($drResult == "green" && $amdResult == "yellow" && $glaucomaResult == "green"){
                $html = str_replace("%result_text%", $result_text_dr_green . "<br /> " . 
                                $result_text_amd_yellow . "<br /> " .
                                $result_text_glaucoma_green , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_amd_yellow, $html);
            }else if($drResult == "green" && $amdResult == "yellow" && $glaucomaResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_dr_green . "<br /> " . 
                                $result_text_amd_yellow . "<br /> " .
                                $result_text_glaucoma_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_yellow . "<br />" . ucfirst($recommendation_text_amd_yellow_short), $html);
            }else if($drResult == "green" && $amdResult == "yellow" && $glaucomaResult == "red"){
                $html = str_replace("%result_text%", $result_text_dr_green . "<br /> " . 
                                $result_text_amd_yellow . "<br /> " .
                                $result_text_glaucoma_red , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_red . "<br />" . ucfirst($recommendation_text_amd_yellow_short), $html);
            }else if($drResult == "yellow" && $amdResult == "green" && $glaucomaResult == "green"){
                $html = str_replace("%result_text%", $result_text_dr_yellow . "<br /> " . 
                                $result_text_amd_green . "<br /> " .
                                $result_text_glaucoma_green  , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_yellow, $html);
            }else if($drResult == "yellow" && $amdResult == "green" && $glaucomaResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_dr_yellow . "<br /> " . 
                                $result_text_amd_green . "<br /> " .
                                $result_text_glaucoma_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_yellow . "<br />" . ucfirst($recommendation_text_glaucoma_yellow_short), $html);
            }else if($drResult == "yellow" && $amdResult == "green" && $glaucomaResult == "red"){
                $html = str_replace("%result_text%", $result_text_dr_yellow . "<br /> " . 
                                $result_text_amd_green . "<br /> " .
                                $result_text_glaucoma_red , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_red, $html);
            }else if($drResult == "yellow" && $amdResult == "yellow" && $glaucomaResult == "green"){
                $html = str_replace("%result_text%", $result_text_dr_yellow . "<br /> " . 
                                $result_text_amd_yellow . "<br /> " .
                                $result_text_glaucoma_green , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_yellow . "<br />" . ucfirst($recommendation_text_amd_yellow_short), $html);
            }else if($drResult == "yellow" && $amdResult == "yellow" && $glaucomaResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_dr_yellow . "<br /> " . 
                                $result_text_amd_yellow . "<br /> " .
                                $result_text_glaucoma_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_yellow . " " . ucfirst($recommendation_text_amd_yellow_short) . " " . ucfirst($recommendation_text_glaucoma_yellow_short), $html);
            }else if($drResult == "yellow" && $amdResult == "yellow" && $glaucomaResult == "red"){
                $html = str_replace("%result_text%", $result_text_dr_yellow . "<br /> " . 
                                $result_text_amd_yellow . "<br /> " .
                                $result_text_glaucoma_red , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_red . "<br />" . ucfirst($recommendation_text_amd_yellow_short), $html);
            }else if($drResult == "red" && $amdResult == "green" && $glaucomaResult == "green"){
                $html = str_replace("%result_text%", $result_text_dr_red . "<br /> " . 
                                $result_text_amd_green . "<br /> " .
                                $result_text_glaucoma_green, $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_red, $html);
            }else if($drResult == "red" && $amdResult == "green" && $glaucomaResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_dr_red . "<br /> " . 
                                $result_text_amd_green . "<br /> " .
                                $result_text_glaucoma_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_red, $html);
            }else if($drResult == "red" && $amdResult == "green" && $glaucomaResult == "red"){
                $html = str_replace("%result_text%", $result_text_dr_red . "<br /> " . 
                                $result_text_amd_green . "<br /> " .
                                $result_text_glaucoma_red , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_red, $html);
            }else if($drResult == "red" && $amdResult == "yellow" && $glaucomaResult == "green"){
                $html = str_replace("%result_text%", $result_text_dr_red . "<br /> " . 
                                $result_text_amd_yellow . "<br /> " .
                                $result_text_glaucoma_green , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_red . "<br />" . ucfirst($recommendation_text_amd_yellow_short), $html);
            }else if($drResult == "red" && $amdResult == "yellow" && $glaucomaResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_dr_red . "<br /> " . 
                                $result_text_amd_yellow . "<br /> " .
                                $result_text_glaucoma_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_red . "<br />" . ucfirst($recommendation_text_amd_yellow_short), $html);
            }else if($drResult == "red" && $amdResult == "yellow" && $glaucomaResult == "red"){
                $html = str_replace("%result_text%", $result_text_dr_red . "<br /> " . 
                                $result_text_amd_yellow . "<br /> " .
                                $result_text_glaucoma_red , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_red . "<br />" . ucfirst($recommendation_text_amd_yellow_short), $html);
            }
        }else if($drResult != NULL && $amdResult != NULL){     
            $intro = $this->lh->replaceSystemName(_("%system% DR screens the photo for indications of minor haemorrhages and microaneurysms."));
            $intro .= '<br />' . $this->lh->replaceSystemName(_("%system% AMD screens the photo for indications of drusen (presence of drusen can be an initial stage of AMD)."));
            $html = str_replace("%block_intro%", $intro, $html);
            $html = str_replace("%performed_analysis_on_image%", $this->lh->replaceSystemName(_("%system% has performed an analysis on a photo of your retina to detect possible changes.")), $html);
            $html = str_replace("%title%", $this->lh->replaceSystemName(_("Results from %system% analysis")), $html);
            $html = str_replace("%result_screening%", _("Results of screening"), $html);
            $html = str_replace("%recommendation%", _("Recommendation"), $html);
            if($drResult == "green" && $amdResult == "green"){
                $html = str_replace("%result_text%", $result_text_dr_green . "<br /> " . $result_text_amd_green , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_green, $html);
            }else if($drResult == "yellow" && $amdResult == "green"){
                $html = str_replace("%result_text%", $result_text_dr_yellow . "<br /> " . $result_text_amd_green , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_yellow, $html);
            }else if($drResult == "red" && $amdResult == "green"){
                $html = str_replace("%result_text%", $result_text_dr_red . "<br /> " . $result_text_amd_green, $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_red, $html);
            }
            if($drResult == "green" && $amdResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_dr_green . "<br /> " . $result_text_amd_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_amd_yellow, $html);
            }else if($drResult == "yellow" && $amdResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_dr_yellow . "<br /> " . $result_text_amd_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_yellow . " " . ucfirst($recommendation_text_amd_yellow_short), $html);
            }else if($drResult == "red" && $amdResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_dr_red . "<br /> " . $result_text_amd_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_red, $html);
            }
        }else if($drResult != NULL && $glaucomaResult != NULL && $glaucomaResult != "ungradable"){     
            $intro = $this->lh->replaceSystemName(_("%system% DR screens the photo for indications of minor haemorrhages and microaneurysms."));
            $intro .= '<br />' . $this->lh->replaceSystemName(_("%system% Glaucoma measures the hemoglobin level in the optic disc. The measurement can be used as an indication of damage to a vital part of the eye (the optic disc)."));
            $html = str_replace("%block_intro%", $intro, $html);
            $html = str_replace("%performed_analysis_on_image%", $this->lh->replaceSystemName(_("%system% has performed an analysis on a photo of your retina to detect possible changes.")), $html);
            $html = str_replace("%title%", $this->lh->replaceSystemName(_("Results from %system% analysis")), $html);
            $html = str_replace("%result_screening%", _("Results of screening"), $html);
            $html = str_replace("%recommendation%", _("Recommendation"), $html);
            if($drResult == "green" && $glaucomaResult == "green"){
                $html = str_replace("%result_text%", $result_text_dr_green . "<br /> " . $result_text_glaucoma_green , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_green, $html);
            }else if($drResult == "yellow" && $glaucomaResult == "green"){
                $html = str_replace("%result_text%", $result_text_dr_yellow . "<br /> " . $result_text_glaucoma_green , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_yellow, $html);
            }else if($drResult == "red" && $glaucomaResult == "green"){
                $html = str_replace("%result_text%", $result_text_dr_red . "<br /> " . $result_text_glaucoma_green, $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_red, $html);
            }
            if($drResult == "green" && $glaucomaResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_dr_green . "<br /> " . $result_text_glaucoma_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_yellow, $html);
            }else if($drResult == "yellow" && $glaucomaResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_dr_yellow . "<br /> " . $result_text_glaucoma_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_yellow . " " . ucfirst($recommendation_text_glaucoma_yellow_short), $html);
            }else if($drResult == "red" && $glaucomaResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_dr_red . "<br /> " . $result_text_glaucoma_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_red, $html);
            }
            if($drResult == "green" && $glaucomaResult == "red"){
                $html = str_replace("%result_text%", $result_text_dr_green . "<br /> " . $result_text_glaucoma_red , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_red, $html);
            }else if($drResult == "yellow" && $glaucomaResult == "red"){
                $html = str_replace("%result_text%", $result_text_dr_yellow . "<br /> " . $result_text_glaucoma_red , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_red, $html);
            }else if($drResult == "red" && $glaucomaResult == "red"){
                $html = str_replace("%result_text%", $result_text_dr_red . "<br /> " . $result_text_glaucoma_red , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_dr_red, $html);
            }
        }else if($amdResult != NULL && $glaucomaResult != NULL && $glaucomaResult != "ungradable"){     
            $intro = $this->lh->replaceSystemName(_("%system% AMD screens the photo for indications of drusen (presence of drusen can be an initial stage of AMD)."));
            $intro .= '<br />' . $this->lh->replaceSystemName(_("%system% Glaucoma measures the hemoglobin level in the optic disc. The measurement can be used as an indication of damage to a vital part of the eye (the optic disc)."));
            $html = str_replace("%block_intro%", $intro, $html);
            $html = str_replace("%performed_analysis_on_image%", $this->lh->replaceSystemName(_("%system% has performed an analysis on a photo of your retina to detect possible changes.")), $html);
            $html = str_replace("%title%", $this->lh->replaceSystemName(_("Results from %system% analysis")), $html);
            $html = str_replace("%result_screening%", _("Results of screening"), $html);
            $html = str_replace("%recommendation%", _("Recommendation"), $html);
            if($amdResult == "green" && $glaucomaResult == "green"){
                $html = str_replace("%result_text%", $result_text_amd_green . "<br /> " . $result_text_glaucoma_green , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_green, $html);
            }else if($amdResult == "green" && $glaucomaResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_amd_green . "<br /> " . $result_text_glaucoma_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_yellow, $html);
            }else if($amdResult == "green" && $glaucomaResult == "red"){
                $html = str_replace("%result_text%", $result_text_amd_green . "<br /> " . $result_text_glaucoma_red, $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_red, $html);
            }
            if($amdResult == "yellow" && $glaucomaResult == "green"){
                $html = str_replace("%result_text%", $result_text_amd_yellow . "<br /> " . $result_text_glaucoma_green , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_amd_yellow, $html);
            }else if($amdResult == "yellow" && $glaucomaResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_amd_yellow . "<br /> " . $result_text_glaucoma_yellow , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_yellow . " " . ucfirst($recommendation_text_amd_yellow_short), $html);
            }else if($amdResult == "yellow" && $glaucomaResult == "red"){
                $html = str_replace("%result_text%", $result_text_amd_yellow . "<br /> " . $result_text_glaucoma_red , $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_red, $html);
            }
        }else if($drResult != NULL){
            if(!empty($this->config['site']) && $this->config['site'] == 'optomed'){
                $intro = $this->lh->replaceSystemName(_("%system% screens the photo for indications of minor haemorrhages and microaneurysms, which could be signs on diabetic retinopathy."));
            }else{
                $intro = $this->lh->replaceSystemName(_("%system% DR screens the photo for indications of minor haemorrhages and microaneurysms."));
            }
            $html = str_replace("%block_intro%", $intro, $html);
            if(!empty($this->config['site']) && $this->config['site'] == 'optomed'){
                $html = str_replace("%performed_analysis_on_image%", $this->lh->replaceSystemName(_("%system% has performed an analysis on a photo of your retina to detect possible changes.")), $html);
            }else{
                $html = str_replace("%performed_analysis_on_image%", $this->lh->replaceSystemName(_("%system% has performed an analysis on a photo of your retina to detect possible changes.")), $html);
            }
            
            $html = str_replace("%title%", $this->lh->replaceSystemName(_("Result from %system% analysis")), $html);

            
            $html = str_replace("%result_screening%", _("Result of screening"), $html);
            if(!empty($this->config['site']) && $this->config['site'] == 'optomed'){
                $html = str_replace("%recommendation%", "", $html);
            }else{
                $html = str_replace("%recommendation%", _("Recommendation"), $html);
            }
            if($drResult == "green"){
                if(!empty($this->config['site']) && $this->config['site'] == 'optomed'){
                    $html = str_replace("%result_text%", $this->lh->replaceSystemName(_("%system% has not found indications of the above mentioned alterations on your retina.")) . "<br /><br />" . $this->lh->replaceSystemName(_("Note that %system% is developed only to diabetic retinopathy screening and it’s not a diagnosis. In addition, it doesn’t exclude any other eye diseases.")), $html);
                    $html = str_replace("%recommendation_text%", "", $html);
                }else{
                    $html = str_replace("%result_text%", $result_text_dr_green , $html);
                    $html = str_replace("%recommendation_text%", $recommendation_text_green, $html);
                }
            }else if($drResult == "yellow"){
                if(!empty($this->config['site']) && $this->config['site'] == 'optomed'){
                    $html = str_replace("%result_text%", $this->lh->replaceSystemName(_("%system% has found a few indications of the above mentioned alterations on your retina.")) . "<br /><br />" . $this->lh->replaceSystemName(_("Note that %system% is developed only to diabetic retinopathy screening and it’s not a diagnosis. In addition, it doesn’t exclude any other eye diseases.")), $html);
                    $html = str_replace("%recommendation_text%", "", $html);
                }else{
                    $html = str_replace("%result_text%", $result_text_dr_yellow , $html);
                    $html = str_replace("%recommendation_text%", $recommendation_text_dr_yellow, $html);
                }
            }else if($drResult == "red"){
                if(!empty($this->config['site']) && $this->config['site'] == 'optomed'){
                    $html = str_replace("%result_text%", $this->lh->replaceSystemName(_("%system% has found a number of indications of the above mentioned alterations on your retina.")) . "<br /><br />" . $this->lh->replaceSystemName(_("Note that %system% is developed only to diabetic retinopathy screening and it’s not a diagnosis. In addition, it doesn’t exclude any other eye diseases.")) , $html);
                    $html = str_replace("%recommendation_text%", "", $html);
                }else{
                    $html = str_replace("%result_text%", $result_text_dr_red , $html);
                    $html = str_replace("%recommendation_text%", $recommendation_text_dr_red, $html);
                }
            }
        }else if($amdResult != NULL){
            $intro = $this->lh->replaceSystemName(_("%system% AMD screens the photo for indications of drusen (presence of drusen can be an initial stage of AMD)."));
            $html = str_replace("%block_intro%", $intro, $html);
            $html = str_replace("%performed_analysis_on_image%", $this->lh->replaceSystemName(_("%system% has performed an analysis on a photo of your retina to detect possible changes.")), $html);
            $html = str_replace("%title%", $this->lh->replaceSystemName(_("Result from %system% analysis")), $html);
            $html = str_replace("%result_screening%", _("Result of screening"), $html);
            $html = str_replace("%recommendation%", _("Recommendation"), $html);
            if($amdResult == "green"){
                $html = str_replace("%result_text%", $result_text_amd_green, $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_green, $html);
            }else if($amdResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_amd_yellow, $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_amd_yellow, $html);
            }
        }else if($glaucomaResult != NULL){
            $intro = $this->lh->replaceSystemName(_("%system% Glaucoma measures the hemoglobin level in the optic disc. The measurement can be used as an indication of damage to a vital part of the eye (the optic disc)."));
            $html = str_replace("%block_intro%", $intro, $html);
            $html = str_replace("%performed_analysis_on_image%", $this->lh->replaceSystemName(_("%system% has performed an analysis on a photo of your retina to detect possible changes.")), $html);
            $html = str_replace("%title%", $this->lh->replaceSystemName(_("Result from %system% analysis")), $html);
            $html = str_replace("%result_screening%", _("Result of screening"), $html);
            $html = str_replace("%recommendation%", _("Recommendation"), $html);
            if($glaucomaResult == "green"){
                $html = str_replace("%result_text%", $result_text_glaucoma_green, $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_green, $html);
            }else if($glaucomaResult == "yellow"){
                $html = str_replace("%result_text%", $result_text_glaucoma_yellow, $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_yellow, $html);
            }else if($glaucomaResult == "red"){
                $html = str_replace("%result_text%", $result_text_glaucoma_red, $html);
                $html = str_replace("%recommendation_text%", $recommendation_text_glaucoma_red, $html);
            }
        }
        return $html;
    }
}
