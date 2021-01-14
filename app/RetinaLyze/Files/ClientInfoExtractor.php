<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Files;

/**
 * Description of ClientInfoExtractor
 *
 * @author mom
 */
class ClientInfoExtractor {

    // Example of filename: 1011343_20150924_115633_Color_L_001.jpg
    // 1011343 is client ID
    // L is side
    public function extractTopconIMAGENET($filename) {
        $clientInfo = array();

        $tok = strtok($filename, "_");

        for ($i = 0; $tok !== false; $i++) {
            if ($i == 0) {
                $clientInfo["id"] = $tok;
            }
            if ($i == 4) {
                $region = $tok;
            }
            $tok = strtok("_");
        }
        if ($region == "L") {
            $clientInfo["region"] = "OS";
        } else if ($region == "R") {
            $clientInfo["region"] = "OD";
        } else {
            //Could not determinenate the region
            $clientInfo["region"] = NULL;
        }

        $clientInfo["name"] = "";
        return $clientInfo;
    }
    
    // Example of filename:  4AG0ZLEP5IF7IHU6TOPCON-Test testesen_12345678_OS_00.JPG
    // 12345678 is client ID
    // Test testesen is name
    // OS is side
    public function extractTopconIBase($filename) {
        $clientInfo = array();

        // Find position of first dash
        $posDash = strpos($filename, "-");

        // Check if it found a dash
        if ($posDash !== false) {
            // Remove the anything before the first dash (including the dash)
            $filename = substr($filename, $posDash + 1);
            // Find position of the last underscore
            $posUnderscore = strrpos($filename, "_");

            if ($posDash !== false) {
                $filename = substr($filename, 0, $posUnderscore - strlen($filename));
                $tok = strtok($filename, "_");

                for ($i = 0; $tok !== false; $i++) {
                    if ($i == 0) {
                        $clientInfo["name"] = $tok;
                    }else if ($i == 1) {
                        $clientInfo["id"] = $tok;
                    }else if ($i == 2) {
                        $clientInfo["region"] = $tok;
                    }
                    $tok = strtok("_");
                }
            }
        }
        return $clientInfo;
    }

    public function extractTopconAndNidek($filename) {
        $clientInfo = array();

        // Find position of first dash
        $posDash = strpos($filename, "-");

        // Check if it found a dash
        if ($posDash !== false) {
            // Remove the anything before the first dash (including the dash)
            $filename = substr($filename, $posDash + 1);
            // Find position of the last underscore
            $posUnderscore = strrpos($filename, "_");

            if ($posDash !== false) {
                $filename = substr($filename, 0, $posUnderscore - strlen($filename));
                $tok = strtok($filename, "_");

                for ($i = 0; $tok !== false; $i++) {
                    if ($i == 0) {
                        $clientInfo["name"] = $tok;
                    }
                    if ($i == 1) {
                        $clientInfo["id"] = $tok;
                    }
                    $tok = strtok("_");
                }
            }
        }
        $clientInfo["region"] = NULL;
        return $clientInfo;
    }

    // Example of filename: Aaltonen, Anja (P1464456750)_OS.jpg
    // Firstname: Anja
    // Surname: Aaltonen
    // ID: P1464456750
    // Region: OS
    public function extractCobra26($filename) {
        $clientInfo = array();

        //Get string until ,
        $lastname = strtok($filename, ",");
        //Get string until ( and remove whitespace after comma
        $firstname = substr(strtok("("), 1);

        //Construct name
        $clientInfo["name"] = $firstname . $lastname;

        //Get string until )
        $clientInfo["id"] = strtok(")");

        //Get string until . and remove _
        $clientInfo["region"] = substr(strtok("."), 1);

        return $clientInfo;
    }

    // Example of filename: 49014_20140514102202872.jpg
    // Another possible filename format: 49014_20180327_102321_Color_L_RetinalImage.jpg
    // ID: 49014
    // Region: L
    public function extractCanon($filename) {
        $clientInfo = array();

        $stringArray = explode("_", $filename);
        
        //Get string until _ (ID)
        $clientInfo["id"] = $stringArray[0];

        //No name to extract
        $clientInfo["name"] = "";

        //No region to extract
        $region = !empty($stringArray[4]) ? $stringArray[4] : null;

        if($region == "L"){
            $clientInfo["region"] = "OS";
        }else if($region == "R"){
            $clientInfo["region"] = "OD";
        }else if($region == "OS"){
            $clientInfo["region"] = "OS";
        }else if($region == "OD"){
            $clientInfo["region"] = "OD";
        }else{
            //Could not determinate the region
            $clientInfo["region"] = NULL;
        }

        return $clientInfo;
    }

