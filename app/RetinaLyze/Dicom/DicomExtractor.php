<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Dicom;

use deanvaughan\Dicom\DicomConvert;
use deanvaughan\Dicom\DicomTag;


/**
 * Description of DicomExtractor
 *
 * @author mom
 */
class DicomExtractor {
    /*
     * Extract the tags which corresponds to name, id and laterality
     * 
     * @param string $dcmFilePath
     * @param string $jpgFilePath
     * @throws Exception
     */
    public static function convertToJpg($dcmFilePath, $jpgFilePath){
        if(!file_exists($dcmFilePath)) {
            throw new \Exception("File does not exist");
        }
        $dc = new DicomConvert;
        $dc->file = $dcmFilePath;
        $dc->jpg_file = $jpgFilePath;
        $dc->dcm_to_jpg();
    }
    
    /*
     * Extract the tags which corresponds to name, id and laterality
     * 
     * @param string $filePath
     * @throws Exception
     */
    public static function getTags($filePath){
        if(!file_exists($filePath)) {
            throw new \Exception("File does not exist");
        }
        $dt = new DicomTag($filePath);
        if($dt->load_tags() === false){
            throw new \Exception("Could not extract tags");
        }
        
        $name = empty($dt->tags["0010,0010"]) ? "" : $dt->tags["0010,0010"];
        $id = empty($dt->tags["0010,0020"]) ? $name : $dt->tags["0010,0020"];
        $camerModel = empty($dt->tags["0008,1090"]) ? NULL : $dt->tags["0008,1090"];
        if($camerModel == "VISUCAM PRO NM 2"){
            $stringArrayName = explode("^", $name);
            $firstname = empty($stringArrayName[1]) ? '' : $stringArrayName[1];
            $lastname = empty($stringArrayName[0]) ? '' : $stringArrayName[0];
            $name = $firstname . ' ' . $lastname;
        }
        
        if($camerModel == "DRS"){
            $stringArrayName = explode("^", $name);
            $firstname = empty($stringArrayName[1]) ? '' : $stringArrayName[1];
            $lastname = empty($stringArrayName[0]) ? '' : $stringArrayName[0];
            $name = $firstname . ' ' . $lastname;
        }
        //Start by trying to get Image Laterality (0020,0062), if that is empty then get Laterality (0020,0060)
        
        if (!empty($dt->tags["0020,0062"])) {
            $laterality = $dt->tags["0020,0062"];
        }else if(!empty($dt->tags["0020,0060"])){
            $laterality = $dt->tags["0020,0060"];
        }else{
            $laterality = NULL;
        }
        
        if(!empty($dt->tags["0008,0018"])){
            $instanceuuid = $dt->tags["0008,0018"];
        }else{
            $instanceuuid = NULL;
        }
        
        \error_log("Tags: " . print_r($dt->tags, true));
        
        $laterality = $laterality == "L" ? "OS" : $laterality;
        $laterality = $laterality == "R" ? "OD" : $laterality;
        
        return array("name" => $name,
            "id" => $id,
            "region" => $laterality,
            "instanceUUID" => $instanceuuid
            );
    }
}
