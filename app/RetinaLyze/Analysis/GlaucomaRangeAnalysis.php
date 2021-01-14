<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Analysis;

use RetinaLyze\Analysis\GlaucomaAnalysis;
use RetinaLyze\Database\DatabaseHandler;

/**
 * Description of GlaucomaRangeAnalysis
 *
 * @author mom
 */
class GlaucomaRangeAnalysis {
    public function analyzePhotoRange($startID, $endID, $fixedLaterality = false) {
        $dbh = new DatabaseHandler();
        //Get photo information
        $notAnaInfo = $dbh->getAllImgsInRange($startID, $endID);
        echo "Number of rows: " . $notAnaInfo->num_rows . "\n";
        while ($notAnaInfoRow = $notAnaInfo->fetch_assoc()) {
            if($notAnaInfoRow['glaucomaResult'] != null){
                echo "Skipping: " . $notAnaInfoRow['id'] . "\n";
                continue;
            }
            $userID = $notAnaInfoRow["userID"];
            $username = $notAnaInfoRow["username"];
            $imgID = $notAnaInfoRow["id"];
            $glaucomaAnalysis = new GlaucomaAnalysis($username, $imgID, $userID);
            if($fixedLaterality){
                $glaucomaAnalysis->setLateralityOfImage('OD');
            }
            try{
                $glaucomaAnalysis->startSegmentation(0, 0);
            } catch (\RetinaLyze\Exception\ImageIsSaturatedException $ex){
                error_log('Image is saturated. ImageID: ' . $imgID);
                continue;
            } catch (\Exception $ex) {
                echo 'Could not do segmentation. Continueing. ImageID: ' . $imgID;
                error_log('Could not do segmentation. Continueing. ImageID: ' . $imgID);
                error_log($ex->getMessage());
                error_log($ex->getTraceAsString());
                continue;
            }
            try{
                $post['userDefinedDicsBorder'] = '';
                $glaucomaAnalysis->startAnalysis($post);
            } catch (\Exception $ex) {
                echo 'Could not do analysis. Continueing. ImageID: ' . $imgID;
                error_log('Could not do analysis. Continueing. ImageID: ' . $imgID);
                continue;
            }
            echo 'Analyzed : '. $imgID . "\n";
        }
        return true;
    }
}
