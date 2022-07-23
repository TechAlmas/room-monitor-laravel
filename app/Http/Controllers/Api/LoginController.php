<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Str,Mail;
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
					"email.required" => trans("The username or email field is required"),
					"password.required" => trans("The password field is required"),
				)
			);
			if ($validator->fails()){
				$response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$email	=	$request->input("email");
				$user_details			=	User::where(function ($query) use($email){
														$query->Orwhere("user_name",$email);
														$query->Orwhere("email",$email);
													})
													->select("id","user_name","name","email","phone_number","password","created_at","updated_at","is_active",'user_role','is_verified')
													->first();
				if(!empty($user_details)){
					$AuthAttemptUser = (!empty($user_details)) ? Hash::check($request->input('password'), $user_details->getAuthPassword()) : array();
					if(!empty($AuthAttemptUser)){
						if($user_details->is_active == 0){
							$response["status"]		=	"error";
							$response["msg"]		=	trans("Your account is inactive.Please contact to admin");
							$response["data"]		=	(object)array();
							$response["http_code"]	=	401;
						}elseif($user_details->is_verified == 0){
							$response["status"]		=	"error";
							$response["msg"]		=	trans("Your account is not verified.Please verify first.");
							$response["data"]		=	(object)array();
							$response["http_code"]	=	401;
						}
						else {
							Auth::loginUsingId($user_details->id);
							$user_details			=	User::where("id",$user_details->id)->select("id","user_name","name","email","phone_number","created_at","updated_at","is_active",'user_role','user_image')->first();
							if(!empty($user_details->user_image)){
								$user_details->user_image_url = url('/uploads/users').'/'.$user_details->user_image;
							}else{
								$user_details->user_image_url = url('/assets/images').'/'.'user-img.png';
							}
							$user          			= 	 Auth::user();
							$token        			=	$user->createToken('RoomMonitor app Personal Access Client')->accessToken;
							$response["status"]		=	"success";
							$response["msg"]		=	trans("You are now logged in");
							$response["data"]		=	$user_details;
							$response["token"]		=	$token;
							$response["http_code"]	=	200;
						}
					}else {
						$response["status"]		=	"error";
						$response["msg"]		=	trans("Username/Email or password is incorrect");
						$response["data"]		=	(object)array();
						$response["http_code"]	=	401;
					}
				}else {
					$response["status"]		=	"error";
					$response["msg"]		=	trans("Username/Email or password is incorrect");
					$response["data"]		=	(object)array();
					$response["http_code"]	=	401;
				}
			}
		}else {
			$response["status"]		=	"error";
			$response["msg"]		=	trans("Invalid Request");
			$response["data"]		=	(object)array();
			$response["http_code"]	=	401;
		}
		return json_encode($response);
	}

	public function SignUp(Request $request){
		$formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$validator 					=	Validator::make(
				$request->all(),
				array(
					'name'							=> 'required',
					'user_name'                     => 'required|unique:users',
					'email' 						=> 'required|email|unique:users',
					'phone_number' 					=> 'required|numeric',
					'vehicle_registration_number'   => 'required',
					'user_role'                     => 'required'
					// 'device_type'				=> 'required',
					// 'device_id'					=> 'required',
				),
				array(
					"name.required"      				 	 => trans("The name field is required"),
					"user_name.required"    				 => trans("The user name field is required"),
					"user_name.unique"       				 => trans("The user name has already been taken"),
					"email.required"           				 => trans("The email field is required"),
					"email.email"              				 => trans("The email must be a valid email address"),
					"email.unique"             				 => trans("The email has already been taken"),
					"phone_number.required"    				 => trans("The phone number field is required"),
					"phone_number.numeric"      			 => trans("The phone number must be numeric"),
					"vehicle_registration_number.required"   => trans("The vehicle registration number field is required"),
					"user_role.required"    				 => trans("The user role field is required"),
					
				)
			);
		
			if ($validator->fails()){
				$response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$password     =     Str::random(8);

                $obj 									=  new User;
				$obj->user_role	 						=  $request->input('user_role');
				$obj->user_name 						=  $request->input('user_name');
				$obj->name 								=  $request->input('name');
				$obj->email 							=  $request->input('email');
				$obj->validate_string					=  md5($request->input('email').time().time());
				$obj->phone_number 						=  $request->input('phone_number');
				$obj->vehicle_registration_number 		=  $request->input('vehicle_registration_number');
				$obj->password                          =  Hash::make($password);
				$obj->is_verified	 					=  0;
				$obj->is_active							=  1;
               
				$obj->save();
				$userId  = $obj->id;

                //SEND EMAIL
                if($obj->email){
                    $fromEmail 		= config('settings.from_email');
					$emailData          =  [ 
						'name'       => $obj->name,
						'username'   => $obj->user_name,
						'email'      => $obj->email,
						'password'   => $password,
						'verifyLink' => env('FRONT_END_URL').'/verify_account/'.$obj->validate_string
					];
                    $subject 			=  "Verify account";
					Mail::send('emails.verify_account', $emailData, function($message) use ($obj,$subject,$fromEmail) {
						$message->to($obj->email, $obj->name)->subject($subject);   
						$message->from($fromEmail,env('APP_NAME'));
					 });
					
                }
				$response				=	array();
				$response["status"]		=	"success";
				$response["data"]		=	array("validate_string"=>$obj->validate_string);
				$response["msg"]		=	trans("Verification email has been sent on your registered email.Please verify your email.");
				$response["http_code"]	=	200;
				return response()->json($response,200);
			}
		}
		return json_encode($response);
	}


	public function VerifyAccount(Request $request,$validate_string){
		$userInfo = User::where('validate_string',$validate_string)->first();
		if(empty($userInfo)){
			$response["status"]		=	"error";
			$response["msg"]		=	trans("Invalid validate string");
			$response["data"]		=	(object)array();
			$response["http_code"]	=	401;
			return json_encode($response);
		}
		$response	=	array();
		$user_details			=	User::where("validate_string",$validate_string)
													->where("is_deleted",0) 
													->select("id","name","user_name","email","phone_number","is_active","is_verified","user_role")
													->first();
				if(!empty($user_details)){
					if($user_details->is_active == 0){
						$response["status"]		=	"error";
						$response["msg"]		=	trans("Your account is blocked.Please contact to the admin.");
						$response["data"]		=	(object)array();
						$response["http_code"]	=	401;
						return json_encode($response);
					}else {
						
						User::where("id",$user_details->id)->update(array("is_verified"=>1,"validate_string"=>""));
						$user_details			=	User::where("id",$user_details->id)->select("id","name","email","phone_number","user_role","user_name","vehicle_registration_number")->first();
						
						$response["status"]		=	"success";
						$response["msg"]		=	trans("Your profile has been verified successfully.Please login to access your account.");
						$response["data"]		=	$user_details;
						$response["http_code"]	=	200;
						return json_encode($response);
					}
					
				}else {
					$response["status"]		=	"error";
					$response["msg"]		=	trans("User doesn't exist");
					$response["data"]		=	(object)array();
					$response["http_code"]	=	401;
					return json_encode($response);
				}
		return json_encode($response);
	}

}
