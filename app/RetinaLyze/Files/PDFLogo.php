<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Files;

use RetinaLyze\Database\DatabaseHandler;
use RetinaLyze\Utils\Config;
use RetinaLyze\Files\FilesystemFactory;

/**
 * Description of Logo
 *
 * @author mom
 */
class PDFLogo {
    public static function getPublicLogoPath($pdfSettinsID) {
        //Get config
        $config = Config::getConfig();
        return $config['base_url'] . 'pdf/getLogo.php?id=' . $pdfSettinsID;
    }
    
    public static function getPublicLogoPathFromChainID($chainID) {
        //Get config
        $config = Config::getConfig();
        
        //Get PDF settins information
        $dbh = new DatabaseHandler();
        $pdfSettinsInfoDB = $dbh->getPDFSettinsInfo($chainID);
        $pdfSettinsInfo = $pdfSettinsInfoDB->fetch_assoc();
        if(empty($pdfSettinsInfo['logo_file_path'])){
            return "";
        }
        return $config['base_url'] . 'pdf/getLogo.php?id=' . $pdfSettinsInfo['pdfChainID'];
    }
    
    public static function getLogo($pdfSettingsID){
        //Get config
        $config = Config::getConfig();
        
        $filesystem = FilesystemFactory::create($config['logo_files_path'], $config['logo_filesystem']);
        
        //Get PDF settins information
        $dbh = new DatabaseHandler();
        $pdfSettinsInfoDB = $dbh->getPDFSettinsInfoFromPDFSettingsID($pdfSettingsID);
        $pdfSettinsInfo = $pdfSettinsInfoDB->fetch_assoc();
        if(empty($pdfSettinsInfo['logo_file_path'])){
            return null;
        }
        
        //Get extention of file
        $path_parts = pathinfo($pdfSettinsInfo['logo_file_path']);
        $extension = $path_parts['extension'];
        
        try{
            $fp = $filesystem->readStream($pdfSettinsInfo['logo_file_path']);
        
            header("Content-Type: image/" . $extension);
            header("Content-Length: " . $filesystem->getSize($pdfSettinsInfo['logo_file_path']));

            fpassthru($fp);
        } catch (\Exception $ex) {
            error_log("Error: Could not read logo image to output: " . $ex->getMessage());            
        }  finally {
            fclose($fp);
        }
        
    }
    
    public static function getLocalLogoPathFromPDFSettingsID($pdfSettingsID) {
        //Get config
        $config = Config::getConfig();
        
        //Get PDF settins information
        $dbh = new DatabaseHandler();
        $pdfSettinsInfoDB = $dbh->getPDFSettinsInfoFromPDFSettingsID($pdfSettingsID);
        $pdfSettinsInfo = $pdfSettinsInfoDB->fetch_assoc();
        if(empty($pdfSettinsInfo['logo_file_path'])){
            return null;
        }
        return $config['logo_host'] . '/' . $pdfSettinsInfo['logo_file_path'];
    }
}