    // Example of foldername: Example Cobra, Miscellanea (P0084948745)
    // Example of filename: 001_[1-7]_OD_20110408112659_.jpg
    // Firstname: Anja
    // Surname: Aaltonen
    // ID: P1464456750
    // Region: OS
    public function extractCobra30($foldername, $filename) {
        $clientInfo = array();

        //Get string until , from foldername
        $lastname = strtok($foldername, ",");
        //Get string until ( and remove whitespace after comma from foldername
        $firstname = substr(strtok("("), 1);

        //Construct name
        $clientInfo["name"] = $firstname . $lastname;

        //Get string until )
        $clientInfo["id"] = strtok(")");

        //Get region from foldername
        $tok = strtok($filename, "_");

        for ($i = 0; $tok !== false; $i++) {
            if ($i == 2) {
                $clientInfo["region"] = $tok;
            }
            $tok = strtok("_");
        }

        return $clientInfo;
    }

    // Example of filename: ID191133-17185.jpg
    // ID: 49014
    public function extractOptimalOptik($filename) {
        $clientInfo = array();

        //Get string until -, and remove the first two characters. 
        $clientInfo["id"] = substr(strtok($filename, "-"), 2);

        //No name to extract
        $clientInfo["name"] = "";

        //No region to extract
        $clientInfo["region"] = NULL;

        return $clientInfo;
    }

    // Example of filename: 1_morten_eye_20160830_OD_1.jpg
    // Example of foldername: 1_morten_eye_20160830 (not used)
    // ID: 1
    // Name: morten
    // Eye: OD
    public function extractHorus($foldername, $filename) {
        $clientInfo = array();

        $stringArray = explode("_", $filename);

        $clientInfo["id"] = $stringArray[0];
        $clientInfo["name"] = $stringArray[1];
        $clientInfo["region"] = $stringArray[4];

        return $clientInfo;
    }

    // Example of filename: 1234_Baltensperger_Evelyne__60_Image_OS_2015-11-06_12-01-51_F_1961-12-12_Main Report.jpg
    // ID: 1234
    // Surname: Baltensperger
    // Firstname: Evelyne
    // Eye: OS
    public function extractOptovueiCam($filename) {
        $clientInfo = array();

        $stringArray = explode("_", $filename);

        $clientInfo["id"] = $stringArray[0];
        $firstname = $stringArray[1];
        $surname = $stringArray[2];
        $clientInfo["name"] = $firstname . " " . $surname;
        $clientInfo["region"] = $stringArray[6];

        return $clientInfo;
    }

    public function extractAllFolder($foldername, $filename) {
        $clientInfo = array();
        
        //Construct name
        $clientInfo["name"] = " ";
        
        //Get string until )
        $clientInfo["id"] = $foldername;
             
	return $clientInfo;
    }
    
    // Example of filename: TOPCON-_first_last_1000_20161110_L_005
    // ID: 1000
    // Surname: last
    // Firstname: first
    // Eye: L
    public function extractEzcapture($filename) {
        $clientInfo = array();
        
        $stringArray = explode("_", $filename);
        
        $clientInfo["id"] = $stringArray[3];
        $firstname = $stringArray[1];
        $surname = $stringArray[2];
        $clientInfo["name"] = $firstname . " " . $surname;
        $region = $stringArray[5];
        
        if($region == "L"){
            $clientInfo["region"] = "OS";
        }else if($region == "R"){
            $clientInfo["region"] = "OD";
        }else{
            //Could not determinenate the region
            $clientInfo["region"] = NULL;
        }
        
	return $clientInfo;
    }
    
    // Example of filename: 1000_first_last_L_005.jpg
    // ID: 1000
    // Surname: last
    // Firstname: first
    // Eye: L
    public function extractIMAGENET6($filename) {
        $clientInfo = array();
        
        $stringArray = explode("_", $filename);
        
        $clientInfo["id"] = $stringArray[0];
        $firstname = $stringArray[1];
        $surname = $stringArray[2];
        $clientInfo["name"] = $firstname . " " . $surname;
        $region = $stringArray[3];
        
        if($region == "L"){
            $clientInfo["region"] = "OS";
        }else if($region == "R"){
            $clientInfo["region"] = "OD";
        }else{
            //Could not determinenate the region
            $clientInfo["region"] = NULL;
        }
        
	return $clientInfo;
    }
    
