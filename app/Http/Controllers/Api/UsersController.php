<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use App\Models\Alarm;
use Illuminate\Support\Facades\Auth;
use Str,Mail;
use Validator; 
use Helper,Hash,File,Config,DB,PDF;
class UsersController extends Controller{


	public function __construct(Request $request) {
		parent::__construct();
        $this->request              =   $request;
    }


    public function addUser(Request $request){
      $getLoggedInUserId = Auth::guard('api')->user()->id;
      $formData	=	$request->all();
      $response	=	array();
      if(!empty($formData)){
        $validator 					=	Validator::make(
          $request->all(),
          array(
            'name'							=> 'required',
            'email'                       => 'required|unique:users',
            'user_name'				            => 'required|unique:users',
            'phone_number'					    => 'required',
            'user_role'                       => 'required',
            'vehicle_registration_number'       => ['required','unique:users','regex:/^(?!ss|ww|.[iou]|[iou].)[a-z]{2}[-\s]?\d{3}[-\s]?(?!ss|ww|.[iou]|[iou].)[a-z]{2}$/i'],
          )
        );
      
        if ($validator->fails()){
          $response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
        }else{

          $password     =     Str::random(8);
          DB::beginTransaction();
          $obj 									=  new User;
          $obj->name 								=  $request->input('name');
          $obj->email 								=  $request->input('email');
          $obj->user_name 								=  $request->input('user_name');
          $obj->phone_number 								=  $request->input('phone_number');
          $obj->user_role 								=  $request->input('user_role');
          $obj->vehicle_registration_number 								=  $request->input('vehicle_registration_number');
          $obj->password                          =  Hash::make($password);
          $obj->is_active                  =   1;
          $obj->is_verified                =   1;
           $obj->validate_string                =   '';
         
           if(!empty($request->user_image)){

            $extension 					=	 $request->user_image->getClientOriginalExtension();
            $original 					=	 $request->user_image->getClientOriginalName();
            $fileName					=	time().'-user-image.'.$extension;
            $folderName     			= 	strtoupper(date('M'). date('Y'))."/";
            $folderPath					=	public_path('/uploads/users/').$folderName;
            if(!File::exists($folderPath)) {
              File::makeDirectory($folderPath, $mode = 0777,true);
            }
            if($request->user_image->move($folderPath, $fileName)){
           
              $obj->user_image     =  $folderName.$fileName;
             
            }else{
              $response				=	array();
              $response["status"]		=	"error";
              $response["data"]		=	(object)array();
              $response["msg"]		=	trans("Something went wrong while uploading the image.");
              $response["http_code"]	=	401;
              return response()->json($response,200);
            }
          }

          $obj->validate_string					=  md5($request->input('email').time().time());
          $obj->save();
          $userId  = $obj->id;
                  

          if($userId){

            //SEND EMAIL
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

              DB::commit();
              
              $getUsersData = User::orderBy('updated_at','desc')->get();
            
              $response				=	array();
              $response["status"]		=	"success";
              $response["data"]		=	$getUsersData;
         
              $response["msg"]		=	trans("User added successfully and Verification email has been sent on his registered email address");
              
              $response["http_code"]	=	200;
              return response()->json($response,200);
          }else{
                DB::rollBack();
                DB::commit();
                $response				=	array();
                $response["status"]		=	"error";
                $response["data"]		=	(object)array();
                $response["msg"]		=	trans("Something Went Wrong.");
                $response["http_code"]	=	401;
                return response()->json($response,200);
          }
          
        }
      }
      return json_encode($response);
    }

