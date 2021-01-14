<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Referral;

use RetinaLyze\Database\DatabaseHandler;
use RetinaLyze\Internationalization\LanguageHandler;
use RetinaLyze\Utils\Config;
use RetinaLyze\Utils\Mailer;


/**
 * Description of ReferralHandler
 *
 * @author mom
 */
class ReferralHandler {

    private $dbHandler;
    private $config;

    function __construct() {
        $this->dbHandler = new DatabaseHandler();
        $this->config = Config::getConfig();
    }

    function initializeReferral($id, $cpr, $pressure, $comment) {
        $this->dbHandler->initializeRefferal($id, $cpr, $pressure, $comment, null);
    }

    function initializeReferralWithDetails($post) {
        if (!isset($post["id"])) {
            return false;
        } else {
            $id = $post["id"];
        }

        
        if(isset($post["age"]) && strlen($post["age"]) > 3){
            throw new \RuntimeException("Age input is above 3");
        }
        $age = !isset($post["age"]) ? null : $post["age"];
        
        if(isset($post["cpr"]) && strlen($post["cpr"]) > 15){
            throw new \RuntimeException("CPR input is above 15");
        }
        $cpr = !isset($post["cpr"]) ? null : $post["cpr"];
        
        if(isset($post["visus_os"]) && strlen($post["visus_os"]) > 5){
            throw new \RuntimeException("Visus OS input is above 5");
        }
        $visus_os = !isset($post["visus_os"]) ? null : $post["visus_os"];
        
        if(isset($post["last_oph_consult"]) && !is_numeric($post["last_oph_consult"]) && strlen($post["last_oph_consult"]) > 2){
            throw new \RuntimeException("Last ophthalmologist consultation input isn't numeric and above 2 lenght");
        }
        $last_oph_consult = !isset($post["last_oph_consult"]) ? null : $post["last_oph_consult"];
        
        if(isset($post["amsler"]) && !is_numeric($post["amsler"]) && strlen($post["amsler"]) > 2){
            throw new \RuntimeException("Amsler input isn't numeric and above 2 lenght");
        }
        $amsler = !isset($post["amsler"]) ? null : $post["amsler"];
        
        if(isset($post["visus_od"]) && strlen($post["visus_od"]) > 5){
            throw new \RuntimeException("Visus OD input is above 5");
        }
        $visus_od = !isset($post["visus_od"]) ? null : $post["visus_od"];
        
        if(isset($post["next_oph_consult"]) && !is_numeric($post["next_oph_consult"]) && strlen($post["next_oph_consult"]) > 2){
            throw new \RuntimeException("Next ophthalmologist consultation input isn't numeric and above 2 lenght");
        }
        $next_oph_consult = !isset($post["next_oph_consult"]) ? null : $post["next_oph_consult"];
        
        if(isset($post["diagnosed_diabetes"]) && !is_numeric($post["diagnosed_diabetes"]) && strlen($post["diagnosed_diabetes"]) > 1){
            throw new \RuntimeException("Diagnosed diabetes input isn't numeric and above 1 lenght");
        }
        $diagnosed_diabetes = !isset($post["diagnosed_diabetes"]) ? null : $post["diagnosed_diabetes"];
        
        if(isset($post["duration_diabetes"]) && !is_numeric($post["duration_diabetes"]) && strlen($post["diagnosed_diabetes"]) > 2){
            throw new \RuntimeException("Duration of diabetes input isn't numeric and above 2 lenght");
        }
        $duration_diabetes = !isset($post["duration_diabetes"]) ? null : $post["duration_diabetes"];
        
        if(isset($post["tension"]) && strlen($post["tension"]) > 15){
            throw new \RuntimeException("Tension input isn't numeric and above 15 lenght");
        }
        $tension = !isset($post["tension"]) ? null : $post["tension"];
        
        if(isset($post["cct_tension"]) && !is_numeric($post["cct_tension"]) && strlen($post["cct_tension"]) > 1){
            throw new \RuntimeException("CCT tension input isn't numeric and above 1 lenght");
        }
        $cct_tension = !isset($post["cct_tension"]) ? null : $post["cct_tension"];
        
        if(isset($post["glaucoma_family"]) && !is_numeric($post["glaucoma_family"]) && strlen($post["glaucoma_family"]) > 1){
            throw new \RuntimeException("Glaucoma in family input isn't numeric and above 1 lenght");
        }
        $glaucoma_family = !isset($post["glaucoma_family"]) ? null : $post["glaucoma_family"];
        
        if(isset($post["reason"]) && !is_numeric($post["reason"]) && strlen($post["reason"]) > 2){
            throw new \RuntimeException("Reason input isn't numeric and above 2 lenght");
        }
        $reason = !isset($post["reason"]) ? null : $post["reason"];
        
        if(isset($post["visus_before_os"]) && strlen($post["visus_before_os"]) > 5){
            throw new \RuntimeException("Visus before OS input is above 5");
        }
        $visus_before_os = !isset($post["visus_before_os"]) ? null : $post["visus_before_os"];
        
        if(isset($post["which_sympton"]) && !is_numeric($post["which_sympton"]) && strlen($post["which_sympton"]) > 2){
            throw new \RuntimeException("Which sympton input isn't numeric and above 2 lenght");
        }
        $which_sympton = !isset($post["which_sympton"]) ? null : $post["which_sympton"];
        
        if(isset($post["visus_before_od"]) && strlen($post["visus_before_od"]) > 5){
            throw new \RuntimeException("Visus before OD input is above 5");
        }
        $visus_before_od = !isset($post["visus_before_od"]) ? null : $post["visus_before_od"];
        
        if(isset($post["position_noticed"]) && !is_numeric($post["position_noticed"]) && strlen($post["position_noticed"]) > 2){
            throw new \RuntimeException("Position noticed input isn't numeric and above 2 lenght");
        }
        $position_noticed = !isset($post["position_noticed"]) ? null : $post["position_noticed"];
        
        if(isset($post["change_prescription_period"]) && !is_numeric($post["change_prescription_period"]) && strlen($post["change_prescription_period"]) > 2){
            throw new \RuntimeException("Change prescription period input isn't numeric and above 2 lenght");
        }
        $change_prescription_period = !isset($post["change_prescription_period"]) ? null : $post["change_prescription_period"];
        
        if(isset($post["comment"]) && strlen($post["comment"]) > 2000){
            throw new \RuntimeException("Comment input isn't numeric and above 2000 lenght");
        }
        $comment = !isset($post["comment"]) ? null : $post["comment"];
        
        if(isset($post["prescription_before_OS"]) && strlen($post["prescription_before_OS"]) > 25){
            throw new \RuntimeException("Prescription before OS input isn't numeric and above 25 lenght");
        }
        $eyePrescriptionBeforeOS = !isset($post["prescription_before_OS"]) ? null : $post["prescription_before_OS"];
        
        if(isset($post["prescription_before_OD"]) && strlen($post["prescription_before_OD"]) > 25){
            throw new \RuntimeException("Prescription before OD input isn't numeric and above 25 lenght");
        }
        $eyePrescriptionBeforeOD = !isset($post["prescription_before_OD"]) ? null : $post["prescription_before_OD"];
        
        if(isset($post["presciption_after_OS"]) && strlen($post["presciption_after_OS"]) > 25){
            throw new \RuntimeException("Prescription after OS input isn't numeric and above 25 lenght");
        }
        $eyePrescriptionAfterOS = !isset($post["presciption_after_OS"]) ? null : $post["presciption_after_OS"];
        
        if(isset($post["presciption_after_OD"]) && strlen($post["presciption_after_OD"]) > 25){
            throw new \RuntimeException("Prescription after OS input isn't numeric and above 25 lenght");
        }
        $eyePrescriptionAfterOD = !isset($post["presciption_after_OD"]) ? null : $post["presciption_after_OD"];

        $detailID = $this->dbHandler->createDetailsOfRef($age, $visus_os, $last_oph_consult, $amsler, $visus_od, $next_oph_consult, $diagnosed_diabetes, $duration_diabetes, $tension, $cct_tension, $glaucoma_family, $reason, $visus_before_os, $which_sympton, $visus_before_od, $position_noticed, $change_prescription_period, $comment, $eyePrescriptionBeforeOS, $eyePrescriptionBeforeOD, $eyePrescriptionAfterOS, $eyePrescriptionAfterOD);

        $this->dbHandler->initializeRefferal($id, $cpr, null, null, $detailID);
        return true;
    }