    // Example of filename: farsoe_1000_last_first_20191231_120000_Color_L_005.jpg
    // ID: 1000
    // Surname: last
    // Firstname: first
    // Eye: L
    public function extractAAUH($filename) {
        $clientInfo = array();
        
        \error_log('TEST AAUH' . $filename);
        
        $stringArray = explode("_", $filename);
        
        $clientInfo["id"] = $stringArray[1];
        $firstname = $stringArray[2];
        $surname = $stringArray[3];
        $clientInfo["name"] = $firstname . " " . $surname;
        $region = $stringArray[7];
        
        if($region == "L"){
            $clientInfo["region"] = "OS";
        }else if($region == "R"){
            $clientInfo["region"] = "OD";
        }else{
            //Could not determinenate the region
            $clientInfo["region"] = NULL;
        }
        
	return $clientInfo;
    }
    
    
    // Example of filename: 496175_196522_OS_2.jpg
    // 496175 is image ID
    // 196522 is customer ID
    // OS is side
    // 2 is cameraID
    public function extractFromRetinaLyzeDatabase($filename) {
        $clientInfo = array();
        $tok = strtok($filename, "_");
        for ($i = 0; $tok !== false; $i++) {
            if ($i == 1) {
                $clientInfo["id"] = $tok;
            }
            if ($i == 2) {
                $region = $tok;
            }
            if ($i == 3) {
                $cameraID = strtok($tok ,".");
            }
            $tok = strtok("_");
        }
        if ($region == "OS" || $region == "OD") {
            $clientInfo["region"] = $region;
        } else {
            $clientInfo["region"] = NULL;
        }
        $clientInfo["cameraID"] = $cameraID;
        $clientInfo["name"] = "";
        return $clientInfo;
    }
    
    // Example of filename: Barroso_marino_Asuncion_OS_14_1_rgb.jpg
    // Asuncion is Firstname
    // Barroso_marino is Lastname
    // OS is side
    public function extractDRS($filename) {
        $clientInfo = array();

        $tok = strtok($filename, "_");
        $count = 0;
        $clientInfo["region"] = NULL;
        for ($i = 0; $tok !== false; $i++) {
            if($tok == 'OS' || $tok == 'OD'){
                $count = $i;
                if ($tok == "OS") {
                    $clientInfo["region"] = "OS";
                } else if ($tok == "OD") {
                    $clientInfo["region"] = "OD";
                }
            }
            $tok = strtok("_");
        }
        $id = "";
        $tok = strtok($filename, "_");
        for ($i = 0; $tok !== false; $i++) {
            if ($i < $count) {
                if($id == ""){
                    $id .= $tok;
                }else{
                    $id .= " " . $tok;
                }
                
            }
            $tok = strtok("_");
        }
        $clientInfo["id"] = $id;
        $clientInfo["name"] = "";
        return $clientInfo;
    }
    
    // Example of filename: last_first_1000_(4571).jpg
    // ID: 1000
    // Surname: last
    // Firstname: first
    // Eye: L
    public function extractZiess($filename) {
        $clientInfo = array();
        
        $stringArray = explode("_", $filename);
        
        $clientInfo["id"] = $stringArray[2];
        $firstname = $stringArray[1];
        $surname = $stringArray[0];
        $clientInfo["name"] = $firstname . " " . $surname;
        $region = NULL;
        
	return $clientInfo;
    }
    
    public function extractFilenameAsClientIDAndName($filename) {
        $clientInfo = array();
        
        
        $clientInfo["id"] = $filename;
        $clientInfo["name"] = "";
        $region = NULL;
        
	return $clientInfo;
    }
    
    // Example of filename: ID1-78.jpg
    // ID: 1
    // Name: 
    // Eye: 
    public function extractKowa($filename) {
        $clientInfo = array();

        $stringArray = explode("-", $filename);

        $clientInfo["id"] = str_replace("ID", "", $stringArray[0]);
        $clientInfo["name"] = "";
        $clientInfo["region"] = NULL;

        return $clientInfo;
    }
}
