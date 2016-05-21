<?php

namespace App\Repositories;

use Tokenly\LaravelApiProvider\Repositories\BaseRepository;
use Exception;

/*
* UserRepository
*/
class UserRepository extends BaseRepository
{

    protected $model_type = 'User';

    protected $uses_uuids = false;

    public function findByUsername($username) {
        return  $this->prototype_model->where('username', $username)->first();
    }

    public function findByEmail($email) {
        return  $this->prototype_model->where('email', $email)->first();
    }

}
