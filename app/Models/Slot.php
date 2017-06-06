<?php

namespace App\Models;

use Tokenly\LaravelApiProvider\Model\APIModel;
use Exception, User;

class Slot extends APIModel {

    protected $api_attributes = ['id',];
    
    
    public function user()
    {
        return User::find($this->userId);
    }
}
