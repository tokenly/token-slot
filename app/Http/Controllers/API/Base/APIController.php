<?php
namespace App\Http\Controllers\API\Base;
use App\Http\Controllers\Controller;
use Exception;
class APIController extends Controller {
    public function __construct() {
        $this->addMiddleware();
    }
    public function addMiddleware() {
        // catch all errors and return a JSON response
		$this->middleware('api.catchErrors');
		
		//check for client API key
		$this->middleware('auth.client');
        
    }
}