    public function updateUser(Request $request){
      if(!empty($request->id)){
          $getLoggedInUserId = Auth::guard('api')->user()->id;
          $formData	=	$request->all();
          $response	=	array();
          if(!empty($formData)){
            $validator 					=	Validator::make(
              $request->all(),
              array(
                'name'							=> 'required',
                'email'                       => 'required|unique:users,email,'.$request->id,
                'user_name'				            => 'required|unique:users,user_name,'.$request->id,
                'phone_number'					    => 'required',
                'user_role'                       => 'required',
                'vehicle_registration_number'       => ['required','unique:users,vehicle_registration_number,'.$request->id,'regex:/^(?!ss|ww|.[iou]|[iou].)[a-z]{2}[-\s]?\d{3}[-\s]?(?!ss|ww|.[iou]|[iou].)[a-z]{2}$/i']
                
              )
            );
          
            if ($validator->fails()){
              $response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
            }else{
              
              DB::beginTransaction();
              $obj 									=  User::find($request->id);
              $obj->name 								=  $request->input('name');
              $obj->email 								=  $request->input('email');
              $obj->user_name 								=  $request->input('user_name');
              $obj->phone_number 								=  $request->input('phone_number');
              $obj->user_role 								=  $request->input('user_role');
              $obj->vehicle_registration_number 								=  $request->input('vehicle_registration_number');

              
              if(!empty($request->user_image)){

                $extension 					=	 $request->user_image->getClientOriginalExtension();
                $original 					=	 $request->user_image->getClientOriginalName();
                $fileName					=	time().'-user-image.'.$extension;
                $folderName     			= 	strtoupper(date('M'). date('Y'))."/";
                $folderPath					=	public_path('/uploads/users/').$folderName;
                if(!File::exists($folderPath)) {
                  File::makeDirectory($folderPath, $mode = 0777,true);
                }
                if($request->user_image->move($folderPath, $fileName)){
                  $checkIfUserImageAlreadyExists = User::where('id',$request->id)->value('user_image');
                  if(!empty($checkIfUserImageAlreadyExists)){
                    $filePath					=	public_path('/uploads/users/').$checkIfUserImageAlreadyExists;
      
                    //Remove uploaded image from directory as well
                    if(\File::exists($filePath)){
            
                      \File::delete($filePath);
                  
                    }
            
                  }
                  $obj->user_image     =  $folderName.$fileName;
                  
                
                }else{
                  $response				=	array();
                  $response["status"]		=	"error";
                  $response["data"]		=	(object)array();
                  $response["msg"]		=	trans("Something went wrong while uploading the image.");
                  $response["http_code"]	=	401;
                  return response()->json($response,200);
                }
              }
              
              $obj->save();
              $userId  = $obj->id;
                      

              if($userId){
                  DB::commit();
                  
                  $getUsersData = User::orderBy('updated_at','desc')->get();
                  $response				=	array();
                  $response["status"]		=	"success";
                  $response["data"]		=	$getUsersData;
                  $response["msg"]		=	trans("User updated successfully.");
                  $response["http_code"]	=	200;
                  return response()->json($response,200);
              }else{
                    DB::rollBack();
                    DB::commit();
                    $response				=	array();
                    $response["status"]		=	"error";
                    $response["data"]		=	(object)array();
                    $response["msg"]		=	trans("Something Went Wrong.");
                    $response["http_code"]	=	401;
                    return response()->json($response,200);
              }
              
            }
          }
          return json_encode($response);
        
      }else{
        $response				=	array();
        $response["status"]		=	"error";
        $response["data"]		=	(object)array();
        $response["msg"]		=	trans("The alarm id field is required.");
        $response["http_code"]	=	401;
        return response()->json($response,200);

      }
    }

    public function fetchUserDetail(Request $request){
      if(!empty($request->id)){
        $getUserDetails = User::where('id',$request->id)->first();
          if(!empty($getUserDetails)){
            $getUserDetails->created_date = date('d-m-Y',strtotime($getUserDetails->created_at));
            $getUserDetails->created_time = date('H:i',strtotime($getUserDetails->created_at));
            if(!empty($getUserDetails->user_image)){
              $getUserDetails->user_image_url = url('/uploads/users').'/'.$getUserDetails->user_image;
            }

              $response				=	array();
              $response["status"]		=	"success";
              $response["data"]		=	$getUserDetails;
              $response["msg"]		=	trans("Data Found Successfully.");
              $response["http_code"]	=	200;
              return response()->json($response,200);
          }else{

              $response				=	array();
              $response["status"]		=	"success";
              $response["data"]		=	(object)array();
              $response["msg"]		=	trans("No Record Found");
              $response["http_code"]	=	200;
              return response()->json($response,200);
          }

      }else{
          $response				=	array();
          $response["status"]		=	"error";
          $response["data"]		=	(object)array();
          $response["msg"]		=	trans("The user id field is required.");
          $response["http_code"]	=	401;
          return response()->json($response,200);

      }
    }

    public function fetchUsers(Request $request){
      $getLoggedInUserId = Auth::guard('api')->user()->id;

      $getUsersData = User::query();
    
      $getUsersData = $getUsersData->orderBy('users.updated_at','desc')->get();
     
           
      if($getUsersData->isNotEmpty()){
        foreach($getUsersData as $userVal){
          if(!empty($userVal->user_image)){
            $userVal->user_image_url = url('/uploads/users').'/'.$userVal->user_image;
          }else{
            $userVal->user_image_url = url('/assets/images').'/'.'user-img.png';
          }
        }
          $response				=	array();
          $response["status"]		=	"success";
          $response["data"]		=	$getUsersData;
          $response["msg"]		=	trans("Data Found Successfully.");
          $response["http_code"]	=	200;
          return response()->json($response,200);
      }else{

          $response				=	array();
          $response["status"]		=	"success";
          $response["data"]		=	array();
          $response["msg"]		=	trans("No Records Found");
          $response["http_code"]	=	200;
          return response()->json($response,200);
      }
    }
}
