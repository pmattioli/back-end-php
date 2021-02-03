<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    protected function getNotFoundResponseContent($message = '', $path){
        return [
            'timestamp'=> gmdate('Y-m-d\TH:i:s.vP'),
            'status'=> 404,
            'error'=> 'Not Found',
            'message'=> $message,
            'path'=> $path
        ];
    }
}
