<?php
namespace App\Http\Controllers;

use RetinaLyze\Chain\ChainEditor;
use Illuminate\Http\Request;
use RetinaLyze\Database\DatabaseHandler;

class ChainController extends Controller
{
    
    /**
     * Create a new controller instance.
     *
     * @param  ChainEditor $ce
     * @return void
     */
    public function __construct(ChainEditor $ce)
    {
        $this->ce = $ce;
    }
    
    public function updateChainInfo(Request $request) {
        $post = $request->input('post');
        $file = $request->input('file');
        
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
            throw $ex;
        }
        
        return response()->json($pdfID);
    }
    
    public function updateLogoFilePathPDFSettings(Request $request) {
        $pdfID = $request->input('pdfID');   
        $path = $request->input('path');
        
        $dbh = new DatabaseHandler();
        $dbh->updateLogoFilePathPDFSettings($pdfID, $path);   
        
        return response('', 201);
    }
    
}

