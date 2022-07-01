<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MasterAlarm;
use App\Models\ReportFile;
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
                    $obj->status 			    =  'ongoing';
                  }else{
                  $obj->status 			    =  'pending';
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
                    return response()->json($response,200);
               }
				
			}
		}
		return json_encode($response);
	}


	public function displayAlarms(Request $request){
    if(!empty($request->user_id)){
          $getAlarmnsData = MasterAlarm::query();
          $getUserData = User::where('id',$request->user_id)->first();
          
          if($getUserData->user_role == 'agent'){
            $getAlarmnsData->where('intervention_concierges',$request->user_id);
            if(!empty($request->type) &&  $request->type == 'submitted'){
              $getAlarmnsData->whereIn('status',['submitted','approved','rejected']);
            }else{
              $getAlarmnsData->where('status','ongoing');
            }
          }else if($getUserData->user_role == 'user'){
            $getAlarmnsData->where('user_id',$request->user_id);
            if(!empty($request->type) &&  $request->type == 'submitted'){
              $getAlarmnsData->whereIn('status',['submitted','approved','rejected']);
            }else{
              $getAlarmnsData->whereIn('status',['ongoing','pending']);
            }
          }else{
            if(!empty($request->type) &&  $request->type == 'submitted'){
              $getAlarmnsData->whereIn('status',['submitted','approved','rejected']);
            }else{
              $getAlarmnsData->whereIn('status',['ongoing','pending']);
            }
          }
          $getAlarmnsData = $getAlarmnsData->orderBy('updated_at', 'desc')->get();
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
                $response["data"]		=	array();
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
            return response()->json($response,200);

        }

	}

  public function getReportDetails(Request $request,$id=0){
    if(!empty($id)){
          $getReportDetails = MasterAlarm::where('id',$id)->first();
            if(!empty($getReportDetails)){
              $getReportDetails->created_date = date('d-m-Y',strtotime($getReportDetails->created_at));
              $getReportDetails->created_time = date('H:i',strtotime($getReportDetails->created_at));
              $getuploadedFiles = ReportFile::where('report_id',$getReportDetails->id)->get();
              if($getuploadedFiles->isNotEmpty()){
                foreach($getuploadedFiles as $uploadedFileVal){
                  $uploadedFileVal->file_url = url('/uploads/reports').'/'.$uploadedFileVal->file;
                }
              }
                $response				=	array();
                $response["status"]		=	"success";
                $response["data"]		=	$getReportDetails;
                $response['uploaded_files'] = $getuploadedFiles;
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
            $response["msg"]		=	trans("The report id field is required.");
            $response["http_code"]	=	401;
            return response()->json($response,200);

        }

	}

  public function startReport(Request $request){
		$formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$validator 					=	Validator::make(
				$request->all(),
				array(
					'name'							=> 'required',
					'zipcode'                       => 'required',
					'city'				            => 'required',
					'address'					    => 'required',
          'time_called'                       => 'required',
          'incident_date'       => 'required',
          'incident_details'    => 'required'

				),
				array(
					"name.required"      				 	 => trans("The name field is required"),
					"zipcode.required"           			 => trans("The zipcode field is required"),
          "city.required"           				 => trans("The city field is required"),
          "address.required"           		     => trans("The address field is required"),
          "time_called.required"           		     => trans("The time field is required"),
          "incident_date.required"           		     => trans("The date field is required"),
          "incident_details.required"           		     => trans("The details field is required")
					
				)
			);
		
			if ($validator->fails()){
				$response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$getReportData = MasterAlarm::where('id',$request->id)->first();
        if(empty($getReportData)){
          $response				=	array();
          $response["status"]		=	"error";
          $response["data"]		=	(object)array();
          $response["msg"]		=	trans("Report does not exists.");
          $response["http_code"]	=	401;
          return response()->json($response,200);
        }
                DB::beginTransaction();
                $obj 									=  MasterAlarm::find($getReportData->id);
				        $obj->name 								=  $request->input('name');
                $obj->zipcode 						    =  $request->input('zipcode');
                $obj->city 						        =  $request->input('city');
                $obj->address 						    =  $request->input('address');
                $obj->time_called 						    =  $request->input('time_called');
                $obj->incident_date 						    =  $request->input('incident_date');
                $obj->incident_details 						    =  $request->input('incident_details');
                $obj->status                  = 'submitted';

               
				$obj->save();
				$userId  = $obj->id;
                

               if($userId){
                   DB::commit();
                   $response				=	array();
                   $response["status"]		=	"success";
                   $response["data"]		=	$obj;
                   $response["msg"]		=	trans("Report details has been received.");
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

  public function approveReport(Request $request,$reportId = 0){
    if(!empty($reportId)){
      $getReportDetails = MasterAlarm::where('id',$reportId)->first();
      if(!empty($getReportDetails)){
        MasterAlarm::where('id',$reportId)->update(['status'=>'approved']);
        $response				=	array();
        $response["status"]		=	"success";
        $response["data"]		=	(object)array();
        $response["msg"]		=	trans("Report approved successfully.");
        $response["http_code"]	=	200;
        return response()->json($response,200);
      }else{
        $response				=	array();
        $response["status"]		=	"success";
        $response["data"]		=	(object)array();
        $response["msg"]		=	trans("Report does not exists.");
        $response["http_code"]	=	200;
        return response()->json($response,200);
      }
    }else{
      $response				=	array();
      $response["status"]		=	"error";
      $response["data"]		=	(object)array();
      $response["msg"]		=	trans("The report id field is required.");
      $response["http_code"]	=	401;
      return response()->json($response,200);
    }
  }
  public function rejectReport(Request $request,$reportId = 0){
    if(!empty($reportId)){
      $getReportDetails = MasterAlarm::where('id',$reportId)->first();
      if(!empty($getReportDetails)){
        if(!empty($request->rejection_details)){

          MasterAlarm::where('id',$reportId)->update(['status'=>'rejected','rejection_details' => $request->rejection_details]);
          $response				=	array();
          $response["status"]		=	"success";
          $response["data"]		=	(object)array();
          $response["msg"]		=	trans("Report rejected successfully.");
          $response["http_code"]	=	200;
          return response()->json($response,200);
        }else{
          $response				=	array();
          $response["status"]		=	"error";
          $response["data"]		=	(object)array();
          $response["msg"]		=	trans("The rejection details field is required.");
          $response["http_code"]	=	401;
          return response()->json($response,200);
        }
      }else{
        $response				=	array();
        $response["status"]		=	"success";
        $response["data"]		=	(object)array();
        $response["msg"]		=	trans("Report does not exists.");
        $response["http_code"]	=	200;
        return response()->json($response,200);
      }
    }else{
      $response				=	array();
      $response["status"]		=	"error";
      $response["data"]		=	(object)array();
      $response["msg"]		=	trans("The report id field is required.");
      $response["http_code"]	=	401;
      return response()->json($response,200);
    }
  }

  public function updateReportFiles(Request $request,$id = 0){
    if(!empty($id)){
      if(!empty($request->report_files)){
        $getReportDetails = MasterAlarm::where('id',$id)->first();
        if(!empty($getReportDetails)){
          foreach($request->report_files as $fileKey => $fileVal){
            $obj                  =    new ReportFile;
            $extension 					=	 $fileVal->getClientOriginalExtension();
            $original 					=	 $fileVal->getClientOriginalName();
            $fileName					=	time().'-report-video'.$fileKey.'.'.$extension;
            $folderName     			= 	strtoupper(date('M'). date('Y'))."/";
            $folderPath					=	public_path('/uploads/reports/').$folderName;
            if(!File::exists($folderPath)) {
              File::makeDirectory($folderPath, $mode = 0777,true);
            }
            if($fileVal->move($folderPath, $fileName)){
              $obj->report_id   = $getReportDetails->id;
              $obj->file				= $folderName.$fileName;
              $obj->file_name		= $original;
              $obj->save();
            }else{
              $response				=	array();
              $response["status"]		=	"error";
              $response["data"]		=	(object)array();
              $response["msg"]		=	trans("Something went wrong while uploading the files.");
              $response["http_code"]	=	401;
              return response()->json($response,200);
            }
          }

          $getuploadedFiles = ReportFile::where('report_id',$getReportDetails->id)->get();
          if($getuploadedFiles->isNotEmpty()){
            foreach($getuploadedFiles as $uploadedFileVal){
              $uploadedFileVal->file_url = url('/uploads/reports').'/'.$uploadedFileVal->file;
            }
            $response				=	array();
            $response["status"]		=	"success";
            $response["data"]		=	$getuploadedFiles;
            $response["msg"]		=	trans("Files uploaded successfully.");
            $response["http_code"]	=	200;
            return response()->json($response,200);

          }
        }else{

            $response				=	array();
            $response["status"]		=	"success";
            $response["data"]		=	(object)array();
            $response["msg"]		=	trans("Report does not exists.");
            $response["http_code"]	=	200;
            return response()->json($response,200);
        }
       
      }else{
        $response				=	array();
        $response["status"]		=	"error";
        $response["data"]		=	(object)array();
        $response["msg"]		=	trans("The files field is required.");
        $response["http_code"]	=	401;
        return response()->json($response,200);
      }
    }else{
      $response				=	array();
      $response["status"]		=	"error";
      $response["data"]		=	(object)array();
      $response["msg"]		=	trans("The report id field is required.");
      $response["http_code"]	=	401;
      return response()->json($response,200);
    }
    
  }

  public function removeUploadedFile(Request $request,$fileId = 0){
    if(!empty($fileId)){
      $checkIfFileExists = ReportFile::where('id',$fileId)->first();
      if($checkIfFileExists){
        ReportFile::where('id',$fileId)->delete();
        $filePath					=	public_path('/uploads/reports/').$checkIfFileExists->file;

        //Remove uploaded file from directory as well
        if(\File::exists($filePath)){

          \File::delete($filePath);
      
        }

        $getuploadedFiles = ReportFile::where('report_id',$checkIfFileExists->report_id)->get();
        if($getuploadedFiles->isNotEmpty()){
          foreach($getuploadedFiles as $uploadedFileVal){
            $uploadedFileVal->file_url = url('/uploads/reports').'/'.$uploadedFileVal->file;
          }
        }
        $response				=	array();
        $response["status"]		=	"success";
        $response["data"]		=	$getuploadedFiles;
        $response["msg"]		=	trans("File removed successfully.");
        $response["http_code"]	=	200;
        return response()->json($response,200);
      }else{
        $response				=	array();
        $response["status"]		=	"error";
        $response["data"]		=	(object)array();
        $response["msg"]		=	trans("File does not exists.");
        $response["http_code"]	=	401;
        return response()->json($response,200);
      }
    }else{
      $response				=	array();
      $response["status"]		=	"error";
      $response["data"]		=	(object)array();
      $response["msg"]		=	trans("The file id field is required.");
      $response["http_code"]	=	401;
      return response()->json($response,200);
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

    public function dropdownManagers(){
      $agentsList = User::where('user_role','agent')->where('is_active',1)->where('is_deleted',0)->where('is_verified',1)->select('id','name as text')->get();

      $response				=	array();
      $response["status"]		=	"success";
      $response["data"]		=	(object)array();
      $response['agents_list'] = $agentsList;
      $response["msg"]		=	trans("Data Found");
      $response["http_code"]	=	200;
      return response()->json($response,200);
    }

}
