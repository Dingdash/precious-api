<?php

namespace Chatter\Models;

class User extends \Illuminate\Database\Eloquent\Model
{
    // put authentication method
    public function authenticate($apikey){
        $user = User::where('apikey','=',$apikey)->take(1)->get();
        $this->details = $user[0];
        //is this api key valid for existing user
        return ($user[0]->exists) ? true : false;
    }

}