<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Views;

use RetinaLyze\Analysis\ResultInterpreter;
use RetinaLyze\l10n\DateTimeLocalized;
use RetinaLyze\Utils\Config;
use RetinaLyze\Database\DatabaseHandler;
use HtmlSanitizer\Sanitizer;

/**
 * Description of newPHPClass
 *
 * @author mom
 */
class ImageListCreator {

    private $showAMD;
    private $showRef;
    private $userID;
    private $lbRegion;
    private $dbHandler;
    private $tz;
    private $locale;
    private $userInfo;
    private $userSettings;
    private $config;
    private $showUser;
    private $showCustomerName;
    private $analysisAllowed;
    private $alwaysHideRef;
    private $notOwnData;
    private $showEyeSpecialistComment;
    private $hideThumbnails;
    private $hideEmail;
    private $showDR;
    private $showGlaucoma;
    private $showDelete;
    private $hideClientID;
    private $linkPage;
    private $sanitizer;

    function __construct($showRef, $userID, $showUser = false, $showCustomerName = false, $analysisAllowed = true, $alwaysHideRef = false, $notOwnData = false, $showEyeSpecialistComment = false, $hideThumbnails = false, $hideEmail = false, $showDelete = false, $dbh = NULL, $showAllTypesOfAnalysisAlways = false, $hideClientID = false, $linkPage = 'detailed') {
        //Save userID
        $this->userID = $userID;
        
        $this->sanitizer = Sanitizer::create(['extensions' => []]);
        
        //Connect to DB and get user data
        if($dbh == NULL){
            $this->dbHandler = new DatabaseHandler();
        }else{
            $this->dbHandler = $dbh;
        }
        $additionalUserInfoDB = $this->dbHandler->getUserInfoWithAdditionalInfoFromID($this->userID);
        $this->userInfo = $additionalUserInfoDB->fetch_assoc();
        $this->userSettings = $this->dbHandler->getUserSettingsFromID($this->userID)->fetch_assoc();
        
        //Store data which indicate how the list should be created.
        $this->showAMD = $this->userSettings["AMDenabled"] == "1" ? true : false;
        
        $this->lbRegion = $this->userInfo["lbRegion"];
        $this->locale = empty($this->userInfo["language"]) ? null : $this->userInfo["language"];
        $this->tz = empty($this->userInfo["timezone"]) ? false : $this->userInfo["timezone"];

        $this->showRef = $showRef;
        $this->showUser = $showUser;
        $this->showCustomerName = $showCustomerName;
        $this->analysisAllowed = $analysisAllowed;
        $this->alwaysHideRef = $alwaysHideRef;
        $this->notOwnData = $notOwnData;
        $this->hideThumbnails = $hideThumbnails;
        $this->hideEmail = $hideEmail;
        $this->showDR = $this->userSettings["DRenabled"] == "1" ? true : false;
        $this->showGlaucoma = $this->userSettings["GlaucomaEnabled"] == "1" ? true : false;
        $this->showDelete = $showDelete;
        $this->showEyeSpecialistComment = $showEyeSpecialistComment;
        $this->hideClientID = $hideClientID;
        
        $this->linkPage = $linkPage;
        
        if($this->userSettings['onlyESB']){
            $this->showDR = false;
            $this->showAMD = false;
            $this->showGlaucoma = false;
        }
        if($showAllTypesOfAnalysisAlways){
            $this->showDR = true;
            $this->showAMD = true;
            $this->showGlaucoma = true;
        }

        $this->config = Config::getConfig();
    }

    public function createHTML($imagesData) {
        $html = '<table class="data-table">';
        $html .= $this->headerHTML();
        $html .= '<tbody>';
        while ($imgData = $imagesData->fetch_assoc()) {
            $html .= $this->bodyRowHTML($imgData);
        }
        $html .= '</tbody>';
        $html .= '</table>';
        
        return $html;
    }

