<?php

/*
 * All rights reserved RetinaLyze System A/S, Denmark
 */

namespace RetinaLyze\Views;

use RetinaLyze\Database\DatabaseHandler;

/**
 * Description of AnnotateFormCreator
 *
 * @author mom
 */
class AnnotateFormCreator {
    
    private $dbh;
    
    function __construct() {
        $this->dbh = new DatabaseHandler();
    }

    public function generate($imageID, $imageData) {
        $html = '<form action = "index.php?page=annotate" method = "post">';
        $html .= '<input type = "hidden" name = "imageID" value = "' . $imageID . '" />';
        $html .= '<p style = "padding-top: 20px;">Number of annotated images: ' . $this->dbh->getNumberOfAnnotatedImages()->fetch_assoc()['COUNT(*)'] . '</p>';
        $html .= '<p style = "padding-top: 20px;">Image ID: ' . $imageID . '</p>';
        $html .= '<h6 style = "">Image quality</h6>';
        $allQualityAnnotationAnswers = $this->dbh->getAllQualityAnnotationAnswers();
        
        //Check if already filled
        $annInfoDB = $this->dbh->getAnnotationInfo($imageID);
        
        if(!empty($annInfoDB->num_rows) && $annInfoDB->num_rows == 1){
            $annInfo = $annInfoDB->fetch_assoc();
            
            $patInfoDB = $this->dbh->getAnnotatePathologiesRelations($annInfo["annotateID"]);
            if(!empty($annInfoDB->num_rows) && $annInfoDB->num_rows > 0){
                $pathologiesAnnotateIDs = array();
                while($patInfo = $patInfoDB->fetch_assoc()){
                    $pathologiesAnnotateIDs[] = $patInfo['pathologiesAnnotateID'];
                }
            }else{
                $pathologiesAnnotateIDs = NULL;
            }
            $qualityAnnotateID = $annInfo["qualityAnnotateID"];
            $lateralityAnnotateID = $annInfo["lateralityAnnotateID"];
            $centeringAnnotateID = $annInfo["centeringAnnotateID"];
            $textAnnotateID = $annInfo["textAnnotateID"];
            $imagetypeAnnotateID = $annInfo["imagetypeAnnotateID"];  
            $html .= '<input type = "hidden" name = "annotatorUserID" value = "' . $annInfo["userID"] . '" />';
            $html .= '<input type = "hidden" name = "repeat" value = "true" />';
        }else{
            $qualityAnnotateID = NULL;
            $lateralityAnnotateID = NULL;
            $centeringAnnotateID = NULL;
            $textAnnotateID = NULL;
            $imagetypeAnnotateID = NULL;
            $pathologiesAnnotateIDs = NULL;
        }
        
        while($answer = $allQualityAnnotationAnswers->fetch_assoc()){
            if($qualityAnnotateID == NULL && $answer['qualityAnnotateID'] == 1){
                $html .=  '<input type="radio" name="qualityAnnotationAnswer" value="' . $answer['qualityAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['qualityAnnotateText'] . '</span><br style="display: inline;" />';
            }else if($qualityAnnotateID != NULL && $answer['qualityAnnotateID'] == $qualityAnnotateID){
                $html .=  '<input type="radio" name="qualityAnnotationAnswer" value="' . $answer['qualityAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['qualityAnnotateText'] . '</span><br style="display: inline;" />';
            }else{
                $html .=  '<input type="radio" name="qualityAnnotationAnswer" value="' . $answer['qualityAnnotateID'] . '" /><span style="margin-left: 5px;">' . $answer['qualityAnnotateText'] . '</span><br style="display: inline;" />';
            }
            
        }
        
        $html .= '<h6 style = "padding-top: 20px;">Pathologies</h6>';
        $allPathologiesAnnotationAnswers = $this->dbh->getAllPathologiesAnnotationAnswers();
        while($answer = $allPathologiesAnnotationAnswers->fetch_assoc()){
            if($pathologiesAnnotateIDs == NULL && $answer['pathologiesAnnotateID'] == 1){
                $html .=  '<input type="checkbox" name="pathologiesAnnotationAnswer[]" value="' . $answer['pathologiesAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['pathologiesAnnotateText'] . '</span><br style="display: inline;" />';
            }else if($pathologiesAnnotateIDs != NULL && in_array($answer['pathologiesAnnotateID'], $pathologiesAnnotateIDs)){
                $html .=  '<input type="checkbox" name="pathologiesAnnotationAnswer[]" value="' . $answer['pathologiesAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['pathologiesAnnotateText'] . '</span><br style="display: inline;" />';
            }else{
                $html .=  '<input type="checkbox" name="pathologiesAnnotationAnswer[]" value="' . $answer['pathologiesAnnotateID'] . '" /><span style="margin-left: 5px;">' . $answer['pathologiesAnnotateText'] . '</span><br style="display: inline;" />';
            }
        }
        
        $html .= '<h6 style = "padding-top: 20px;">Laterality</h6>';
        $allLateralityAnnotationAnswers = $this->dbh->getAllLateralityAnnotationAnswers();
        if(!empty($imageData['region']) && $imageData['region'] == 'OD'){
            $laterality = 'OD';
        }else if(!empty($imageData['region']) && $imageData['region'] == 'OS'){
            $laterality = 'OS';
        }else{
            $laterality = null;
        }
        while($answer = $allLateralityAnnotationAnswers->fetch_assoc()){
            if($lateralityAnnotateID != NULL && $answer['lateralityAnnotateID'] == $lateralityAnnotateID){
                $html .=  '<input type="radio" name="lateralityAnnotationAnswer" value="' . $answer['lateralityAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['lateralityAnnotateText'] . '</span><br style="display: inline;" />';
            }else if($lateralityAnnotateID == NULL && $answer['lateralityAnnotateID'] == 1 && $laterality == 'OD'){
                $html .=  '<input type="radio" name="lateralityAnnotationAnswer" value="' . $answer['lateralityAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['lateralityAnnotateText'] . '</span><br style="display: inline;" />';
            }else if($lateralityAnnotateID == NULL && $answer['lateralityAnnotateID'] == 2 && $laterality == 'OS'){
                $html .=  '<input type="radio" name="lateralityAnnotationAnswer" value="' . $answer['lateralityAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['lateralityAnnotateText'] . '</span><br style="display: inline;" />';
            }else if($lateralityAnnotateID == NULL && $answer['lateralityAnnotateID'] == 3 && $laterality == null){
                $html .=  '<input type="radio" name="lateralityAnnotationAnswer" value="' . $answer['lateralityAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['lateralityAnnotateText'] . '</span><br style="display: inline;" />';
            }else{
                $html .=  '<input type="radio" name="lateralityAnnotationAnswer" value="' . $answer['lateralityAnnotateID'] . '" /><span style="margin-left: 5px;">' . $answer['lateralityAnnotateText'] . '</span><br style="display: inline;" />';
            }
            
        }
        
        $html .= '<h6 style = "padding-top: 20px;">Center of image</h6>';
        $allCenteringAnnotationAnswers = $this->dbh->getAllCenteringAnnotationAnswers();
        while($answer = $allCenteringAnnotationAnswers->fetch_assoc()){
            if($centeringAnnotateID == NULL && $answer['centeringAnnotateID'] == 2){
                $html .=  '<input type="radio" name="centeringAnnotationAnswer" value="' . $answer['centeringAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['centeringAnnotateText'] . '</span><br style="display: inline;" />';
            }else if($centeringAnnotateID != NULL && $answer['centeringAnnotateID'] == $centeringAnnotateID){
                $html .=  '<input type="radio" name="centeringAnnotationAnswer" value="' . $answer['centeringAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['centeringAnnotateText'] . '</span><br style="display: inline;" />';
            }else{
                $html .=  '<input type="radio" name="centeringAnnotationAnswer" value="' . $answer['centeringAnnotateID'] . '" /><span style="margin-left: 5px;">' . $answer['centeringAnnotateText'] . '</span><br style="display: inline;" />';
            }
            
        }
        
        $html .= '<h6 style = "padding-top: 20px;">Is there text on the image?</h6>';
        $allTextAnnotationAnswers = $this->dbh->getAllTextAnnotationAnswers();
        while($answer = $allTextAnnotationAnswers->fetch_assoc()){
            if($textAnnotateID == NULL && $answer['textAnnotateID'] == 2){
                $html .=  '<input type="radio" name="textAnnotationAnswer" value="' . $answer['textAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['textAnnotateText'] . '</span><br style="display: inline;" />';
            }else if($textAnnotateID != NULL && $answer['textAnnotateID'] == $textAnnotateID){
                $html .=  '<input type="radio" name="textAnnotationAnswer" value="' . $answer['textAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['textAnnotateText'] . '</span><br style="display: inline;" />';
            }else{
                $html .=  '<input type="radio" name="textAnnotationAnswer" value="' . $answer['textAnnotateID'] . '" /><span style="margin-left: 5px;">' . $answer['textAnnotateText'] . '</span><br style="display: inline;" />';
            }
            
        }
        
        $html .= '<h6 style = "padding-top: 20px;">Image type/Image modality</h6>';
        $allImageTypeAnnotationAnswers = $this->dbh->getAllImageTypeAnnotationAnswers();
        while($answer = $allImageTypeAnnotationAnswers->fetch_assoc()){
            if($imagetypeAnnotateID == NULL && $answer['imagetypeAnnotateID'] == 1){
                $html .=  '<input type="radio" name="imageTypeAnnotationAnswer" value="' . $answer['imagetypeAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['imagetypeAnnotateText'] . '</span><br style="display: inline;" />';
            }else if($imagetypeAnnotateID != NULL && $answer['imagetypeAnnotateID'] == $imagetypeAnnotateID){
                $html .=  '<input type="radio" name="imageTypeAnnotationAnswer" value="' . $answer['imagetypeAnnotateID'] . '" checked="checked" /><span style="margin-left: 5px;">' . $answer['imagetypeAnnotateText'] . '</span><br style="display: inline;" />';
            }else{
                $html .=  '<input type="radio" name="imageTypeAnnotationAnswer" value="' . $answer['imagetypeAnnotateID'] . '" /><span style="margin-left: 5px;">' . $answer['imagetypeAnnotateText'] . '</span><br style="display: inline;" />';
            }
        }
	
	$html .=  '<p style="margin-top: 20px;"><input type="submit" value="'. _('Send response') . '" class="btn btn-success"></p>';				
	$html .=  '</form>';
        return $html;
    }
}
