<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Chain;

use RetinaLyze\Database\DatabaseHandler;
use RetinaLyze\Utils\Config;
use RetinaLyze\Files\FilesystemFactory;

/**
 * Description of ChainEditor
 *
 * @author mom
 */
class ChainEditor {
    public static function addChain($post) {
        if(!empty($post["chaintitle"])){
            $dbh = new DatabaseHandler();
            try{
                $dbh->createChain($post["chaintitle"]);
            } catch (\Exception $ex) {
                error_log("Error: Could not create chain: " . $ex->getMessage());
                return false;
            }
            return true;
        }else{
            return false;
        }
    }
    
    public static function getChainInfo($chainID){
        $dbh = new DatabaseHandler();
        $chainInfoDB = $dbh->getChainAndPDFInfo($chainID);
        return $chainInfoDB->fetch_assoc();
    }
    
    public static function updateChainInfo($post, $file){
        //Get config
        $config = Config::getConfig();
        
        //Chain info
        $chainID = $post["chainID"];
        $chainName = $post["chainname"];
        $adminAccess = empty($post["allow_admin_access_to_userdata"]) ? 0 : $post["allow_admin_access_to_userdata"];
        $chainUsersAccess = empty($post["allow_chainusers_access_to_otheruserdata"]) ? 0 : $post["allow_chainusers_access_to_otheruserdata"];
        $separateLists = empty($post["seperate_not_and_analyzed"]) ? 0 : $post["seperate_not_and_analyzed"];
        $showSavePDFOption = empty($post["show_save_pdf_option"]) ? 0 : $post["show_save_pdf_option"];        
        $hidePDF = empty($post["hide_pdf"]) ? 0 : $post["hide_pdf"];
        $hideDownloadImageWithOverlay = empty($post["hide_download_image_with_overlay"]) ? 0 : $post["hide_download_image_with_overlay"];
        
        //PDF settings info
        $logo = empty($post["showlogo"]) ? 0 : $post["showlogo"];
        $showShopName = empty($post["showcompanyname"]) ? 0 : $post["showcompanyname"];
        $showShopAddress = empty($post["showshopaddress"]) ? 0 : $post["showshopaddress"];
        $showShopPhonenumber = empty($post["showphonenumber"]) ? 0 : $post["showphonenumber"];
        $showClientName = empty($post["showclientname"]) ? 0 : $post["showclientname"];
        $showClientID = empty($post["showclientid"]) ? 0 : $post["showclientid"];
        
        $dbh = new DatabaseHandler();
        try{
            $dbh->updateChainInfo($chainID, $chainName, $adminAccess, $chainUsersAccess, $separateLists, $showSavePDFOption, $hidePDF, $hideDownloadImageWithOverlay);
            $pdfID = $dbh->updatePDFSettingsOnChain($chainID, $logo, $showShopName, $showShopPhonenumber, $showClientName, $showClientID, $showShopAddress);
        } catch (\Exception $ex) {
            error_log("Error: Could not update chaininfo: " . $ex->getMessage());
            return false;
        }
        
        //PDF Logo upload handling
        if(!empty($file["logo"]["name"])){
            
            //Get extention
            $path_parts = pathinfo($_FILES['logo']["name"]);
            $extension = $path_parts['extension'];
            try{
                $filesystem = FilesystemFactory::create($config['logo_files_path'], $config['logo_filesystem']);
                $stream = fopen($_FILES['logo']['tmp_name'], 'r+');
                $path = $pdfID . "." . $extension;
                $filesystem->putStream($path, $stream);
                //Update db with filename
                $dbh->updateLogoFilePathPDFSettings($pdfID, $path);             
            } catch (\Exception $ex) {
                error_log("Error: Could not upload logo: " . $ex->getMessage());
                return false;
            }  finally {
                fclose($stream);
            }
        }
        
        
        return true;
    }
    
    public static function getLogoPath($chainID) {
        //Get config
        $config = Config::getConfig();
        
        //Get PDF settins information
        $dbh = new DatabaseHandler();
        $pdfSettinsInfoDB = $dbh->getPDFSettinsInfo($chainID);
        $pdfSettinsInfo = $pdfSettinsInfoDB->fetch_assoc();
        if(empty($pdfSettinsInfo['logo_file_path'])){
            return null;
        }
        return $config['logo_host'] . '/' . $pdfSettinsInfo['logo_file_path'];
    }
}
