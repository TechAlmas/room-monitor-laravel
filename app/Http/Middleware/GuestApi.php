<?php
namespace App\Http\Middleware;

use Closure;
Use Auth;
Use Redirect;
use Response;
use DB;
use Config;
use Input;
use Illuminate\Http\Request;
use App;
use App\Model\MobileApiLog;

class GuestApi
{
    /**
    * Run the request filter.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  \Closure  $next
    * @return mixed
    */
    public function handle($request, Closure $next){
      header_remove('Access-Control-Allow-Credentials');
      header_remove('Access-Control-Allow-Origin');
      if(!empty($request->header('Accept-Language'))){
        App::setLocale($request->header('Accept-Language'));
      }else{
        App::setLocale("en");
      }

      if($request->path() == 'api/upload-customers-csv' || $request->path() == 'api/upload-rooms-csv'){
        if(!empty($request->verify_string)){
          if($request->verify_string != config('settings.verify_string') ){
            $response				=	array();
            $response["status"]		=	"error";
            $response["data"]		=	(object)array();
            $response["msg"]		=	trans("You are not authorized to access this api.");
            $response["http_code"]	=	401;
            return response()->json($response,200);
          }
        }else{
          $response				=	array();
          $response["status"]		=	"error";
          $response["data"]		=	(object)array();
          $response["msg"]		=	trans("You are not authorized to access this api.");
          $response["http_code"]	=	401;
          return response()->json($response,200);
        }
      }
		
		return $next($request);
	}
}
