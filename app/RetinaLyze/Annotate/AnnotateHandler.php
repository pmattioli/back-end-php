<?php

/*
 * All rights reserved RetinaLyze System A/S, Denmark
 */

namespace RetinaLyze\Annotate;

use RetinaLyze\Database\DatabaseHandler;

/**
 * Description of AnnotateHandler
 *
 * @author mom
 */
class AnnotateHandler {
    private $dbHandler;

    function __construct() {
        $this->dbHandler = new DatabaseHandler();
    }
    
    function createUpdateAnnotationFromFormData($post_array, $userid, $optUserID = null) {
        $imageID = $post_array["imageID"];
        if(empty($post_array["imageID"])){
            throw new \Exception("No image ID provided");
        }
        $pathologiesAnnotationAnswers = $post_array["pathologiesAnnotationAnswer"];

        $qualityAnnotationAnswer = !empty($post_array["qualityAnnotationAnswer"]) ? (int)$post_array["qualityAnnotationAnswer"] : null;
        $lateralityAnnotationAnswer = !empty($post_array["lateralityAnnotationAnswer"]) ? (int)$post_array["lateralityAnnotationAnswer"] : null;
        $centeringAnnotationAnswer = !empty($post_array["centeringAnnotationAnswer"]) ? (int)$post_array["centeringAnnotationAnswer"] : null;
        $textAnnotationAnswer = !empty($post_array["textAnnotationAnswer"]) ? (int)$post_array["textAnnotationAnswer"] : null;
        $imageTypeAnnotationAnswer = !empty($post_array["imageTypeAnnotationAnswer"]) ? (int)$post_array["imageTypeAnnotationAnswer"] : null;

        $this->dbHandler->createUpdateAnnotationFromFormData($imageID, $pathologiesAnnotationAnswers, $qualityAnnotationAnswer, $lateralityAnnotationAnswer, $centeringAnnotationAnswer, $textAnnotationAnswer, $imageTypeAnnotationAnswer,$userid, $optUserID);
    }
}
