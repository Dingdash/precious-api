<?php

namespace Chatter\Models;

class Userdata extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'users';
    public function output()
    {
        $output = [];
        $output['id'] = $this->id;
        $output['username'] = $this->username;
        $output['first_name'] = $this->first_name;
        $output['last_name'] = $this->last_name;
        $output['user_uri'] = '/users/'.$this->id;
        $output['email'] = $this->email;
        $output['apikey'] = $this->apikey;
        $output['city'] = $this->city;
        $output['address']=$this->address;
        $output['post_code'] = $this->post_code;
        $output['telp'] = $this->telp;
        $output['gender'] = $this->gender;
        $output['age'] = $this->age;
        
        return $output;
    }
    public function generateAPIKey()
    {
        $token = openssl_random_pseudo_bytes(16);
        
        //Convert the binary data into hexadecimal representation.
        $token = bin2hex($token);
        
        //Print it out for example purposes.
        return $token;
    }
}
