<?php
namespace App\Http\Controllers;

use RetinaLyze\Chain\ChainEditor;
use Illuminate\Http\Request;

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
        $response = $this->ce->updateChainInfo($post, $file);
        return response()->json($response);
    }
    
}

