<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Analysis;

use RetinaLyze\Exception\RuntimeException;
use RetinaLyze\Exception\UnexpectedValueException;

/**
 * Description of ResultFileHandler
 *
 * @author mom
 */
class ResultFileHandler {
    /**
     * Extract the information from a result file.
     * 
     * @param string $txtFileStream The key for the object that will be putted
     * @param int $type The path to the object that have to be uploaded
     * @return array Return array with the extracted information keys: "annotations", "opticnervehead", "quality"
     * @throws RuntimeException
     * @throws UnexpectedValueException
     */
    
    public static function extractResultData($txtFileStream, $type) {
        //Get the annotation and result files
        //Get the number of annotations from the result
        if(empty($txtFileStream)){
            throw new RuntimeException("Could not parse result file");
        }
        $resultString = $txtFileStream;
        $resultArray = preg_split("/\r\n|\n|\r/", $resultString);
        if($resultArray === false){
            throw new RuntimeException("Could not get split the result data");
        }
        $darkLesionsString = $resultArray[0];
        $brightLesionsString = $resultArray[1];
        $opticNerveHeadString = $resultArray[2];
        $qualityString = $resultArray[3];
        
        //Extract annotations
        if ($type == 0) { //If DR analysis
            $annotations = ResultFileHandler::extractValueFromString($darkLesionsString, "darklesions=");
        } else { //type = "1" (AMD)
            $annotations = ResultFileHandler::extractValueFromString($brightLesionsString, "brightlesions=");
        }
        
        //Check that the $annotations variable only contain a number
        if (!ctype_digit($annotations)) {
            throw new UnexpectedValueException("The annotation result wasn't a number");
        }
        
        //Get optic nerve head annotation number
        $opticNerveHeadAnns = ResultFileHandler::extractValueFromString($opticNerveHeadString, "opticnerveheads=");
        //Check that the $annotations variable only contain a number
        if (!ctype_digit($opticNerveHeadAnns)) {
            throw new UnexpectedValueException("The optic nervehead result wasn't a number");
        }

        //Get quality (0 = UNACCEPTABLE, 1 = ACCEPTABLE)
        $qualityResult = ResultFileHandler::extractValueFromString($qualityString, "quality=");
        if($qualityResult == "UNACCEPTABLE" || $qualityResult == "ACCEPTABLE"){
            $quality = $qualityResult == "ACCEPTABLE" ? 1 : 0;
        }else{
            throw new UnexpectedValueException("The quality result cannot be interpeted");
        }
        
        return array("annotations" => $annotations,
            "opticnervehead" => $opticNerveHeadAnns,
            "quality" => $quality,
            );
    }
    
    public static function extractValueFromString($string, $key){
        if (strpos($string, $key) !== false) {
            $value = str_replace($key, "", $string);
            return $value;
        } else {
            throw new RuntimeException("Could not get ". $key . " from steam");
        }
    }
}
