<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MasterAlarm;
use App\Models\AlarmImport;
use App\Models\ReportFile;
use App\Models\Customer;
use App\Models\Alarm;
use Illuminate\Support\Facades\Auth;
use Str,Mail;
use Validator; 
use Helper,Hash,File,Config,DB,PDF;
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
          
          if($getUserData->user_role == 'night_agents'){
            $getAlarmnsData->where('intervention_concierges',$request->user_id);
            if(!empty($request->type) &&  $request->type == 'submitted'){
              $getAlarmnsData->whereIn('status',['submitted','approved','rejected']);
            }else{
              $getAlarmnsData->where('status','ongoing');
            }
          }else if($getUserData->user_role == 'offices'){
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
					'id'							=> 'required',
					'intervention_time'                       => 'required',
					'intervention_duration'				            => 'required',
          'agent_comments'    => 'required'

				)
			);
		
			if ($validator->fails()){
				$response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$getAlarmData = Alarm::where('id',$request->id)->first();
        if(empty($getAlarmData)){
          $response				=	array();
          $response["status"]		=	"error";
          $response["data"]		=	(object)array();
          $response["msg"]		=	trans("Alarm does not exists.");
          $response["http_code"]	=	401;
          return response()->json($response,200);
        }
                DB::beginTransaction();
                $obj 									=  Alarm::find($getAlarmData->id);
				        $obj->intervention_time 								=   date('h:i A',strtotime($request->input('intervention_time')));
                $obj->intervention_duration 						    =  $request->input('intervention_duration');
                $obj->agent_comments 						        =  $request->input('agent_comments');
                $obj->is_noise_heard_from_outside 			=  !empty($request->input('is_noise_heard_from_outside')) ? 1 : 0;
                $obj->is_guest_opened_door 						    =  !empty($request->input('is_guest_opened_door')) ? 1 : 0;
                $obj->is_noise_goes_down 						    =  !empty($request->input('is_noise_goes_down')) ? 1 : 0;
                $obj->alarm_status                          = 'completed';
 
                $obj->save();
                $userId  = $obj->id;
                

               if($userId){
                   DB::commit();

                   //Save Pdf Report 
                   $this->savePdfReport($userId);

                   $response				=	array();
                   $response["status"]		=	"success";
                   $response["data"]		=	(object)array();
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
    // print_r($request->report_files);die;
    if(!empty($id)){
      if(!empty($request->report_files)){
        $getReportDetails = Alarm::where('id',$id)->first();
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
    public function addAlarmItem(Request $request){
      $getLoggedInUserId = Auth::guard('api')->user()->id;
      $formData	=	$request->all();
      $response	=	array();
      if(!empty($formData)){
        $validator 					=	Validator::make(
          $request->all(),
          array(
            'date'							=> 'required',
            'time'                       => 'required',
            'customer_id'				            => 'required',
            'room_name'					    => 'required',
            'city'                       => 'required',
            'address'       => 'required',
            // 'agent_id'    => 'required',
            'alarm_type'    => 'required',
            'is_manager_contacted'    => 'required',
            'is_guest_reached'    => 'required',
            'comments'    => 'required',
            'type'    => 'required',
            'alarm_status'    => 'required',
          )
        );
      
        if ($validator->fails()){
          $response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
        }else{
          
          
          DB::beginTransaction();
          $obj 									=  new Alarm;
          $obj->created_by         =  $getLoggedInUserId;
          $obj->date 								=  date('Y-m-d',strtotime($request->input('date')));
          $obj->time 								=  date('h:i A',strtotime($request->input('time')));
          $obj->customer_id 								=  $request->input('customer_id');
          $obj->room_name 								=  $request->input('room_name');
          $obj->city 								=  $request->input('city');
          $obj->address 								=  $request->input('address');
          $obj->agent_id 								=  !empty($request->input('agent_id')) ? $request->input('agent_id') : 0 ;
          $obj->alarm_type 								=  $request->input('alarm_type');
          $obj->is_manager_contacted 								=  $request->input('is_manager_contacted');
          $obj->is_guest_reached 								=  $request->input('is_guest_reached');
          $obj->comments 								=  $request->input('comments');
          $obj->type 								=  $request->input('type');

          $obj->alarm_status 								=  $request->input('alarm_status');

          if($request->input('is_manager_contacted') == 1){
            $obj->manager_details 								=  $request->input('manager_details');
          }
          if($request->input('is_guest_reached') == 1){
            $obj->guest_details 								=  $request->input('guest_details');
          }
          if($request->input('alarm_type') == 'owner_call' || $request->input('alarm_type') == 'neighbor_call' || $request->input('alarm_type') == 'guest_call' ){
            $obj->caller_name 								=  $request->input('caller_name');
            $obj->caller_phone_number 								=  $request->input('caller_phone_number');
            $obj->caller_location 								=  $request->input('caller_location');
          }
         
          $obj->save();
          $userId  = $obj->id;
                  

          if($userId){
              DB::commit();
              if(Auth::guard('api')->user()->user_role == 'admin'){

                $getAlarmsData = Alarm::orderBy('updated_at','desc')->get();
              }else{
                $getAlarmsData = Alarm::where('created_by',$getLoggedInUserId)->orderBy('updated_at','desc')->get();
              }
              $response				=	array();
              $response["status"]		=	"success";
              $response["data"]		=	$getAlarmsData;
              if($obj->alarm_status == 'draft'){
                $response["msg"]		=	trans("Item saved successfully.");
              }else{

                $response["msg"]		=	trans("Item added successfully.");
              }
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

    public function updateAlarmItem(Request $request){
      if(!empty($request->id)){
          $getLoggedInUserId = Auth::guard('api')->user()->id;
          $formData	=	$request->all();
          $response	=	array();
          if(!empty($formData)){
            $validator 					=	Validator::make(
              $request->all(),
              array(
                'date'							=> 'required',
                'time'                       => 'required',
                'customer_id'				            => 'required',
                'room_name'					    => 'required',
                'city'                       => 'required',
                'address'       => 'required',
                // 'agent_id'    => 'required',
                'alarm_type'    => 'required',
                'is_manager_contacted'    => 'required',
                'is_guest_reached'    => 'required',
                'comments'    => 'required',
                'type'    => 'required',

              )
            );
          
            if ($validator->fails()){
              $response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
            }else{
              
              DB::beginTransaction();
              $obj 									=  Alarm::find($request->id);
              $obj->date 								=  date('Y-m-d',strtotime($request->input('date')));
              $obj->time 								=  date('h:i A',strtotime($request->input('time')));
              $obj->customer_id 								=  $request->input('customer_id');
              $obj->room_name 								=  $request->input('room_name');
              $obj->city 								=  $request->input('city');
              $obj->address 								=  $request->input('address');
              $obj->agent_id 								=  !empty($request->input('agent_id')) ? $request->input('agent_id') : 0 ;
              $obj->alarm_type 								=  $request->input('alarm_type');
              $obj->is_manager_contacted 								=  $request->input('is_manager_contacted');
              $obj->is_guest_reached 								=  $request->input('is_guest_reached');
              $obj->comments 								=  $request->input('comments');
              $obj->type 								=  $request->input('type');

              if($request->input('is_manager_contacted') == 1){
                $obj->manager_details 								=  $request->input('manager_details');
              }else{
                $obj->manager_details 								=  "";
              }
              if($request->input('is_guest_reached') == 1){
                $obj->guest_details 								=  $request->input('guest_details');
              }else{
                $obj->guest_details 								=  "";
              }
              if($request->input('alarm_type') == 'owner_call' || $request->input('alarm_type') == 'neighbor_call' || $request->input('alarm_type') == 'guest_call' ){
                $obj->caller_name 								=  $request->input('caller_name');
                $obj->caller_phone_number 								=  $request->input('caller_phone_number');
                $obj->caller_location 								=  $request->input('caller_location');
              }else{
                $obj->caller_name 								=  "";
                $obj->caller_phone_number 								=  "";
                $obj->caller_location 								=  "";
              }
            
              $obj->save();
              $userId  = $obj->id;
                      

              if($userId){
                  DB::commit();
                  if(Auth::guard('api')->user()->user_role == 'admin'){

                    $getAlarmsData = Alarm::orderBy('updated_at','desc')->get();
                  }else{
                    $getAlarmsData = Alarm::where('created_by',$getLoggedInUserId)->orderBy('updated_at','desc')->get();
                  }
                  $response				=	array();
                  $response["status"]		=	"success";
                  $response["data"]		=	$getAlarmsData;
                  $response["msg"]		=	trans("Item updated successfully.");
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

    public function dropdownManagers(){
      $agentsList = User::where('user_role','night_agents')->where('is_active',1)->where('is_deleted',0)->where('is_verified',1)->select('id','name as text')->get();
      $customersList = Customer::select('id','alias as text')->where('status','!=','draft')->get();
      $response				=	array();
      $response["status"]		=	"success";
      $response["data"]		=	(object)array();
      $response['agents_list'] = $agentsList;
      $response['customers_list'] = $customersList;

      $response["msg"]		=	trans("Data Found");
      $response["http_code"]	=	200;
      return response()->json($response,200);
    }

    public function fetchAlarms(Request $request){
      $getLoggedInUserId = Auth::guard('api')->user()->id;
      if(Auth::guard('api')->user()->user_role == 'admin'){

        $getAlarmnsData = Alarm::query();
      }else if(Auth::guard('api')->user()->user_role == 'offices'){
        $getAlarmnsData = Alarm::where('alarms.created_by',$getLoggedInUserId);
      }else{
        $getAlarmnsData = Alarm::where('alarms.agent_id',$getLoggedInUserId);
      }
      $getAlarmnsData = $getAlarmnsData->orderBy('alarms.updated_at','desc')->get();
     
           
      if($getAlarmnsData->isNotEmpty()){
        foreach($getAlarmnsData as $alarmVal){
          $alarmVal->customer_name = DB::table('customers')->where('id',$alarmVal->customer_id)->value('company_name');
          $alarmVal->agent_name = !empty($alarmVal->agent_id) ? DB::table('users')->where('id',$alarmVal->agent_id)->value('name') : 'N/A';
          $alarmVal->time = date('H:i',strtotime($alarmVal->time));
          $alarmVal->alarm_type = !empty(config('alarm_type')[$alarmVal->alarm_type]) ?config('alarm_type')[$alarmVal->alarm_type] : 'N/A' ;
        }
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
    }

    public function uploadAlarmFile(Request $request)
    {
        $getLoggedInUserId = Auth::guard('api')->user()->id;
        $path = $request->file('alarm_file')->getRealPath();
        $records = array_map('str_getcsv', file($path));

        if (! count($records) > 0) {
          $response				=	array();
          $response["status"]		=	"error";
          $response["data"]		=	(object)array();
          $response["msg"]		=	trans("The file should have atleast one record to upload.");
          $response["http_code"]	=	401;
          return response()->json($response,200);
        }
        // Remove the header column
        array_shift($records);

        foreach ($records as $record) {
            // Decode unwanted html entities
            $record =  array_map("html_entity_decode", $record);

            // Get the clean data
            $this->rows[] = $this->clear_encoding_str($record);
        }

        foreach ($this->rows as $data) {
          AlarmImport::create([
              'user_id' => $getLoggedInUserId,
               'hour' => !empty($data[0]) ? $data[0] : '',
               'types_of_alarms' => !empty($data[1]) ? $data[1] : '',
               'room_name' => !empty($data[2]) ? $data[2] : '',
               'user' => !empty($data[3]) ? $data[3] : '',
               'alarms' => !empty($data[4]) ? $data[4] : '',
               'agent_sent' => !empty($data[5]) ? $data[5] : '',
               'agent_name' => !empty($data[6]) ? $data[6] : '',
               'guest_reached' => !empty($data[7]) ? $data[7] : '',
               'guest_name' => !empty($data[8]) ? $data[8] : '',
            ]);
        }

        if(Auth::guard('api')->user()->user_role == 'admin'){

          $getAlarmsData = AlarmImport::orderBy('updated_at','desc')->get();
        }else{
          $getAlarmsData = AlarmImport::where('user_id',$getLoggedInUserId)->orderBy('updated_at','desc')->get();
        }

        $response				=	array();
        $response["status"]		=	"success";
        $response["data"]		=	$getAlarmsData;
        $response["msg"]		=	trans("Alarms Uploaded Successfully.");
        $response["http_code"]	=	200;
        return response()->json($response,200);
      
    }
    
    private function clear_encoding_str($value)
    {
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $key => $val) {
                $clean[$key] = mb_convert_encoding($val, 'UTF-8', 'UTF-8');
            }
            return $clean;
        }
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    public function uploadUserImage(Request $request,$id = 0){
      $getLoggedInUserId = Auth::guard('api')->user()->id;
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
            $checkIfUserImageAlreadyExists = User::where('id',$getLoggedInUserId)->value('user_image');
            if(!empty($checkIfUserImageAlreadyExists)){
              $filePath					=	public_path('/uploads/users/').$checkIfUserImageAlreadyExists;

              //Remove uploaded image from directory as well
              if(\File::exists($filePath)){
      
                \File::delete($filePath);
            
              }
      
            }
            User::where('id',$getLoggedInUserId)->update(['user_image'=>$folderName.$fileName]);
           
          }else{
            $response				=	array();
            $response["status"]		=	"error";
            $response["data"]		=	(object)array();
            $response["msg"]		=	trans("Something went wrong while uploading the image.");
            $response["http_code"]	=	401;
            return response()->json($response,200);
          }
            
  
            $getUserDetails = User::where('id',$getLoggedInUserId)->first();
            if(!empty($getUserDetails)){
              $getUserDetails->user_image_url = url('/uploads/users').'/'.$getUserDetails->user_image;
            
              $response				=	array();
              $response["status"]		=	"success";
              $response["data"]		=	$getUserDetails;
              $response["msg"]		=	trans("Profile picture changed successfully.");
              $response["http_code"]	=	200;
              return response()->json($response,200);
  
            }else{
              $response				=	array();
              $response["status"]		=	"error";
              $response["data"]		=	(object)array();
              $response["msg"]		=	trans("Something Went Wrong");
              $response["http_code"]	=	401;
              return response()->json($response,200);
            }
          
         
        }else{
          $response				=	array();
          $response["status"]		=	"error";
          $response["data"]		=	(object)array();
          $response["msg"]		=	trans("The user image field is required.");
          $response["http_code"]	=	401;
          return response()->json($response,200);
        }
    
      
    }

    public function fetchAlarmDetail(Request $request){
      if(!empty($request->id)){
        $getAlarmDetails = Alarm::where('id',$request->id)->first();
          if(!empty($getAlarmDetails)){
            $getAlarmDetails->created_date = date('d-m-Y',strtotime($getAlarmDetails->created_at));
            $getAlarmDetails->created_time = date('H:i',strtotime($getAlarmDetails->created_at));
            $getAlarmDetails->customer_name = DB::table('customers')->where('id',$getAlarmDetails->customer_id)->value('company_name');
            $getAlarmDetails->agent_name = DB::table('users')->where('id',$getAlarmDetails->agent_id)->value('name');
            // $getAlarmDetails->alarm_type = !empty(config('alarm_type')[$getAlarmDetails->alarm_type]) ?config('alarm_type')[$getAlarmDetails->alarm_type] : 'N/A' ;

            if(!empty($getAlarmDetails->pdf_file)){
              $getAlarmDetails->pdf_file_url = url('/uploads/pdf').'/'.$getAlarmDetails->pdf_file;
            }
            $getuploadedFiles = ReportFile::where('report_id',$getAlarmDetails->id)->get();
            if($getuploadedFiles->isNotEmpty()){
              foreach($getuploadedFiles as $uploadedFileVal){
                $uploadedFileVal->file_url = url('/uploads/reports').'/'.$uploadedFileVal->file;
              }
            }
              $response				=	array();
              $response["status"]		=	"success";
              $response["data"]		=	$getAlarmDetails;
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
          $response["msg"]		=	trans("The alarm id field is required.");
          $response["http_code"]	=	401;
          return response()->json($response,200);

      }
    }



    public function emailReport(Request $request){
      if(!empty($request->id)){
        $getData = Alarm::where('id',$request->id)->first();
         if(!empty($getData)){
          $getData->customer_name = DB::table('customers')->where('id',$getData->customer_id)->value('company_name');
          $getData->agent_name = DB::table('users')->where('id',$getData->agent_id)->value('name');
          // $getData->alarm_type = !empty(config('alarm_type')[$getData->alarm_type]) ?config('alarm_type')[$getData->alarm_type] : 'N/A' ;
          
          $fromEmail 		= config('settings.from_email');
          $customerData = Customer::where('id',$getData->customer_id)->first();
          $subject = "Report Alarm Assistant";
          Mail::send('emails.report_email', compact('getData'), function($message) use ($getData,$subject,$fromEmail,$customerData) {
						$message->to($customerData->reports_email, $customerData->company_name)->subject($subject);   
						$message->from($fromEmail,env('APP_NAME'));
            $message->attach(public_path('/uploads/pdf/').$getData->pdf_file);

					 });

            $response				=	array();
            $response["status"]		=	"success";
            $response["data"]		=	array();
            $response["msg"]		=	trans("Email sent successfully");
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

    public function savePdfReport($id = 0){
      $getData = Alarm::where('id',$id)->first();
      if(!empty($getData)){
        $getData->customer_name = DB::table('customers')->where('id',$getData->customer_id)->value('company_name');
        $getData->agent_name = DB::table('users')->where('id',$getData->agent_id)->value('name');
        // $getData->alarm_type = !empty(config('alarm_type')[$getData->alarm_type]) ?config('alarm_type')[$getData->alarm_type] : 'N/A' ;
        $filePath					=	public_path('/uploads/pdf/').$getData->pdf_file;
          //Remove uploaded file from directory if exists
          if(\File::exists($filePath)){

            \File::delete($filePath);
        
          }
        $fileName					=	time().'-report-pdf'.$getData->id.'.pdf';
        $folderName     			= 	strtoupper(date('M'). date('Y'))."/";
        $folderPath					=	public_path('/uploads/pdf/').$folderName.$fileName;
        if(!File::exists(public_path('/uploads/pdf/').$folderName)) {
          File::makeDirectory(public_path('/uploads/pdf/').$folderName, $mode = 0777,true);
        }
        PDF::loadView('front.pdf.report_pdf', compact('getData'), [], [
          'title'      => 'Report Alarm Assiatant',
          'margin_top' => 0
        ])->save($folderPath);
        //update pdf file
        Alarm::where('id',$id)->update(['pdf_file' =>$folderName.$fileName ]);
      }
    }


}
