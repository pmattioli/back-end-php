<?php

namespace App\Models;

class User extends Model
{
    
    protected $table = 'ret_users';
    
    protected $primaryKey = 'userID';
    
    protected $columns = [
        'id' => 'userID',
        'username' => 'username',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
}
