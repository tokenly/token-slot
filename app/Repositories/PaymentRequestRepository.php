<?php

namespace App\Repositories;

use App\Providers\Date\Facade\DateProvider;
use Exception;
use Tokenly\LaravelApiProvider\Repositories\BaseRepository;

/*
* PaymentRequestRepository
*/
class PaymentRequestRepository extends BaseRepository
{

    protected $model_type = '\Payment';

    public function findUnarchivedOlderThanSeconds($seconds_old, $limit=null) {
        $query = $this->prototype_model
            ->where('archived', '0')
            ->where('init_date', '<=', DateProvider::now()->subSeconds($seconds_old));

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
