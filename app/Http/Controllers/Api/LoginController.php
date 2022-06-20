<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator; 
use Helper,Hash,File,Config,DB;
class LoginController extends Controller
{ 

	public function __construct(Request $request) {
		parent::__construct();
        $this->request              =   $request;
    }


	public function login(Request $request){
		// $password = Hash::make("System@123");
		// print_r($password); die;
		$formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$validator = Validator::make(
				$request->all(),
				array(
					'email' 	    => 'required',
					'password' 			=> 'required',
				),
				array(
					"email.required" => trans("The email field is required"),
					"password.required" => trans("The password field is required"),
				)
			);
			if ($validator->fails()){
				$response				=	$validator->errors()->getMessages();
			}else{
				$email	=	$request->input("email");
				$user_details			=	User::where(function ($query) use($email){
														$query->Orwhere("user_name",$email);
														$query->Orwhere("email",$email);
													})
													->select("id","user_name","name","email","phone_number","password","created_at","is_active")
													->first();
				if(!empty($user_details)){
					$AuthAttemptUser = (!empty($user_details)) ? Hash::check($request->input('password'), $user_details->getAuthPassword()) : array();
					if(!empty($AuthAttemptUser)){
						if($user_details->is_active == 0){
							$response["status"]		=	"error";
							$response["msg"]		=	trans("Your account is inactive Please contact to admin");
							$response["data"]		=	(object)array();
						}
						else {
							Auth::loginUsingId($user_details->id);
							$user_details			=	User::where("id",$user_details->id)->select("id","user_name","name","email","phone_number","created_at")->first();

							$user          			= 	 Auth::user();
							$token        			=	$user->createToken('RoomMonitor app Personal Access Client')->accessToken;
							$response["status"]		=	"success";
							$response["msg"]		=	trans("You are now logged in");
							$response["data"]		=	$user_details;
							$response["token"]		=	$token;
						}
					}else {
						$response["status"]		=	"error";
						$response["msg"]		=	trans("Email or password is incorrect");
						$response["data"]		=	(object)array();
					}
				}else {
					$response["status"]		=	"error";
					$response["msg"]		=	trans("Email or password is incorrect");
					$response["data"]		=	(object)array();
				}
			}
		}else {
			$response["status"]		=	"error";
			$response["msg"]		=	trans("Invalid Request");
			$response["data"]		=	(object)array();
		}
		return json_encode($response);
	}

}