    private function headerHTML() {
        $html = '<thead>';
        $html .= '<tr>';
        $html .= '<th class="td-date"><strong>' . _('Date of screening') . '</strong></th>';
        if ($this->showUser) {
            $html .= '<th class="td-result"><strong>' . _('User') . '</strong></th>';
        }
        if (!$this->hideThumbnails) {
            $html .= '<th class="td-img"><strong>' . _('Photo') . '</strong></th>';
        }
        if(!$this->hideClientID){
            $html .= '<th class="td-id"><strong>' . _('Client reference') . '</strong></th>';
        }
        if ($this->showCustomerName) {
            $html .= '<th class="td-result"><strong>' . _('Name') . '</strong></th>';
        }
        if($this->showEyeSpecialistComment){
            $html .= '<th class="td-result"><strong>' . _('Comment from eye-specialist') . '</strong></th>';
        }
        if((empty($this->config['site']) || $this->config['site'] == 'retinalyze') && !$this->hideEmail){
            $html .= '<th class="td-email"><strong>' . _('Email') . '</strong></th>';
        }        
        if ($this->showDR) {
            $html .= '<th class="td-result"><strong>' . _('DR status') . '</strong></th>';
        }

        if ($this->showAMD) {
            $html .= '<th class="td-result"><strong>' . _('AMD status') . '</strong></th>';
        }
        if ($this->showGlaucoma) {
            $html .= '<th class="td-result"><strong>' . _('Glaucoma status') . '</strong></th>';
        }
        if (($this->showRef || !empty($this->lbRegion)) && $this->alwaysHideRef == false) {
            $html .= '<th class="td-referral"><strong>' . _('Assessment') . '</strong></th>';
        }
        if($this->showDelete){
            $html .= '<th class="td-referral"><strong>' . _('Delete') . '</strong></th>';
        }
        $html .= '</tr>';
        $html .= '</thead>';
        return $html;
    }

    private function bodyRowHTML($imgData) {
        $html = '<tr>';
        $html .= '<td class="td-date">';
        $html .= $this->getTime($imgData);
        $html .= '</td>';
        
        if ($this->showUser) {
            $html .= '<td class="td-date">';
            $html .= $this->sanitizer->sanitize($imgData['username']);
            $html .= '</td>';
        }
        if (!$this->hideThumbnails) {
            $html .= '<td class="td-img">';
            $html .= $this->thumbnailHTML($imgData);
            $html .= '</td>';
        }
        
        if($imgData["customerID"] === NULL){
            $html .= $this->getSaveClientDataHTML($imgData);
        }else{
            if(!$this->hideClientID){
                $html .= '<td class="td-id">';
                $html .= '<a href="index.php?page=' . $this->linkPage . '&id=' . $imgData['id'] . '">' . $this->sanitizer->sanitize($imgData['socialsecurity_no']) . '</a>';
                $html .= '</td>';
            }
        
            if ($this->showCustomerName) {
                $html .= '<td class="td-id">';
                $html .= '<a href="index.php?page=' . $this->linkPage . '&id=' . $imgData['id'] . '">' . $this->sanitizer->sanitize($imgData['name']) . '</a>';
                $html .= '</td>';
            }
        }
        
        if ($this->showEyeSpecialistComment) {
           $html .= '<td class="td-id">';
           $html .= $this->sanitizer->sanitize($imgData['answerComment']);
           $html .= '</td>';
        }
        
        if((empty($this->config['site']) || $this->config['site'] == 'retinalyze') && !$this->hideEmail){
            $html .= '<td  class="td-email"><a class="btn btn-success" href="index.php?page=mailpid&id=' . $imgData['id'] . '" title="' . _('Send an email with the results to your own email') . '"><i class="fas fa-envelope"></i></a></td>';
        }
        if($imgData["customerID"] !== NULL){
            if ($this->showDR) {
                $html .= '<td class="td-result">';        
                $html .= $this->getDRResultHTML($imgData);
                $html .= '</td>';
            }

            if ($this->showAMD) {
                $html .= '<td class="td-result">';
                $html .= $this->getAMDResultHTML($imgData);
                $html .= '</td>';
            }
            
            if ($this->showGlaucoma) {
                $html .= '<td class="td-result">';
                $html .= $this->getGlaucomaResultHTML($imgData);
                $html .= '</td>';
            }
        
            if (($this->showRef || !empty($this->lbRegion)) && $this->alwaysHideRef == false) {
                $html .= '<td class="td-referral">';
                $html .= $this->getRefHTML($imgData);
                $html .= '</td>';
            }
        }
        if($this->showDelete){
            $html .= '<td class="td-delete"><a class="btn btn-success" href="index.php?page=start&delete=' . $imgData['id'] . '&token=' . $_SESSION['CSRF_GET_TOKEN'] . '" title="' . _('Delete') . '"><i class="fas fa-trash-alt"></i></a></td>';
        }
        $html .= '</tr>';
        return $html;
    }
    
