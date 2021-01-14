<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\PDF;

use RetinaLyze\Database\DatabaseHandler;

/**
 * Description of PDFDemoPackageGenerator
 *
 * @author mom
 */
class PDFDemoPackageGenerator {

    public static function generatePackage($userID, $lang) {
        $dbh = new DatabaseHandler();
        $userInfo = $dbh->getUserInfoFromID($userID)->fetch_assoc();
        $userSettings = $dbh->getUserSettingsFromID($userID)->fetch_assoc();
        
        $tempfoldername = sys_get_temp_dir(). DIRECTORY_SEPARATOR . uniqid() . "/";
        mkdir($tempfoldername);
        
        if($userSettings['onlyESB'] != 1){
            if($userSettings['DRenabled'] == 1 && $userSettings['AMDenabled'] == 1 && $userSettings['GlaucomaEnabled'] == 1){
                //Generate DR green AMD green Glaucoma green
                $demoData = array("annotationsDR" => 0,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => 1,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_green_AMD_green_Glaucoma_green.pdf");
                
                //Generate DR green AMD green Glaucoma yellow
                $demoData = array("annotationsDR" => 0,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => 2,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_green_AMD_green_Glaucoma_yellow.pdf");
                
                //Generate DR green AMD green Glaucoma red
                $demoData = array("annotationsDR" => 0,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => 3,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_green_AMD_green_Glaucoma_red.pdf");
                
                //Generate DR green AMD yellow Glaucoma green
                $demoData = array("annotationsDR" => 0,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => 1,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_green_AMD_yellow_Glaucoma_green.pdf");
                
                //Generate DR green AMD yellow Glaucoma yellow
                $demoData = array("annotationsDR" => 0,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => 2,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_green_AMD_yellow_Glaucoma_yellow.pdf");
                
                //Generate DR green AMD yellow Glaucoma red
                $demoData = array("annotationsDR" => 0,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => 3,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_green_AMD_yellow_Glaucoma_red.pdf");
                
                //Generate DR yellow AMD green Glaucoma green
                $demoData = array("annotationsDR" => 3,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => 1,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_yellow_AMD_green_Glaucoma_green.pdf");
                
                //Generate DR yellow AMD green Glaucoma yellow
                $demoData = array("annotationsDR" => 3,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => 2,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_yellow_AMD_green_Glaucoma_yellow.pdf");
                
                //Generate DR yellow AMD green Glaucoma red
                $demoData = array("annotationsDR" => 3,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => 3,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_yellow_AMD_green_Glaucoma_red.pdf");
                
                //Generate DR yellow AMD yellow Glaucoma green
                $demoData = array("annotationsDR" => 3,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => 1,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_yellow_AMD_yellow_Glaucoma_green.pdf");
                
                //Generate DR yellow AMD yellow Glaucoma yellow
                $demoData = array("annotationsDR" => 3,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => 2,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_yellow_AMD_yellow_Glaucoma_yellow.pdf");
                
                //Generate DR yellow AMD yellow Glaucoma red
                $demoData = array("annotationsDR" => 3,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => 3,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_yellow_AMD_yellow_Glaucoma_red.pdf");
                
                //Generate DR red AMD green Glaucoma green
                $demoData = array("annotationsDR" => 100,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => 1,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_red_AMD_green_Glaucoma_green.pdf");
                
                //Generate DR red AMD green Glaucoma yellow
                $demoData = array("annotationsDR" => 100,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => 2,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_red_AMD_green_Glaucoma_yellow.pdf");
                
                //Generate DR red AMD green Glaucoma red
                $demoData = array("annotationsDR" => 100,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => 3,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_red_AMD_green_Glaucoma_red.pdf");
                
                //Generate DR red AMD yellow Glaucoma green
                $demoData = array("annotationsDR" => 100,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => 1,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_red_AMD_yellow_Glaucoma_green.pdf");
                
                //Generate DR red AMD yellow Glaucoma yellow
                $demoData = array("annotationsDR" => 100,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => 2,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_red_AMD_yellow_Glaucoma_yellow.pdf");
                
                //Generate DR red AMD yellow Glaucoma red
                $demoData = array("annotationsDR" => 100,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => 3,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_red_AMD_yellow_Glaucoma_red.pdf");
            }
            if($userSettings['DRenabled'] == 1 && $userSettings['AMDenabled'] == 1){
                //Generate DR green AMD green
                $demoData = array("annotationsDR" => 0,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => NULL,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_green_AMD_green.pdf");

                //Generate DR yellow AMD green
                $demoData = array("annotationsDR" => 3,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => NULL,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_yellow_AMD_green.pdf");

                //Generate DR red AMD green
                $demoData = array("annotationsDR" => 100,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => NULL,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_red_AMD_green.pdf");

                //Generate DR green AMD yellow
                $demoData = array("annotationsDR" => 0,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => NULL,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_green_AMD_yellow.pdf");

                //Generate DR yellow AMD yellow
                $demoData = array("annotationsDR" => 3,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => NULL,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_yellow_AMD_yellow.pdf");

                //Generate DR red AMD yellow
                $demoData = array("annotationsDR" => 100,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => NULL,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_red_AMD_yellow.pdf");
            }
            if($userSettings['DRenabled'] == 1 && $userSettings['GlaucomaEnabled'] == 1){
                //Generate DR green Glaucoma green
                $demoData = array("annotationsDR" => 0,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => 1,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_green_Glaucoma_green.pdf");
                
                //Generate DR green Glaucoma yellow
                $demoData = array("annotationsDR" => 0,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => 2,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_green_Glaucoma_yellow.pdf");
                
                //Generate DR green Glaucoma red
                $demoData = array("annotationsDR" => 0,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => 3,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_green_Glaucoma_red.pdf");
                
                //Generate DR yellow Glaucoma green
                $demoData = array("annotationsDR" => 3,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => 1,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_yellow_Glaucoma_green.pdf");
                
                //Generate DR yellow Glaucoma yellow
                $demoData = array("annotationsDR" => 3,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => 2,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_yellow_Glaucoma_yellow.pdf");
                
                //Generate DR yellow Glaucoma red
                $demoData = array("annotationsDR" => 3,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => 3,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_yellow_Glaucoma_red.pdf");
                
                //Generate DR red Glaucoma green
                $demoData = array("annotationsDR" => 100,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => 1,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_red_Glaucoma_green.pdf");
                
                //Generate DR red Glaucoma yellow
                $demoData = array("annotationsDR" => 100,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => 2,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_red_Glaucoma_yellow.pdf");
                
                //Generate DR red Glaucoma red
                $demoData = array("annotationsDR" => 100,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => 3,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_red_Glaucoma_red.pdf");
            }
            if($userSettings['AMDenabled'] == 1 && $userSettings['GlaucomaEnabled'] == 1){
                //Generate AMD green Glaucoma green
                $demoData = array("annotationsDR" => NULL,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => 1,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "AMD_green_Glaucoma_green.pdf");
                
                //Generate AMD green Glaucoma yellow
                $demoData = array("annotationsDR" => NULL,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => 2,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "AMD_green_Glaucoma_yellow.pdf");
                
                //Generate AMD green Glaucoma red
                $demoData = array("annotationsDR" => NULL,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => 3,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "AMD_green_Glaucoma_red.pdf");
                
                //Generate AMD yellow Glaucoma green
                $demoData = array("annotationsDR" => NULL,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => 1,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "AMD_yellow_Glaucoma_green.pdf");
                
                //Generate AMD yellow Glaucoma yellow
                $demoData = array("annotationsDR" => NULL,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => 2,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "AMD_yellow_Glaucoma_yellow.pdf");
                
                //Generate AMD yellow Glaucoma red
                $demoData = array("annotationsDR" => NULL,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => 3,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "AMD_yellow_Glaucoma_red.pdf");
            }
            if($userSettings['AMDenabled'] == 1){
                //Generate AMD green
                $demoData = array("annotationsDR" => NULL,
                    "annotationsAMD" => 0,
                    "glaucomaResult" => NULL,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "AMD_green.pdf");

                //Generate AMD yellow
                $demoData = array("annotationsDR" => NULL,
                    "annotationsAMD" => 100,
                    "glaucomaResult" => NULL,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "AMD_yellow.pdf");
            }
            
            if($userSettings['GlaucomaEnabled'] == 1){
                //Generate Glaucoma green
                $demoData = array("annotationsDR" => NULL,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => 1,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(1, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "Glaucoma_green.pdf");

                //Generate Glaucoma yellow
                $demoData = array("annotationsDR" => NULL,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => 2,
                    "status" => NULL,
                    "answerComment" => NULL,
                    "isSaturated" => 0);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(1, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "Glaucoma_yellow.pdf");
                
                //Generate Glaucoma red
                $demoData = array("annotationsDR" => NULL,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => 3,
                    "status" => NULL,
                    "answerComment" => NULL,
                    "isSaturated" => 0);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(1, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "Glaucoma_red.pdf");

            }
            if($userSettings['DRenabled'] == 1){
                //Generate DR green
                $demoData = array("annotationsDR" => 0,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => NULL,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_green.pdf");

                //Generate DR yellow
                $demoData = array("annotationsDR" => 3,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => NULL,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_yellow.pdf");

                //Generate DR red
                $demoData = array("annotationsDR" => 100,
                    "annotationsAMD" => NULL,
                    "glaucomaResult" => NULL,
                    "status" => NULL,
                    "answerComment" => NULL);
                $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
                $pdfGenerator->generate($lang, $tempfoldername . "DR_red.pdf");
            }
            
        }
        if(!empty($userInfo['lbRegion']) || $userSettings['referral'] == 1){
            //Generate Eye specialist comment
            $demoData = array("annotationsDR" => 100,
                "annotationsAMD" => 100,
                "glaucomaResult" => NULL,
                "status" => 1,
                "answerComment" => "Signs of background diabetic retinopthathy. Recommendation: see ophthalmologist within 6 months");
            $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
            $pdfGenerator->generate($lang, $tempfoldername . "Eye_specialist_comment.pdf");

            //Generate Eye specialist comment
            $demoData = array("annotationsDR" => 0,
                "annotationsAMD" => 0,
                "glaucomaResult" => NULL,
                "status" => 1,
                "answerComment" => "");
            $pdfGenerator = new \RetinaLyze\PDF\PDFGenerator(2, $userID, NULL, $demoData);
            $pdfGenerator->generate($lang, $tempfoldername . "Eye_specialist_nothing.pdf");
        }

        $zip = new \ZipArchive();
        $filename = "pdfs.zip";

        if ($zip->open($tempfoldername . $filename, \ZipArchive::CREATE) !== TRUE) {
            error_log("Error: cannot open <$filename>");
            return false;
        }
        
        $PDFfiles = array_diff(scandir($tempfoldername), array('.', '..'));
        
        foreach ($PDFfiles as $file) {
            $zip->addFile($tempfoldername . $file, $file);
        }
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=' . $filename);
        header('Content-Length: ' . filesize($tempfoldername . $filename));
        readfile($tempfoldername . $filename);

        $it = new \RecursiveDirectoryIterator($tempfoldername, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($tempfoldername);
    }

}