    function responseToReferral($post_array, $eyespecialistID, $eyespecialistLang) {
        //Extract info from post req
        $refID = $post_array["refID"];
        $answerTypeDR = !isset($post_array["opthal_response_dr"]) || $post_array["opthal_response_dr"] == "3" ? null : $post_array["opthal_response_dr"];
        $answerTypeAMD = !isset($post_array["opthal_response_amd"]) || $post_array["opthal_response_amd"] == "2" ? null : $post_array["opthal_response_amd"];
        $answerTypeGlaucoma = !isset($post_array["opthal_response_glaucoma"]) || $post_array["opthal_response_glaucoma"] == "3" ? null : $post_array["opthal_response_glaucoma"];
        $comment = $post_array["comment"];

        //Make sure there is a if DR answer is red or yellow or AMD answer is yellow.
        if (($answerTypeDR == "1" || $answerTypeDR == "2" || $answerTypeAMD == "1" || $answerTypeGlaucoma == "1" || $answerTypeGlaucoma == "2") && empty($comment)) {
            throw new \Exception("No comment on not green answer");
        }
        //Get user and image data from refID
        try{
            $userAndImageDetailsDB = $this->dbHandler->getUserAndImageDetailsFromRefID($refID);
        } catch (\Exception $ex) {
            error_log('Error: Could not get user and image details from ref id.');
            error_log($ex->getTraceAsString());
            return false;
        }
        
        $userAndImageDetails = $userAndImageDetailsDB->fetch_assoc();
        $emailAddress = $userAndImageDetails["email"];
        $wantEmail = $userAndImageDetails["refferalMail"];
        $imageID = $userAndImageDetails["id"];
        $userID = $userAndImageDetails["userID"];

        //Get additional user info (to get language)
        $additionalUserInfoDB = $this->dbHandler->getUserAdditionalInfoFromID($userID);
        $additionalUserInfo = $additionalUserInfoDB->fetch_assoc();
        $language = $additionalUserInfo["language"] == NULL ? "en" : $additionalUserInfo["language"];

        //Set the language
        new LanguageHandler($language);

        //Update response in DB
        $this->dbHandler->responseToReferral($refID, $answerTypeDR, $answerTypeAMD, $comment, $eyespecialistID, $answerTypeGlaucoma);

        //Send notice on mail.
        if ($wantEmail) {
            //Link to response:
            $url = $this->config['base_url'] . "index.php?page=detailed&id=" . $imageID;

            // Send email 
            $mail = new Mailer();
            $result = $mail->send($emailAddress, _('New comment received regarding photo'), "<html><h2>" . _('New comment received regarding photo') . "</h2><p>" . _('Our specialist has commented on your photo. See the comment and photo here:') . "<br> <a href='" . $url . "'>" . $url . "</a></p></html>");
        }
        
        //Set the language back to the original language
        new LanguageHandler($eyespecialistLang);
    }

