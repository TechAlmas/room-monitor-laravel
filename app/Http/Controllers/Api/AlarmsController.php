<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MasterAlarm;
use Illuminate\Support\Facades\Auth;
use Str,Mail;
use Validator; 
use Helper,Hash,File,Config,DB;
class AlarmsController extends Controller{


	public function __construct(Request $request) {
		parent::__construct();
        $this->request              =   $request;
    }


	public function createReport(Request $request){
		$formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$validator 					=	Validator::make(
				$request->all(),
				array(
					'name'							=> 'required',
					'phone_number' 					=> 'required|numeric',
					'zipcode'                       => 'required',
					'city'				            => 'required',
					'address'					    => 'required',
                    'user_id'                       => 'required'
				),
				array(
					"name.required"      				 	 => trans("The name field is required"),
					"phone_number.required"    				 => trans("The phone number field is required"),
					"phone_number.numeric"      			 => trans("The phone number must be numeric"),
					"zipcode.required"           			 => trans("The zipcode field is required"),
                    "city.required"           				 => trans("The city field is required"),
                    "address.required"           		     => trans("The address field is required"),
                    "user_id.required"           		     => trans("The user id field is required"),
					
				)
			);
		
			if ($validator->fails()){
				$response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$password     =     Str::random(8);
                DB::beginTransaction();
                $obj 									=  new MasterAlarm;
				$obj->user_id	 						=  $request->input('user_id');
				$obj->name 								=  $request->input('name');
				$obj->phone_number 						=  $request->input('phone_number');
                $obj->zipcode 						    =  $request->input('zipcode');
                $obj->city 						        =  $request->input('city');
                $obj->address 						    =  $request->input('address');
                $obj->subject_name 						=  $request->input('subject_name');
                $obj->time_called 						    =  $request->input('time_called');
                $obj->incident_date 						    =  $request->input('incident_date');
                if(!empty($request->input('is_manager_contacted'))){

                    $obj->is_manager_contacted 			    =  1;
                    $obj->manager_details 						    =  $request->input('manager_details');
                }
                if(!empty($request->input('is_intervention_needed'))){

                    $obj->is_intervention_needed 			    =  1;
                    $obj->intervention_time 						    =  $request->input('intervention_time');
                    $obj->intervention_type 						    =  $request->input('intervention_type');
                    $obj->intervention_concierges 						=  $request->input('intervention_concierges');
                }

               
				$obj->save();
				$userId  = $obj->id;
                DB::commit();

               if($userId){

                   $response				=	array();
                   $response["status"]		=	"success";
                   $response["data"]		=	$obj;
                   $response["msg"]		=	trans("Report has been created successfully.");
                   $response["http_code"]	=	200;
                   return response()->json($response,200);
               }else{
                    DB::rollBack();
                    $response				=	array();
                    $response["status"]		=	"error";
                    $response["data"]		=	(object)array();
                    $response["msg"]		=	trans("Something Went Wrong.");
                    $response["http_code"]	=	401;
                    return response()->json($response,401);
               }
				
			}
		}
		return json_encode($response);
	}


	public function displayAlarms(Request $request){
        if(!empty($request->user_id)){
            $getAlarmnsData = MasterAlarm::where('user_id',$request->user_id)->orderBy('created_at','DESC')->get();
            if($getAlarmnsData->isNotEmpty()){
                $response				=	array();
                $response["status"]		=	"success";
                $response["data"]		=	$getAlarmnsData;
                $response["msg"]		=	trans("Data Found Successfully.");
                $response["http_code"]	=	200;
                return response()->json($response,200);
            }else{

                $response				=	array();
                $response["status"]		=	"success";
                $response["data"]		=	(object)array();
                $response["msg"]		=	trans("No Records Found");
                $response["http_code"]	=	200;
                return response()->json($response,200);
            }

        }else{
            $response				=	array();
            $response["status"]		=	"error";
            $response["data"]		=	(object)array();
            $response["msg"]		=	trans("The user id field is required.");
            $response["http_code"]	=	401;
            return response()->json($response,401);

        }

	}
    public function datatableTestData(Request $request){
        $returnData = [
            [
              "Hour"=> "Hour",
              "Types_of_Alarms"=> "Types of Alarms",
              "Room_Name"=> "Room Name",
              "User"=> "User",
              "Alarms"=> "Alarms",
              "Agent_Sent"=> "Agent Sent",
              "Agent_Name"=> "Agent Name",
              "Guest_Reached"=> "Guest Reached",
              "Guest_Name"=> "Guest Name"
            ],
            [
              "Hour"=> "Hour",
              "Types_of_Alarms"=> "Types of Alarms",
              "Room_Name"=> "Room Name",
              "User"=> "User",
              "Alarms"=> "Alarms",
              "Agent_Sent"=> "Agent Sent",
              "Agent_Name"=> "Agent Name",
              "Guest_Reached"=> "Guest Reached",
              "Guest_Name"=> "Guest Name"
            ],
            [
              "Hour"=> "Hour",
              "Types_of_Alarms"=> "Types of Alarms",
              "Room_Name"=> "Room Name",
              "User"=> "User",
              "Alarms"=> "Alarms",
              "Agent_Sent"=> "Agent Sent",
              "Agent_Name"=> "Agent Name",
              "Guest_Reached"=> "Guest Reached",
              "Guest_Name"=> "Guest Name"
            ],
            [
              "Hour"=> "Hour",
              "Types_of_Alarms"=> "Types of Alarms",
              "Room_Name"=> "Room Name",
              "User"=> "User",
              "Alarms"=> "Alarms",
              "Agent_Sent"=> "Agent Sent",
              "Agent_Name"=> "Agent Name",
              "Guest_Reached"=> "Guest Reached",
              "Guest_Name"=> "Guest Name"
            ],
            [
              "Hour"=> "Hour",
              "Types_of_Alarms"=> "Types of Alarms",
              "Room_Name"=> "Room Name",
              "User"=> "User",
              "Alarms"=> "Alarms",
              "Agent_Sent"=> "Agent Sent",
              "Agent_Name"=> "Agent Name",
              "Guest_Reached"=> "Guest Reached",
              "Guest_Name"=> "Guest Name"
            ],
            [
              "Hour"=> "Hour",
              "Types_of_Alarms"=> "Types of Alarms",
              "Room_Name"=> "Room Name",
              "User"=> "User",
              "Alarms"=> "Alarms",
              "Agent_Sent"=> "Agent Sent",
              "Agent_Name"=> "Agent Name",
              "Guest_Reached"=> "Guest Reached",
              "Guest_Name"=> "Guest Name"
            ],
            [
              "Hour"=> "7",
              "Types_of_Alarms"=> "Types of Alarms",
              "Room_Name"=> "Room Name",
              "User"=> "User",
              "Alarms"=> "Alarms",
              "Agent_Sent"=> "Agent Sent",
              "Agent_Name"=> "Agent Name",
              "Guest_Reached"=> "Guest Reached",
              "Guest_Name"=> "Guest Name"
            ],
            [
              "Hour"=> "8",
              "Types_of_Alarms"=> "Types of Alarms",
              "Room_Name"=> "Room Name",
              "User"=> "User",
              "Alarms"=> "Alarms",
              "Agent_Sent"=> "Agent Sent",
              "Agent_Name"=> "Agent Name",
              "Guest_Reached"=> "Guest Reached",
              "Guest_Name"=> "Guest Name"
            ]
        ];
        return $returnData;
          
    }

}