    private function getSaveClientDataHTML($imgData){
        ob_start();
        $colspan = 2 + $this->showAMD + $this->showGlaucoma + $this->showCustomerName + (($this->showRef || $this->lbRegion !== false) && $this->alwaysHideRef == false);
        ?>
                        <td class="td-result" colspan="<?php echo $colspan; ?>"><div style="position: relative"><a class="action fancybox btn btn-success btn_update_info" href="#updateInfo_<?php echo $imgData['id'] ?>"><?php echo _('Add client info'); ?></a></div>
                            <div style="display:none">
                                <div id="updateInfo_<?php echo $imgData["id"]; ?>" class="band-default">
                                    <form id="updateInfo_<?php echo $imgData["id"]; ?>" class="inputfields_with_space"  method="post" action="">
                                        <input name="imgid" type="hidden" value="<?php echo $imgData['id']; ?>"/>
                                        <input name="type" type="hidden" value="updateInfo"/>
                                        <ul>
                                            <li>
                                                <h3><?php echo _("Update client info") ?></h3>
                                                <p><?php echo _("Input the client data for the image") ?></p>
                                                <label class="description" for="age"><?php echo _("Client reference") ?>*</label>
                                                <input id="id_<?php echo $imgData["id"]; ?>" name="id" class="element text medium" type="text" value=""/> 
                                                <?php
                                                if($this->showCustomerName){
                                                ?>
                                                <label class="description" for="age"><?php echo _("Name") ?>*</label>
                                                <input id="name_<?php echo $imgData["id"]; ?>" name="name" class="element text medium" type="text" value=""/> 
                                                <?php
                                                }
                                                ?>
                                            </li>
                                            <li class="buttons">
                                                <input id="saveUpdateForm_<?php echo $imgData["id"]; ?>" class="btn btn-success" type="submit" name="submit" value="<?php echo _("Submit") ?>" />
                                            </li>
                                        </ul>
                                    </form>
                                </div>
                            </div>
                        </td>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    private function getTime($imgData) {
        $timeDR = strtotime($imgData['timeAnalyzedDR']);
        $timeAMD = strtotime($imgData['timeAnalyzedAMD']);
        $timeGlaucoma = strtotime($imgData['timeGlaucomaStarted']);
        if(empty($timeDR) && empty($timeAMD) && empty($timeGlaucoma)){
            $time = DateTimeLocalized::getShortDateTime($imgData['timeUploaded'], $this->locale, $this->tz);
        } else {
            $heighestTime = max($timeDR, $timeAMD, $timeGlaucoma);
            $time = DateTimeLocalized::getShortDateTime($heighestTime, $this->locale, $this->tz, true);
        }
        return $time;
    }

    private function thumbnailHTML($imgData) {
        $html = '<a href="index.php?page=' . $this->linkPage . '&id=' . $imgData['id'] . '">';
        $username = empty($imgData['username']) ? "" : $imgData['username'];
        $userID = empty($imgData['userID']) ? "" : $imgData['userID'];
        $s3usingUserID = !isset($imgData['s3usingUserID']) ? "" : $imgData['s3usingUserID'];
        $html .= '<img class="lazy" data-src="images/thumbnail.php?type=not&username=' . urlencode($username) . '&userID=' . $userID . '&useNewImagePath=' . $s3usingUserID . '&fileName=' . urlencode($imgData['id']) . '.jpg" style="position:relative; top:0;left:0; width:50px" />';
        $html .= '</a>';
        return $html;
    }

    private function getDRResultHTML($imgData) {
        if($this->notOwnData){
            $results = ResultInterpreter::getResultColorsStandard($imgData, null);
        }else{
            $results = ResultInterpreter::getResultColorsStandard($imgData, $this->userSettings);
        }
        $resultDR = $results['dr'];
        if ($resultDR === NULL) {
            //If the photo as been sent to ESB it should no longer be possible to run analysis.
            if (($imgData['status'] !== NULL && $imgData["status"] != 0) || !$this->analysisAllowed) {
                return "<p class='notice gray'>" . _('Analysis not runned') . "</p>";
            }else{
                return "<div style='position: relative'><a class='Run_DR_Analysis btn_run_analysis' href='index.php?page=start&analyze=1&type=0&id=" . $imgData['id'] . "'>" . _('Run DR analysis') . "</a></div>";
            }
        } else if ($resultDR == 'ungradable') {
            return "<p class='notice gray'>" . _('Ungradable') . "</p>";
        } elseif ($resultDR == 'red') {
            return "<p class='notice error'>" . _('Several alterations found') . "</p>";
        } else if ($resultDR == 'yellow') {
            return "<p class='notice warning'>" . _('Few alterations found') . "</p>";
        } else {
            return "<p class='notice success'>" . _('No immediate alterations') . "</p>";
        }
    }

    private function getAMDResultHTML($imgData) {
        $results = ResultInterpreter::getResultColorsStandard($imgData, $this->userSettings);
        $resultAMD = $results['amd'];
        if ($resultAMD === NULL) {
            //If the photo as been sent to ESB and answered, it should no longer be possible to run analysis.
            if (($imgData['status'] !== NULL && $imgData["status"] != 0) || !$this->analysisAllowed) {
                return "<p class='notice gray'>" . _('Analysis not runned') . "</p>";
            }else{
                return "<div style='position: relative'><a class='Run_AMD_Analysis btn_run_analysis' href='index.php?page=start&analyze=1&type=1&id=" . $imgData['id'] . "'>" . _('Run AMD analysis') . "</a></div>";
            }
        } else if ($resultAMD == 'ungradable') {
            return "<p class='notice gray'>" . _('Ungradable') . "</p>";
        } else if ($resultAMD == 'yellow') {
            return "<p class='notice amd'>" . _('Few alterations found') . "</p>";
        } else {
            return "<p class='notice success'>" . _('No immediate alterations') . "</p>";
        }
    }
    
    private function getGlaucomaResultHTML($imgData) {
        $results = ResultInterpreter::getResultColorsStandard($imgData, $this->userSettings, true);
        $resultGlaucoma = $results['glaucoma'];
        if ($resultGlaucoma === NULL) {
            //If the photo as been sent to ESB and answered, it should no longer be possible to run analysis.
            if (($imgData['status'] !== NULL && $imgData["status"] != 0) || !$this->analysisAllowed) {
                return "<p class='notice gray'>" . _('Analysis not runned') . "</p>";
            }else{
                return "<div style='position: relative'><a class='Run_Glaucoma_Analysis btn_run_analysis' href='index.php?page=glaucoma&id=" . $imgData['id'] . "'>" . _('Run Glaucoma analysis') . "</a></div>";
            }
        } else if ($resultGlaucoma == 'ungradable') {
            if($results['glaucomaUngrableReason'] == 'no onh'){
                /* ONH stands for Optic Nerve Head.  */
                return "<p class='notice gray'>" . _('No ONH') . "</p>";
            }else if($results['glaucomaUngrableReason'] == 'saturated'){
                return "<p class='notice gray'>" . _('Saturated') . "</p>";
            }else{
                return "<p class='notice gray'>" . _('Ungradable') . "</p>";
            }
        } elseif ($resultGlaucoma == 'red') {
            return "<p class='notice error'>" . _('Several alterations found') . "</p>";
        } else if ($resultGlaucoma == 'yellow') {
            return "<p class='notice amd'>" . _('Few alterations found') . "</p>";
        } else {
            return "<p class='notice success'>" . _('No immediate alterations') . "</p>";
        }
    }

    private function getRefHTML($imgData) {
        if ($imgData['status'] !== NULL) {
            if ($imgData["status"] == 0) {
                return '<i class="fas fa-user-md sent-ref"></i>';
            } else {
                if(!empty($imgData["answerComment"])){
                    return '<a href="index.php?page=' . $this->linkPage . '&id=' . $imgData['id'] . '"><i class="fas fa-user-md received-ref"></i> <i class="fas fa-exclamation"></i></a>';
                }else{
                    return '<a href="index.php?page=' . $this->linkPage . '&id=' . $imgData['id'] . '"><i class="fas fa-user-md received-ref"></i></a>';
                }
            }
        } else {
            return $this->getLargeRefHTML($imgData);
        }
    }

    private function getLargeRefHTML($imgData) {
        ob_start();
        ?>
        <a class="action fancybox btn btn-success" href="#sendtodoc_<?php echo $imgData["id"]; ?>" title="<?php echo _('Get assessment by eye specialist'); ?>"><i class="fas fa-user-md"></i></a>
        <div style="display:none">
            <div id="sendtodoc_<?php echo $imgData["id"]; ?>" class="band-default">
                <h1><?php echo _("Send to eye specialist") ?></h1>
                <form id="send_to_doc_form_<?php echo $imgData["id"]; ?>" class="appnitro"  method="post" action="">
                    <input name="id" type="hidden" value="<?php echo $imgData['id']; ?>"/>
                    <input name="type" type="hidden" value="withdetails"/>
                    <ul>
                        <div class="grid-row linearise">
                            <div class="grid-item ten-tenth">
                                <h3><?php echo _("General information") ?></h3>
                            </div>
                            <div class="grid-item five-tenth">
                                <?php
                                if (!empty($this->userInfo["country"]) && $this->userInfo["country"] == "DK") {
                                    ?>
                                    <li>
                                        <label class="description" for="cpr"><?php echo _('Social security number (DDMMÅÅ-XXXX)') ?>*</label>
                                        <input id="cpr_age_<?php echo $imgData["id"]; ?>" name="cpr" class="element text medium" type="text" maxlength="15" value=""/> 
                                    </li>
                                    <?php
                                } else {
                                    ?>
                                    <li>
                                        <label class="description" for="age"><?php echo _("Client's age") ?>*</label>
                                        <input id="cpr_age_<?php echo $imgData["id"]; ?>" name="age" class="element text medium" type="number" max="200" value=""/> 
                                    </li>
                                    <?php
                                }
                                ?>
                                <li>
                                    <label class="description" for="visus_od"><?php echo _("Current visus OD") ?></label>
                                    <div>
                                        <select class="element select medium" id="visus_od_<?php echo $imgData["id"]; ?>" name="visus_od"> 
                                            <option value="" selected="selected"><?php echo _("N/A") ?></option>
                                            <option value="2.5">2.5</option>
                                            <option value="2.0">2.0</option>
                                            <option value="1.6">1.6</option>
                                            <option value="1.25">1.25</option>
                                            <option value="1.0">1.0</option>
                                            <option value="0.9">0.9</option>
                                            <option value="0.8">0.8</option>
                                            <option value="0.7">0.7</option>
                                            <option value="0.6">0.6</option>
                                            <option value="0.5">0.5</option>
                                            <option value="0.4">0.4</option>
                                            <option value="0.3">0.3</option>
                                            <option value="0.2">0.2</option>
                                            <option value="0.1">0.1</option>
                                            <option value="0.0">0.0</option>
                                        </select>
                                    </div> 
                                </li>
                                <li>
                                    <label class="description" for="last_oph_consult"><?php echo _("Last ophthalmologist consultation") ?></label>
                                    <div>
                                        <select class="element select medium" id="last_oph_consult_<?php echo $imgData["id"]; ?>" name="last_oph_consult"> 
                                            <option value="" selected="selected"><?php echo _("N/A") ?></option>
                                            <option value="1" ><6 <?php echo _("months") ?></option>
                                            <option value="2" >0.5-5 <?php echo _("years") ?></option>
                                            <option value="3" >>5 <?php echo _("years") ?></option>
                                            <option value="4" ><?php echo _("Never") ?></option>
                                        </select>
                                    </div> 
                                </li>
                            </div>
                            <div class="grid-item five-tenth">
                                <li>
                                    <label class="description" for="amsler"><?php echo _("Amsler test") ?></label>
                                    <div>
                                        <select class="element select medium" id="amsler_<?php echo $imgData["id"]; ?>" name="amsler"> 
                                            <option value="" selected="selected"><?php echo _("N/A") ?></option>
                                            <option value="1" ><?php echo _("Show signs of visual defects") ?></option>
                                            <option value="2" ><?php echo _("No signs of visual defects") ?></option>
                                        </select>
                                    </div> 
                                </li>
                                <li>
                                    <label class="description" for="visus_os"><?php echo _("Current visus OS") ?></label>
                                    <div>
                                        <select class="element select medium" id="visus_os" name="visus_os"> 
                                            <option value="" selected="selected"><?php echo _("N/A") ?></option>
                                            <option value="2.5">2.5</option>
                                            <option value="2.0">2.0</option>
                                            <option value="1.6">1.6</option>
                                            <option value="1.25">1.25</option>
                                            <option value="1.0">1.0</option>
                                            <option value="0.9">0.9</option>
                                            <option value="0.8">0.8</option>
                                            <option value="0.7">0.7</option>
                                            <option value="0.6">0.6</option>
                                            <option value="0.5">0.5</option>
                                            <option value="0.4">0.4</option>
                                            <option value="0.3">0.3</option>
                                            <option value="0.2">0.2</option>
                                            <option value="0.1">0.1</option>
                                            <option value="0.0">0.0</option>
                                        </select>
                                    </div> 
                                </li>
                                <li>                                                           
                                    <label class="description" for="next_oph_consult"><?php echo _("Next ophthalmologist consultation") ?></label>
                                    <div>
                                        <select class="element select medium" id="next_oph_consult_<?php echo $imgData["id"]; ?>" name="next_oph_consult"> 
                                            <option value="" selected="selected"><?php echo _("N/A") ?></option>
                                            <option value="1" ><1 <?php echo _("month") ?></option>
                                            <option value="2" >1-6 <?php echo _("months") ?></option>
                                            <option value="3" >0.5-1 <?php echo _("year") ?></option>
                                            <option value="4" >>1 <?php echo _("year") ?></option>
                                            <option value="5" ><?php echo _("No consultation scheduled") ?></option>
                                        </select>
                                    </div> 
                                </li>		
                            </div>
                        </div>
                        <div class="gap" style="height:10px;"></div>
                        <div class="grid-row linearise">
                            <div class="grid-item ten-tenth">
                                <h3><?php echo _("Information relating to Diabetic Retinopathy") ?></h3>
                            </div>
                            <div class="grid-item five-tenth">
                                <li>
                                    <p><input id="diagnosed_diabetes_<?php echo $imgData["id"]; ?>" name="diagnosed_diabetes" class="element checkbox" type="checkbox" value="1" /> <?php echo _("Client is diagnosed with diabetes") ?></p>
                                </li>
                            </div>
                            <div class="grid-item five-tenth durationdiabetes" style="display: none">
                                <li>
                                    <label class="description" for="duration_diabetes"><?php echo _("Duration of diabetes") ?></label>
                                    <div>
                                        <select class="element select medium" id="duration_diabetes_<?php echo $imgData["id"]; ?>" name="duration_diabetes"> 
                                            <option value="" selected="selected"></option>
                                            <option value="1" ><1 <?php echo _("year") ?></option>
                                            <option value="2" >1-5 <?php echo _("years") ?></option>
                                            <option value="3" >5-10 <?php echo _("years") ?></option>
                                            <option value="4" >>10 <?php echo _("years") ?></option>

                                        </select>
                                    </div> 
                                </li>
                            </div>
                        </div>
                        <div class="gap" style="height:10px;"></div>
                        <div class="grid-row linearise">	
                            <div class="grid-item ten-tenth">
                                <h3><?php echo _("Information relating to glaucoma") ?></h3>
                            </div>
                            <div class="grid-item five-tenth">
                                <li>
                                    <label class="description" for="tension"><?php echo _("Intraocular pressure") ?> (mmHg)</label>
                                    <div>
                                        <input id="tension_<?php echo $imgData["id"]; ?>" name="tension" class="element text medium" type="text" maxlength="15" value=""/> 
                                    </div> 
                                </li>
                            </div>
                            <div class="grid-item five-tenth">
                                <li>
                                    <p>	
                                        <input id="cct_tension_<?php echo $imgData["id"]; ?>" name="cct_tension" class="element checkbox" type="checkbox" value="1" /> <?php echo _("Intraocular pressure corrected for CCT") ?>?
                                    </p>
                                    <p>	
                                        <input id="glaucoma_family_<?php echo $imgData["id"]; ?>" name="glaucoma_family" class="element checkbox" type="checkbox" value="1" /> <?php echo _("Family history of glaucoma") ?>?
                                    </p>
                                </li>	
                            </div>
                        </div>
                        <div class="grid-row linearise">	
                            <div class="grid-item ten-tenth">
                                <h3><?php echo _("Reason for sending photo") ?></h3>
                            </div>
                            <div class="grid-item five-tenth">
                                <li>
                                    <label class="description" for="reason"><?php echo _("Why do you want to get this photo assessed?") ?>
                                    <?php
                                    if(!empty($this->config['site']) && $this->config['site'] == "optomed"){
                                        echo '*';
                                    }
                                    ?>
                                    </label>
                                    <div>
                                        <select class="element select medium" id="reason_<?php echo $imgData["id"]; ?>" name="reason"> 
                                            <option value="" selected="selected"></option>
                                            <option value="1" ><?php echo _("Symptoms of client") ?></option>
                                            <option value="2" ><?php echo _("I have noticed something on the photo") ?></option>
                                            <option value="3" ><?php echo _("Result of algorithm") ?></option>
                                            <option value="4" ><?php echo _("Other") ?></option>
                                        </select>
                                    </div> 
                                </li>
                                <li class="change_presciption" style="display: none">
                                    <div>
                                        <label class="description" for="presciption_after"><?php echo _("Eye prescription before OD") ?></label>
                                        <div>
                                            <input id="prescription_before_OD_<?php echo $imgData["id"]; ?>" name="prescription_before_OD" class="element text medium" type="text" maxlength="25" value=""/> 
                                        </div> 
                                    </div>
                                </li>
                                <li class="change_presciption" style="display: none">
                                    <div>
                                        <label class="description" for="presciption_after"><?php echo _("Eye prescription after OD") ?></label>
                                        <div>
                                            <input id="presciption_after_OD_<?php echo $imgData["id"]; ?>" name="presciption_after_OD" class="element text medium" type="text" maxlength="25" value=""/> 
                                        </div> 
                                    </div>
                                </li>
                                <li class="loss_vision" style="display: none">                                                            
                                    <label class="description" for="visus_before_od"><?php echo _("Visus before OD") ?></label>
                                    <div>
                                        <select class="element select medium" id="visus_before_od_<?php echo $imgData["id"]; ?>" name="visus_before_od"> 
                                            <option value="" selected="selected"><?php echo _("N/A") ?></option>
                                            <option value="2.5">2.5</option>
                                            <option value="2.0">2.0</option>
                                            <option value="1.6">1.6</option>
                                            <option value="1.25">1.25</option>
                                            <option value="1.0">1.0</option>
                                            <option value="0.9">0.9</option>
                                            <option value="0.8">0.8</option>
                                            <option value="0.7">0.7</option>
                                            <option value="0.6">0.6</option>
                                            <option value="0.5">0.5</option>
                                            <option value="0.4">0.4</option>
                                            <option value="0.3">0.3</option>
                                            <option value="0.2">0.2</option>
                                            <option value="0.1">0.1</option>
                                            <option value="0.0">0.0</option>
                                        </select>
                                    </div>
                                </li>
                            </div>
                            <div class="grid-item five-tenth">
                                <li class="symptoms" style="display: none">
                                    <div>
                                        <label class="description" for="which_sympton"><?php echo _("Which symptom?") ?></label>
                                        <div>
                                            <select class="element select medium" id="which_sympton_<?php echo $imgData["id"]; ?>" name="which_sympton"> 
                                                <option value="" selected="selected"></option>
                                                <option value="1" ><?php echo _("Loss of vision") ?></option>
                                                <option value="2" ><?php echo _("Change in eyeglass prescription") ?></option>
                                                <option value="3" ><?php echo _("Other") ?></option>
                                            </select>
                                        </div> 
                                    </div>
                                </li>
                                <li class="change_presciption" style="display: none">
                                    <div class="symptoms">
                                        <label class="description" for="prescription_before_OS"><?php echo _("Eye prescription before OS") ?></label>
                                        <div>
                                            <input id="prescription_before_OS_<?php echo $imgData["id"]; ?>" name="prescription_before_OS" class="element text medium" type="text" maxlength="25" value=""/> 
                                        </div> 
                                    </div>

                                </li>
                                <li class="change_presciption" style="display: none">
                                    <div class="symptoms">
                                        <label class="description" for="presciption_after_OS"><?php echo _("Eye prescription after OS") ?></label>
                                        <div>
                                            <input id="presciption_after_OS_<?php echo $imgData["id"]; ?>" name="presciption_after_OS" class="element text medium" type="text" maxlength="25" value=""/> 
                                        </div> 
                                    </div>

                                </li>
                                <li class="loss_vision" style="display: none">                                                            
                                    <label class="description" for="visus_before_os"><?php echo _("Visus before OS") ?></label>
                                    <div>
                                        <select class="element select medium" id="visus_before_os_<?php echo $imgData["id"]; ?>" name="visus_before_os"> 
                                            <option value="" selected="selected"><?php echo _("N/A") ?></option>
                                            <option value="2.5">2.5</option>
                                            <option value="2.0">2.0</option>
                                            <option value="1.6">1.6</option>
                                            <option value="1.25">1.25</option>
                                            <option value="1.0">1.0</option>
                                            <option value="0.9">0.9</option>
                                            <option value="0.8">0.8</option>
                                            <option value="0.7">0.7</option>
                                            <option value="0.6">0.6</option>
                                            <option value="0.5">0.5</option>
                                            <option value="0.4">0.4</option>
                                            <option value="0.3">0.3</option>
                                            <option value="0.2">0.2</option>
                                            <option value="0.1">0.1</option>
                                            <option value="0.0">0.0</option>
                                        </select>
                                    </div>
                                </li>
                                <li class="noticed" style="display: none">
                                    <div>
                                        <label class="description" for="position_noticed"><?php echo _("Position") ?></label>
                                        <div>
                                            <select class="element select medium" id="position_noticed_<?php echo $imgData["id"]; ?>" name="position_noticed"> 
                                                <option value="" selected="selected"></option>
                                                <option value="1" ><?php echo _("Periphery") ?></option>
                                                <option value="2" ><?php echo _("Optic nerve head") ?></option>
                                                <option value="3" ><?php echo _("Macula") ?></option>
                                                <option value="4" ><?php echo _("Other") ?></option>
                                            </select>
                                        </div> 
                                    </div>
                                </li>
                            </div>
                        </div>

                        <li class="period_of_change" style="display: none">
                            <div>
                                <label class="description" for="change_prescription_period"><?php echo _("Period of change") ?></label>
                                <div>
                                    <select class="element select medium" id="change_prescription_period_<?php echo $imgData["id"]; ?>" name="change_prescription_period"> 
                                        <option value="" selected="selected"></option>
                                        <option value="1" ><6 <?php echo _("months") ?></option>
                                        <option value="2" >6-12 <?php echo _("months") ?></option>
                                        <option value="3" >>1 <?php echo _("year") ?></option>

                                    </select>
                                </div>
                            </div>
                        </li>	

                        <li>
                            <label class="description" for="comment"><?php echo _("Comment") ?> (<?php echo _("optional") ?>)
                            <?php
                            if($this->showRef && $this->userInfo["country"] != "DK" && $this->userInfo["country"] != "DE" && $this->userInfo["country"] != "CH" && $this->userInfo["country"] != "AT"){
                                echo '<i>' . _("Comments must be written in English") . '</i>';
                            }
                            ?>
                                </label>
                            <div>
                                <textarea id="comment_<?php echo $imgData["id"]; ?>" name="comment" maxlength="2000" class="element textarea medium"></textarea> 
                            </div> 
                        </li>
                        
                        <div class="euged-control euged-control-checkbox" id="referToScheckbox_Container">
                            <?php
                            if(empty($this->config['site']) || $this->config['site'] == 'retinalyze'){
                                ?>
                            <label><span class="euged-control-title"><?php echo _('I accept the ToS') ?></span><input type="checkbox" value="1" name="tos" class="referToScheckbox" id="checkbox_<?php echo $imgData['id'] ?>"></label>
                            <?php
                            }else{
                                ?>
                            <label><a href="http://www.optomedavenue.com/termsoftheservice" target="_blank"><span class="euged-control-title"><?php echo _('I accept the ToS') ?></span></a><input type="checkbox" value="1" name="tos" class="referToScheckbox" id="checkbox_<?php echo $imgData['id'] ?>"></label>
                            <?php
                            }
                            ?>
                            
                        </div>

                        <li class="buttons">
                            <input id="saveForm_<?php echo $imgData["id"]; ?>" class="btn btn-success" type="submit" name="submit" value="Submit" />
                        </li>
                    </ul>
                </form>	
                <script>
                    jQuery(document).ready(function () {
                        jQuery('#reason_<?php echo $imgData["id"]; ?>').on('change', function () {
                            if (this.value === '1')
                            {
                                jQuery('.symptoms').show();
                                hideNoticed();
                            } else if (this.value === '2')
                            {
                                jQuery('.noticed').show();
                                hideWhichSymptom();
                            } else if (this.value === '3')
                            {
                                hideNoticed();
                                hideWhichSymptom();
                            }
                        });
                        jQuery('#diagnosed_diabetes_<?php echo $imgData["id"]; ?>').on('change', function () {
                            if (this.checked == true)
                            {
                                jQuery('.durationdiabetes').show();
                            } else
                            {
                                jQuery('.durationdiabetes').hide();
                                jQuery('#duration_diabetes_<?php echo $imgData["id"]; ?>').val("");

                            }
                        });
                        jQuery('#which_sympton_<?php echo $imgData["id"]; ?>').on('change', function () {
                            if (this.value === '1')
                            {
                                jQuery('.loss_vision').show();
                                jQuery('.period_of_change').show();
                                hideChangePrescription(false);
                            } else if (this.value === '2')
                            {
                                jQuery('.change_presciption').show();
                                jQuery('.period_of_change').show();
                                hideVisionLoss(false);
                            } else
                            {
                                hideChangePrescription(true);
                                hideVisionLoss(true);
                            }
                        });
                        function hideNoticed() {
                            jQuery('.noticed').hide();
                            jQuery('#position_noticed_<?php echo $imgData["id"]; ?>').val("");
                        }
                        function hideWhichSymptom() {
                            jQuery('.symptoms').hide();
                            jQuery('#which_sympton_<?php echo $imgData["id"]; ?>').val("");
                            hideChangePrescription(true);
                            hideVisionLoss(true);
                        }
                        function hideChangePrescription(hidePeriod) {
                            jQuery('.change_presciption').hide();
                            if (hidePeriod === true) {
                                jQuery('.period_of_change').hide();
                            }
                            jQuery('#prescription_before_OS_<?php echo $imgData["id"]; ?>').val("");
                            jQuery('#presciption_after_OS_<?php echo $imgData["id"]; ?>').val("");
                            jQuery('#prescription_before_OD_<?php echo $imgData["id"]; ?>').val("");
                            jQuery('#presciption_after_OD_<?php echo $imgData["id"]; ?>').val("");
                            jQuery('#change_prescription_period_<?php echo $imgData["id"]; ?>').val("");
                        }
                        function hideVisionLoss(hidePeriod) {
                            jQuery('.loss_vision').hide();
                            if (hidePeriod === true) {
                                jQuery('.period_of_change').hide();
                            }
                            jQuery('#visus_before_od_<?php echo $imgData["id"]; ?>').val("");
                            jQuery('#visus_before_os_<?php echo $imgData["id"]; ?>').val("");
                            jQuery('#change_prescription_period_<?php echo $imgData["id"]; ?>').val("");
                        }
                        jQuery('#send_to_doc_form_<?php echo $imgData["id"]; ?>').on("submit", function () {
                            if (jQuery('#cpr_age_<?php echo $imgData["id"]; ?>').val().length === 0) {
        <?php
        if ($this->userInfo["country"] == "DK") {
            ?>
                                    alert("<?php echo _("Please input the CPR"); ?>");
            <?php
        } else {
            ?>
                                    alert("<?php echo _("Please input the age"); ?>");
            <?php
        }
        ?>
                                return false;
                            }
                            if (!jQuery('#checkbox_<?php echo $imgData["id"]; ?>').is(':checked')) {
                                alert("<?php echo _("Please accept the ToS"); ?>");
                                return false;
                            }
                            <?php
                            if(!empty($this->config['site']) && $this->config['site'] == "optomed"){
                            ?>
                                if (jQuery('#reason_<?php echo $imgData["id"]; ?>').val() === "") {
                                    alert("<?php echo _("Please input reason"); ?>");
                                    return false;
                                }
                            <?php
                            }
                            ?>
                        });
                    });

                </script>
            </div>
        </div>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

}