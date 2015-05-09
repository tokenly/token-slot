<?php 
namespace App\Http\Middleware;
use Input, Closure, Response, User;

class CheckClientKey {

    public function handle($request, Closure $next)
    {
        $input = Input::all();
        $output = array();
		if(!isset($input['key'])){
			$output['error'] = 'API key required';
			return Response::json($output, 403);
		}
		$findUser = User::where('api_key', '=', $input['key'])->first();
		if(!$findUser){
			$output['error'] = 'Invalid API key';
			return Response::json($output, 400);
		}
		if($findUser->activated == 0){
			$output['error'] = 'Account not activated';
			return Response::json($output, 403);
		}
		User::$api_user = $findUser;

        return $next($request);
    }
}
