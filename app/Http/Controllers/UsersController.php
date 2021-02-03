<?php

namespace App\Http\Controllers;

use App\Models\User;
use RetinaLyze\Users\UserEditor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
        if (sizeof($users) === 0) {
            return response()->json($this->getNotFoundResponseContent('', '/users/find/' . $term), 404);
        }
        return response()->json(['usersFound' =>  $users]);
    }
    
    public function getUsersFromChain($chainID)
    {
        $users = $this->ue->getUsersFromChain($chainID);
        if (sizeof($users) === 0) {
            return response()->json($this->getNotFoundResponseContent('', '/users/chain/' . $chainID), 404);
        }
        return response()->json(['usersFound' =>  $users]);
    }
    
    public function getDisabledUsersFromChain($chainID)
    {
        $users = $this->ue->getDisabledUsersFromChain($chainID);
        if (sizeof($users) === 0) {
            return response()->json($this->getNotFoundResponseContent('', '/users/chain/' . $chainID . '/disabled'), 404);
        }
        return response()->json(['usersFound' =>  $users]);
    }
    
    public function getUsersFromCountryOutsideChain($country)
    {
        $users = $this->ue->getUsersFromCountryOutsideChain($country);
        if (sizeof($users) === 0) {
            return response()->json($this->getNotFoundResponseContent('', '/users/country/' . $country), 404);
        }
        return response()->json(['usersFound' =>  $users]);
    }
    
    public function getDisabledUsersFromCountryOutsideChain($country)
    {
        $users = $this->ue->getDiabledUsersFromCountryOutsideChain($country);
        if (sizeof($users) === 0) {
            return response()->json($this->getNotFoundResponseContent('', '/users/country/' . $country . '/disabled'), 404);
        }
        return response()->json(['usersFound' =>  $users]);
    }
    
    public function changePassword(Request $request) {
        $userID = $request->input('userID');
        $newPassword = $request->input('newPassword');
        $oldPassword = $request->input('oldPassword');
        $response = $this->ue->changePassword($userID, $newPassword, $oldPassword);
        return response()->json($response);
    }
    
    public function getUserInfo(Request $request) {
        $username = $request->input('username');
        $userInfo = $this->ue->getUserInfo($username);
        return response()->json($userInfo);
    }
    
    public function saveUserDetails(Request $request) {
        $postData = $request->input('postData');
        $success = $this->ue->saveUserDetails($postData);
        return response()->json($success);
    }
    
    public function showAllUsers()
    {
        return response()->json(User::all());
    }

}