    function updateResponse($post_array) {
        $refID = $post_array["refID"];
        $answerIDs = $post_array["answerID"];
        if(empty($post_array["answerID"])){
            error_log("AnswerID is empty. Post: " . print_r($post_array, true));
        }
        $optionIDs = !empty($post_array["optionID"]) ? $post_array["optionID"] : null;
        $positionID = !empty($post_array["positionID"]) ? $post_array["positionID"] : null;
        $notificationID = !empty($post_array["notificationID"]) ? $post_array["notificationID"] : null;
        $recommendationID = !empty($post_array["recommendationID"]) ? $post_array["recommendationID"] : null;

        //Set answers
        foreach ($answerIDs as $answerID) {
            $this->dbHandler->setAnswerOnRefferal($answerID, $refID);
        }
        //Set options
        if ($optionIDs != null) {
            foreach ($optionIDs as $optionID) {
                $this->dbHandler->setOptionOnRefferal($optionID, $refID);
            }
        }

        if ($positionID != null) {
            $this->dbHandler->updatePosition($positionID, $refID);
        }
        if ($notificationID != null) {
            $this->dbHandler->updateNotification($notificationID, $refID);
        }
        if ($recommendationID != null) {
            $this->dbHandler->updateRecommendation($recommendationID, $refID);
        }
    }

}
