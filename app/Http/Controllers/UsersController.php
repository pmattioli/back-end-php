<?php

namespace App\Http\Controllers;

use App\Models\User;
use RetinaLyze\Users\UserEditor;

class UsersController extends Controller
{
    
    /**
     * The user editor instance.
     */
    protected $ue;
    
    /**
     * Create a new controller instance.
     *
     * @param  UserEditor $ue
     * @return void
     */
    public function __construct(UserEditor $ue)
    {
        $this->ue = $ue;
    }

    public function findUsers($term)
    {   
        $users = $this->ue->findUsers($term);
        return response()->json(['usersFound' =>  $users]);
    }
    
    public function showAllUsers()
    {
        return response()->json(User::all());
    }

}
