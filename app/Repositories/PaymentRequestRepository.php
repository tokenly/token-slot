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

    /* depreciated, does not support custom timeouts */
    public function findUnarchivedOlderThanSeconds($seconds_old, $limit=null) {
        $query = $this->prototype_model
            ->where('archived', '0')
            ->where('init_date', '<=', DateProvider::now()->subSeconds($seconds_old));

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }
    
    public function findUnarchived($limit = null)
    {
        $output = array();
        $time = time();
        $default_seconds = env('EXPIRE_OLD_ADDRESSES_SECONDS', 172800);
        
        $query = $this->prototype_model->where('archived', 0);
        if ($limit !== null) {
            $query = $query->limit($limit);
        }
        $query = $query->get();        
        
        if($query){
            foreach($query as $row){
                $payment_time = strtotime($row->init_date);
                $custom_timeout = intval($row->expire_timeout);
                if($custom_timeout <= 0){
                    $custom_timeout = intval($row->slot()->expire_timeout);
                }
                $use_timeout = $default_seconds;
                if($custom_timeout > 0){
                    $use_timeout = $custom_timeout;
                }
                $diff = $time - $payment_time;
                if($diff >= $use_timeout){
                    $output[] = $row;
                }
            }
        }

        return $output;
    }
}
