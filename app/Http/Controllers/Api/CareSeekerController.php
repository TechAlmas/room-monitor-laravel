<?php

namespace App\Http\Controllers\Api\v1;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\Model\User;
use App\Model\UserDeviceToken;
use App\Model\Country;
use App\Model\Contact;
use App\Model\EmailAction;
use App\Model\EmailTemplate;
use App\Model\Category;
use App\Model\Block;
use App\Model\SubCategory;
use App\Model\SymptomSubcategories;

use App\Model\Faq;
use App\Model\Lookup;
use App\Model\PatientDetail;
use App\Model\PatientAddress;
use App\Model\UserEmergencyContact;
use App\Model\PatientAllergies;
use App\Model\CurrentMedication;
use App\Model\PastMedicate;
use App\Model\ChronicDisease;
use App\Model\Injuries;
use App\Model\SmokingHabits;
use App\Model\AlcoholConsumption;
use App\Model\ActivityLevel;
use App\Model\FoodPrefreces;
use App\Model\Occupation;
use App\Model\Booking;
use App\Model\BookingSymptom;

use Illuminate\Support\Facades\Auth;
use Validator; 
use Helper,Hash,File,Config,DB,PDF;
use Stripe;
class CareSeekerController extends BaseController
{ 

	public function __construct(Request $request) {
		parent::__construct();
        $this->request              =   $request;
    }


	public function updatePersonalProfile(Request $request){

		$formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$request->replace($this->arrayStripTags($request->all()));
			$validator = Validator::make(
				$request->all(),
				array(
					'first_name'					=> 'required',
					'last_name'						=> 'required',
					'gender'						=> 'required',
                    'dob'							=> 'required',
                    // 'country'						=> 'required',
					// 'address_line_1'				=> 'required',
					// 'address_line_3'				=> 'required',
					// 'areas'							=> 'required_if:country,==,99',
					// 'districts'						=>  'required_if:country,==,99',
					// 'latitude'						=> 'required',
				   	// 'longitude'						=> 'required',
                    'identification_document'       => 'nullable|mimes:'.IMAGE_EXTENSION_DOCUMENTS,
				),
				array(
					"first_name.required"		 				=> trans("messages.The_first_name_field_is_required"),
					"last_name.required"		 				=> trans("messages.The_last_name_field_is_required"),
					"gender.required"		 					=> trans("messages.The_gender_field_is_required"),
					"dob.required"		 					=> trans("messages.The_dob_field_is_required"),
					// "country.required"		 			=> trans("messages.The_country_field_is_required"),
					// "address_line_1.required"			=>	trans("messages.The_google_map_location_field_is_required"),
					// "address_line_3.required"			=>	trans("messages.The_address_line_1_field_is_required"),
					// "areas.required_if"		 			=> trans("messages.The_area_field_is_required"),
					// "districts.required_if"			 	=> trans("messages.The_district_field_is_required"),
					// "latitude.required"							=> trans("messages.The_latitude_field_is_required"),
					// "longitude.required"						=> trans("messages.The_longitude_field_is_required"),
                    "identification_document.mimes"				=>	trans("messages.The_identification_document_must_be_a_file_of_type"),
				)
			);
			if ($validator->fails()){
				$response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$userId = Auth::guard('api')->user()->id;

                $obj 									        = User::find($userId);
				$obj->first_name 								=  $request->input('first_name');
				$obj->last_name 								=  $request->input('last_name');
				$obj->name 										=  $request->input('first_name').' '.$request->input('last_name');
				$obj->dob 										=  date('Y-m-d', strtotime($request->input('dob')));
				$obj->gender 									=  $request->input('gender');
                $obj->save();
                $insertID =  $obj->id;

                $checkIfPatientAddressExists = PatientAddress::where('user_id',$userId)->first();
                if(!empty($checkIfPatientAddressExists)){
                    $patient_address								=  PatientAddress::find($checkIfPatientAddressExists->id);
                }else{
                    $patient_address								= new PatientAddress;
                }

				$patient_address->user_id						= $userId;
				$patient_address->country						= $request->input('country');
				$patient_address->address_line_1				= !empty($request->input('address_line_1')) ? $request->input('address_line_1') : '' ;
				$patient_address->address_line_2				= !empty($request->input('address_line_2')) ? $request->input('address_line_2') : '' ;
				$patient_address->address_line_3				= !empty($request->input('address_line_3')) ? $request->input('address_line_3') : '' ;
				$patient_address->areas							= !empty($request->input('areas')) ? $request->input('areas') : 0 ;
				$patient_address->districts						= !empty($request->input('districts')) ? $request->input('districts') : 0;
				$patient_address->building_name					= !empty($request->input('building_name')) ? $request->input('building_name') : 0;
				$patient_address->latitude						= !empty($request->input('latitude')) ? $request->input('latitude') : 0;
				$patient_address->longitude						= !empty($request->input('longitude')) ? $request->input('longitude') : 0;

				$patient_address->save();


                $checkIfPatientPatientDetail = PatientDetail::where('patient_id',$userId)->first();
                if(!empty($checkIfPatientPatientDetail)){
                    $patient_details								=  PatientDetail::find($checkIfPatientPatientDetail->id);
                }else{
                    $patient_details								= new PatientDetail;
                    $patient_details->patient_id					= $userId;
                }

				if($request->hasFile('identification_document')){
					$extension 		=	 $request->file('identification_document')->getClientOriginalExtension();
					$original_identification_document_name 	=	 $request->file('identification_document')->getClientOriginalName();
					$fileName	=	time().'-identification_document.'.$extension;

					$folderName     	= 	strtoupper(date('M'). date('Y'))."/";
					$folderPath			=	USER_IMAGE_ROOT_PATH.$folderName;
					if(!File::exists($folderPath)) {
						File::makeDirectory($folderPath, $mode = 0777,true);
					}
					if($request->file('identification_document')->move($folderPath, $fileName)){
						$patient_details->identification_document	=	$folderName.$fileName;
						$patient_details->original_identification_document_name	=	$original_identification_document_name;
					}
				
				}
                $patient_details->save();

                $checkIfPatientECOntactExists = UserEmergencyContact::where('user_id',$userId)->first();
                if(!empty($checkIfPatientECOntactExists)){
                    $patient_emergecy_contact								=  UserEmergencyContact::find($checkIfPatientECOntactExists->id);
                }else{
                    $patient_emergecy_contact								= new UserEmergencyContact;
                    $patient_emergecy_contact->user_id						= $userId;
                }
                // $e_contact  = !empty($request->input('e_contact')) ? json_decode($request->input('e_contact'), true) : "";
				// $patient_emergecy_contact->user_id						= $userId;
				// $patient_emergecy_contact->phone_number_prefix 			= !empty($e_contact->e_dial_codes) ? $e_contact->e_dial_codes:'';
				// $patient_emergecy_contact->phone_number_country_code 	= !empty($e_contact->e_country_codes) ? $e_contact->e_country_codes:'';
				// $patient_emergecy_contact->name 	                    = !empty($e_contact->e_name) ? $e_contact->e_name:'';
				// $patient_emergecy_contact->contact 	                    = !empty($e_contact->contact) ? $e_contact->contact:'';
				// $patient_emergecy_contact->save();
                 
				 $patient_emergecy_contact->phone_number_prefix 		= !empty($request->input('e_dial_codes')) ? $request->input('e_dial_codes'):'';
				 $patient_emergecy_contact->phone_number_country_code 	= !empty($request->input('e_country_codes')) ? $request->input('e_country_codes'):'';
				 $patient_emergecy_contact->name 	                    = !empty($request->input('e_name')) ? $request->input('e_name'):'';
				 $patient_emergecy_contact->contact 	                = !empty($request->input('e_contact') && $request->input('e_contact') != "undefined") ? $request->input('e_contact'):'';
				 $patient_emergecy_contact->save();

				
				$response["status"]	 	=	"success";
				$response["msg"]		=	trans("messages.profile_updated_successfully");
				$response["data"]		=	(object)array();

			}
				
		}else {
			$response["status"]		=	"error";
			$response["msg"]		=	trans("messages.Invalid_Request");
			$response["data"]		=	(object)array();
		}
		return json_encode($response);

	}


 
	public function updateAdditionalProfileDetails(Request $request){
        
		$formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$request->replace($this->arrayStripTags($request->all()));
			$validator = Validator::make(
				$request->all(),
                array(
                    // 'height'						=> 'required',
                    // 'weight'						=> 'required',
                    // 'blood_group'					=>  'required',
                    // 'marital_status'				=> 'required',
                ),
                array(
                    "height.required"		 				=> trans("messages.The_height_field_is_required"),
                    "weight.required"		 				=> trans("messages.The_weight_field_is_required"),
                    "blood_group.required"		 			=> trans("messages.The_blood_group_field_is_required"),
                    "marital_status.required"		 		=> trans("messages.The_marital_status_field_is_required"),
                )
			);
			if ($validator->fails()){
				$response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$userId = Auth::guard('api')->user()->id;
                $checkIfPatientDetailExists = PatientDetail::where('patient_id',$userId)->first();
                if(!empty($checkIfPatientDetailExists)){
                    $patient_details								=  PatientDetail::find($checkIfPatientDetailExists->id);
                }else{
                    $patient_details								= new PatientDetail;
                }
               
				$patient_details->patient_id					= $userId;
                if(!empty($request->input('blood_group'))){
                    $patient_details->blood_group					=   $request->input('blood_group');
                }
                if(!empty($request->input('marital_status'))){
                    $patient_details->marital_status					=   $request->input('marital_status');
                }
                if(!empty($request->input('height'))){
                    $patient_details->height					=   $request->input('height');
                }
                if(!empty($request->input('weight'))){
                    $patient_details->weight					=   $request->input('weight');
                }
                if(!empty($request->input('no_of_children'))){
                    $patient_details->no_of_children					=   $request->input('no_of_children');
                }else {
                    $patient_details->no_of_children					=   0;
                }
                if(!empty($request->input('pregnant'))){
                    $patient_details->pregnant					=   $request->input('pregnant');
                }
                if(!empty($request->input('smoke'))){
                    $patient_details->smoke					=   $request->input('smoke');
                }
                if(!empty($request->input('diabetes'))){
                    $patient_details->diabetes					=   $request->input('diabetes');
                }
                if(!empty($request->input('hypertension'))){
                    $patient_details->hypertension					=   $request->input('hypertension');
                }
                if(!empty($request->input('g6pd'))){
                    $patient_details->g6pd					=   $request->input('g6pd');
                }
                if(!empty($request->input('drug_alergy'))){
                    $patient_details->drug_alergy					=   $request->input('drug_alergy');
                }
                if(!empty($request->input('drug_alergy_description'))){
                    $patient_details->drug_alergy_description					=   $request->input('drug_alergy_description');
                }
                $patient_details->save();
               
				$response["status"]	 	=	"success";
				$response["msg"]		=	trans("messages.profile_updated_successfully");
				$response["data"]		=	(object)array();

			}
				
		}else {
			$response["status"]		=	"error";
			$response["msg"]		=	trans("messages.Invalid_Request");
			$response["data"]		=	(object)array();
		}
		return json_encode($response);

	}

    public function getMedicalInfo(){
      
        $allergies			= DB::table('lookups')->where('lookup_type','allergies')->select('code','id')->get()->toArray();
		$currentMedication	= DB::table('lookups')->where('lookup_type','current-medication')->select('code','id')->get()->toArray();
		$pastMedication		= DB::table('lookups')->where('lookup_type','past-medication')->select('code','id')->get()->toArray();
		$chronicDisease		= DB::table('lookups')->where('lookup_type','chronic-disease')->select('code','id')->get()->toArray();
		$patientsInjuries	= DB::table('lookups')->where('lookup_type','injuries')->select('code','id')->get()->toArray();
		$smokingHabit		= DB::table('lookups')->where('lookup_type','smoking-habits')->select('code','id')->get()->toArray();
		$alcoholConsumption	= DB::table('lookups')->where('lookup_type','alcohol-consumption')->select('code','id')->get()->toArray();
		$activityLevel		= DB::table('lookups')->where('lookup_type','activity-level')->select('code','id')->get()->toArray();
		$foodPreferences	= DB::table('lookups')->where('lookup_type','food-preferences')->select('code','id')->get()->toArray();
		$occupations		= DB::table('lookups')->where('lookup_type','occupation')->select('code','id')->get()->toArray();


        $response["status"]		=	"success";
        $response["msg"]		=	trans("");
        $response["data"]		=	['allergies'=> $allergies, 'currentMedication'=>$currentMedication,  'pastMedication'=>$pastMedication,  'chronicDisease'=>$chronicDisease,'patientsInjuries'=>$patientsInjuries,'smokingHabit'=>$smokingHabit,'alcoholConsumption'=>$alcoholConsumption,'activityLevel'=>$activityLevel,'foodPreferences'=>$foodPreferences,'occupations'=>$occupations];
        return response()->json($response,200);
		
		
	}

    public function addTagMedicalInfo(Request $request){

        $formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$request->replace($this->arrayStripTags($request->all()));
			$validator = Validator::make(
				$request->all(),
				array(
                    'type'						=> 'required',
                    'tag'					    =>  'required',
				),
				array(
					"type.required"		 				=> trans("messages.The_type_field_is_required"),
					"tag.required"		 					=> trans("messages.The_tag_field_is_required"),
				)
			);
			if ($validator->fails()){
				$response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$userId = Auth::guard('api')->user()->id;
                $type = $request->type;
                $tag  = $request->tag;
                if($type == 'allergies'){
                    $patientAllergies							= PatientAllergies::where('name',$tag)->where('patient_id',$userId)->first();
                    if(!empty($patientAllergies)){
                        $response["status"]		=	"already_exist";
                        $response["msg"]		=	trans("messages.tag_already_exist");
                        $response["data"]		=	(object)array();
                        return response()->json($response,200);
                    }else{
                        $patientAllergies							= new PatientAllergies;
                        $patientAllergies->patient_id				= $userId;
                        $patientAllergies->name						= $tag;
                        $patientAllergies->save();
                    }
                    

                }elseif($type == 'currentMedication'){
                    $currentpatientAllergies							= CurrentMedication::where('name',$tag)->where('patient_id',$userId)->first();
                    if(!empty($currentpatientAllergies)){
                        $response["status"]		=	"already_exist";
                        $response["msg"]		=	trans("messages.tag_already_exist");
                        $response["data"]		=	(object)array();
                        return response()->json($response,200);
                    }else{
                        $currentpatientAllergies					= new CurrentMedication;
                        $currentpatientAllergies->patient_id		= $userId;
                        $currentpatientAllergies->name				= $tag;
                        $currentpatientAllergies->save();
                    }
        
                
                }elseif($type == 'pastMedication'){
                    $pastpatientMedication							= PastMedicate::where('name',$tag)->where('patient_id',$userId)->first();
                    if(!empty($pastpatientMedication)){
                        $response["status"]		=	"already_exist";
                        $response["msg"]		=	trans("messages.tag_already_exist");
                        $response["data"]		=	(object)array();
                        return response()->json($response,200);
                    }else{
                        $pastpatientMedication						= new PastMedicate;
                        $pastpatientMedication->patient_id			= $userId;
                        $pastpatientMedication->name				= $tag;
                        $pastpatientMedication->save();
                    }
                
                }elseif($type == 'chronicDisease'){
                    $ChronicDisease							= ChronicDisease::where('name',$tag)->where('patient_id',$userId)->first();
                    if(!empty($ChronicDisease)){
                        $response["status"]		=	"already_exist";
                        $response["msg"]		=	trans("messages.tag_already_exist");
                        $response["data"]		=	(object)array();
                        return response()->json($response,200);
                    }else{
                        $chronicDisease								= new ChronicDisease;
                        $chronicDisease->patient_id					= $userId;
                        $chronicDisease->name						= $tag;
                        $chronicDisease->save();
                    }
                
                }elseif($type == 'patientsInjuries'){
                    $Injuries							= Injuries::where('name',$tag)->where('patient_id',$userId)->first();
                    if(!empty($Injuries)){
                        $response["status"]		=	"already_exist";
                        $response["msg"]		=	trans("messages.tag_already_exist");
                        $response["data"]		=	(object)array();
                        return response()->json($response,200);
                    }else{
                        $patientInjuries							= new Injuries;
                        $patientInjuries->patient_id				= $userId;
                        $patientInjuries->name						= $tag;
                        $patientInjuries->save();
                    }
                
                }elseif($type == 'smokingHabit'){
                    $SmokingHabits							= SmokingHabits::where('name',$tag)->where('patient_id',$userId)->first();
                    if(!empty($SmokingHabits)){
                        $response["status"]		=	"already_exist";
                        $response["msg"]		=	trans("messages.tag_already_exist");
                        $response["data"]		=	(object)array();
                        return response()->json($response,200);
                    }else{
                        $patientSmokeHabits							= new SmokingHabits;
                        $patientSmokeHabits->patient_id				= $userId;
                        $patientSmokeHabits->name					= $tag;
                        $patientSmokeHabits->save();
                    }
                
                }elseif($type == 'alcoholConsumption'){
                    $AlcoholConsumption							= AlcoholConsumption::where('name',$tag)->where('patient_id',$userId)->first();
                    if(!empty($AlcoholConsumption)){
                        $response["status"]		=	"already_exist";
                        $response["msg"]		=	trans("messages.tag_already_exist");
                        $response["data"]		=	(object)array();
                        return response()->json($response,200);
                    }else{
                        $patientAlcoholConsumption					= new AlcoholConsumption;
                        $patientAlcoholConsumption->patient_id		= $userId;
                        $patientAlcoholConsumption->name			= $tag;
                        $patientAlcoholConsumption->save();
                    }
                
                }elseif($type == 'activityLevel'){
                    $ActivityLevel							= ActivityLevel::where('name',$tag)->where('patient_id',$userId)->first();
                    if(!empty($ActivityLevel)){
                        $response["status"]		=	"already_exist";
                        $response["msg"]		=	trans("messages.tag_already_exist");
                        $response["data"]		=	(object)array();
                        return response()->json($response,200);
                    }else{
                        $patientActivityLevel						= new ActivityLevel;
                        $patientActivityLevel->patient_id			= $userId;
                        $patientActivityLevel->name					= $tag;
                        $patientActivityLevel->save();
                    }
                
                }elseif($type == 'foodPreferences'){
                    $FoodPrefreces							= FoodPrefreces::where('name',$tag)->where('patient_id',$userId)->first();
                    if(!empty($FoodPrefreces)){
                        $response["status"]		=	"already_exist";
                        $response["msg"]		=	trans("messages.tag_already_exist");
                        $response["data"]		=	(object)array();
                        return response()->json($response,200);
                    }else{
                        $patientFoodPrefrences						= new FoodPrefreces;
                        $patientFoodPrefrences->patient_id			= $userId;
                        $patientFoodPrefrences->name				= $tag;
                        $patientFoodPrefrences->save();
                    }
                
                }elseif($type == 'occupations'){
                    $Occupation							= Occupation::where('name',$tag)->where('patient_id',$userId)->first();
                    if(!empty($Occupation)){
                        $response["status"]		=	"already_exist";
                        $response["msg"]		=	trans("messages.tag_already_exist");
                        $response["data"]		=	(object)array();
                        return response()->json($response,200);
                    }else{
                        $patientOccupation							= new Occupation;
                        $patientOccupation->patient_id				= $userId;
                        $patientOccupation->name					= $tag;
                        $patientOccupation->save();
                    }
        
                }else{
                    $response["status"]		=	"error";
                    $response["msg"]		=	trans("messages.Invalid_Request");
                    $response["data"]		=	(object)array();
                }
                $response["status"]		=	"success";
                $response["msg"]		=	trans("messages.tag_added_successfully");
                $response["data"]		=	(object)array();
              
			}
				
		}else {
			$response["status"]		=	"error";
			$response["msg"]		=	trans("messages.Invalid_Request");
			$response["data"]		=	(object)array();
		}
    
        
      return response()->json($response,200);
       
	}

    public function deleteTagMedicalInfo(Request $request){

        $formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$request->replace($this->arrayStripTags($request->all()));
			$validator = Validator::make(
				$request->all(),
				array(
                    'type'						=> 'required',
                    'tag'					    =>  'required',
				
				),
				array(
					"type.required"		 				=> trans("messages.The_type_field_is_required"),
					"tag.required"		 					=> trans("messages.The_tag_field_is_required"),
				
				)
			);
			if ($validator->fails()){
				$response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$userId = Auth::guard('api')->user()->id;
                $type = $request->type;
                $tag_id  = $request->tag;
                if($type == 'allergies'){
                    PatientAllergies::where('patient_id',$userId)->where('name',$tag_id)->delete();
                   
                }elseif($type == 'currentMedication'){
                    CurrentMedication::where('patient_id',$userId)->where('name',$tag_id)->delete();
                  
                }elseif($type == 'pastMedication'){
                    PastMedicate::where('patient_id',$userId)->where('name',$tag_id)->delete();
                    
                
                }elseif($type == 'chronicDisease'){
                    ChronicDisease::where('patient_id',$userId)->where('name',$tag_id)->delete();
                    
                }elseif($type == 'patientsInjuries'){
                    Injuries::where('patient_id',$userId)->where('name',$tag_id)->delete();
                   
                }elseif($type == 'smokingHabit'){
                    SmokingHabits::where('patient_id',$userId)->where('name',$tag_id)->delete();
               
                
                }elseif($type == 'alcoholConsumption'){
                    AlcoholConsumption::where('patient_id',$userId)->where('name',$tag_id)->delete();
                 
                
                }elseif($type == 'activityLevel'){
                    ActivityLevel::where('patient_id',$userId)->where('name',$tag_id)->delete();
                  
                
                }elseif($type == 'foodPreferences'){
                    FoodPrefreces::where('patient_id',$userId)->where('name',$tag_id)->delete();
                   
                }elseif($type == 'occupations'){
                    Occupation::where('patient_id',$userId)->where('name',$tag_id)->delete();
                    
                }else{
                    $response["status"]		=	"error";
                    $response["msg"]		=	trans("messages.Invalid_Request");
                    $response["data"]		=	(object)array();
                }
                $response["status"]		=	"success";
                $response["msg"]		=	trans("messages.tag_removed_successfully");
                $response["data"]		=	(object)array();
              
			}
				
		}else {
			$response["status"]		=	"error";
			$response["msg"]		=	trans("messages.Invalid_Request");
			$response["data"]		=	(object)array();
		}
    
        
      return response()->json($response,200);
       
	}

    public function view_profile(Request $request){

        $model = DB::table('users')
		->leftjoin('patient_details', 'users.id', '=', 'patient_details.patient_id')
        ->leftjoin("lookups as blood","blood.id","patient_details.blood_group")
		->leftjoin('patient_address', 'users.id', '=', 'patient_address.user_id')
		->leftjoin('user_emergency_contact', 'users.id', '=', 'user_emergency_contact.user_id')
		->select('users.*', 'patient_details.identification_document','patient_details.original_identification_document_name','patient_details.blood_group','blood.code as blood_group_name','patient_details.marital_status','patient_details.height','patient_details.weight','patient_details.no_of_children','patient_details.pregnant','patient_details.smoke','patient_details.diabetes','patient_details.hypertension','patient_details.g6pd','patient_details.drug_alergy',"patient_details.drug_alergy_description",'patient_address.areas','patient_address.districts','patient_address.address_line_1','patient_address.address_line_2','patient_address.address_line_3','patient_address.building_name','patient_address.country','user_emergency_contact.name as e_name','user_emergency_contact.contact as e_contact','user_emergency_contact.phone_number_prefix as e_dial_codes','user_emergency_contact.phone_number_country_code as e_country_codes','patient_address.latitude','patient_address.longitude')
		->where('users.id',  Auth::guard('api')->user()->id)
		->first();

       if(!empty($model)){
            $modelId   = $model->id;

            if(!empty($model->image) && file_exists(USER_IMAGE_ROOT_PATH.$model->image)){
                $model->image		 = USER_IMAGE_URL.$model->image;
            }else{
                $model->image		 = WEBSITE_IMG_URL.'noimage.png';
            }
            if(!empty($model->identification_document) && file_exists(USER_IMAGE_ROOT_PATH.$model->identification_document)){
                $model->identification_document		 = USER_IMAGE_URL.$model->identification_document;
            }else{
                $model->identification_document		 = "";
            }

            $model->patient_test_report = DB::table('patient_test_report')->where('patient_id',$model->id)->get();
		    $model->patient_prescription = DB::table('patient_prescription')->where('patient_id',$model->id)->get();

            $model->allergies = DB::table('patient_allergies')
			->select('patient_allergies.name')
			->where('patient_allergies.patient_id', $modelId)
			->get()->toArray();
		

            $model->currentMedication = DB::table('patient_current_medications')
                ->select('patient_current_medications.name')
                ->where('patient_current_medications.patient_id', $modelId)
                ->get()->toArray();

            $model->pastMedication = DB::table('patient_past_medications')
                ->select('patient_past_medications.name')
                ->where('patient_past_medications.patient_id', $modelId)
                ->get()->toArray();

            $model->chronicDisease = DB::table('patient_chronic_diseases')
                ->select('patient_chronic_diseases.name')
                ->where('patient_chronic_diseases.patient_id', $modelId)
                ->get()->toArray();

            $model->patientsInjuries = DB::table('patient_injuries')
                ->select('patient_injuries.name')
                ->where('patient_injuries.patient_id', $modelId)
                ->get()->toArray();
                
            $model->smokingHabit = DB::table('patient_smoking_habits')
                ->select('patient_smoking_habits.name')
                ->where('patient_smoking_habits.patient_id', $modelId)
                ->get()->toArray();
                
            $model->alcoholConsumption = DB::table('patient_alcohol_consumptions')
                ->select('patient_alcohol_consumptions.name')
                ->where('patient_alcohol_consumptions.patient_id', $modelId)
                ->get()->toArray();
                
            $model->activityLevel = DB::table('patient_activity_levels')
                ->select('patient_activity_levels.name')
                ->where('patient_activity_levels.patient_id', $modelId)
                ->get()->toArray();
                
            $model->foodPreferences = DB::table('patient_food_preferences')
                ->select('patient_food_preferences.name')
                ->where('patient_food_preferences.patient_id', $modelId)
                ->get()->toArray();
                
            $model->occupations = DB::table('patient_occupation')
                ->select('patient_occupation.name')
                ->where('patient_occupation.patient_id', $modelId)
                ->get()->toArray();
          

           $response["status"]	 	=	"success";
           $response["msg"]		=	"";
           $response["data"]		=	$model;
       }else{

           $response["status"]		=	"error";
           $response["msg"]		=	trans("messages.Invalid_Request");
           $response["data"]		=	(object)array();
       }
   

       return json_encode($response);
   }

   


   public function updateDOB(Request $request){

    $formData	=	$request->all();
    $response	=	array();
    if(!empty($formData)){
        $request->replace($this->arrayStripTags($request->all()));
        $validator = Validator::make(
            $request->all(),
            array(
                'dob'				 => 'required',
            ),
            array(
                "dob.required"		 	=> trans("messages.The_dob_field_is_required"),
            )
        );
        if ($validator->fails()){
            $response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
        }else{
            $userId = Auth::guard('api')->user()->id;
            $userDetail   =   User::where('users.id',$userId)->first();
            $userDetail->dob   = !empty($request->input('dob')) ? date('Y-m-d', strtotime($request->input('dob'))) : NULL;
            $userDetail->save();

            $response["status"]		=	"success";
            $response["msg"]		=	trans("messages.profile_updated_successfully");
            $response["data"]		=	(object)array();
        }	
    }else {
        $response["status"]		=	"error";
        $response["msg"]		=	trans("messages.Invalid_Request");
        $response["data"]		=	(object)array();
    }
    return json_encode($response);

}


public function updateAddress(Request $request){

    $formData	=	$request->all();
    $response	=	array();
    if(!empty($formData)){
        $request->replace($this->arrayStripTags($request->all()));
        $validator = Validator::make(
            $request->all(),
            array(
                'country'						=> 'required',
                'address_line_1'				=> 'required',
                'address_line_3'				=> 'required',
                'areas'							=> 'required_if:country,==,99',
                'districts'						=>  'required_if:country,==,99',
                'latitude'						=> 'required',
                'longitude'						=> 'required',
            ),
            array(
                "country.required"		 			=> trans("messages.The_country_field_is_required"),
                "address_line_1.required"			=>	trans("messages.The_google_map_location_field_is_required"),
                "address_line_3.required"			=>	trans("messages.The_address_line_1_field_is_required"),
                "areas.required_if"		 			=> trans("messages.The_area_field_is_required"),
                "districts.required_if"			 	=> trans("messages.The_district_field_is_required"),
                "latitude.required"					=> trans("messages.The_latitude_field_is_required"),
                "longitude.required"				=> trans("messages.The_longitude_field_is_required"),
            )
        );
        if ($validator->fails()){
            $response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
        }else{
            $userId = Auth::guard('api')->user()->id;
            $checkIfPatientAddressExists = PatientAddress::where('user_id',$userId)->first();
            if(!empty($checkIfPatientAddressExists)){
                $patient_address								=  PatientAddress::find($checkIfPatientAddressExists->id);
            }else{
                $patient_address								= new PatientAddress;
                $patient_address->user_id						= $userId;
            }
            $patient_address->country						= !empty($request->input('country')) ? $request->input('country') : 0 ;
            $patient_address->address_line_1				= !empty($request->input('address_line_1')) ? $request->input('address_line_1') : '' ;
            $patient_address->address_line_2				= !empty($request->input('address_line_2')) ? $request->input('address_line_2') : '' ;
            $patient_address->address_line_3				= !empty($request->input('address_line_3')) ? $request->input('address_line_3') : '' ;
            $patient_address->areas							= !empty($request->input('areas')) ? $request->input('areas') : 0 ;
            $patient_address->districts						= !empty($request->input('districts')) ? $request->input('districts') : 0;
            $patient_address->building_name					= !empty($request->input('building_name')) ? $request->input('building_name') : 0;
            $patient_address->latitude						= !empty($request->input('latitude')) ? $request->input('latitude') : 0;
            $patient_address->longitude						= !empty($request->input('longitude')) ? $request->input('longitude') : 0;
            $patient_address->save();

            $response["status"]		=	"success";
            $response["msg"]		=	trans("messages.profile_updated_successfully");
            $response["data"]		=	(object)array();
        }	
    }else {
        $response["status"]		=	"error";
        $response["msg"]		=	trans("messages.Invalid_Request");
        $response["data"]		=	(object)array();
    }
    return json_encode($response);

}




public function updateContactName(Request $request){

    $formData	=	$request->all();
    $response	=	array();
    if(!empty($formData)){
        $request->replace($this->arrayStripTags($request->all()));
        $validator = Validator::make(
            $request->all(),
            array(
                'contact_name'					=> 'required',
            ),
            array(
                "contact_name.required"		 				=> trans("messages.The_contact_name_field_is_required"),
            )
        );
        if ($validator->fails()){
            $response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
        }else{
            $userId = Auth::guard('api')->user()->id;
            $checkIfPatientECOntactExists = UserEmergencyContact::where('user_id',$userId)->first();
            if(!empty($checkIfPatientECOntactExists)){
                $patient_emergecy_contact								=  UserEmergencyContact::find($checkIfPatientECOntactExists->id);
            }else{
                $patient_emergecy_contact								= new UserEmergencyContact;
                $patient_emergecy_contact->user_id						= $userId;
            }
            $patient_emergecy_contact->name 	                        = !empty($request->input('contact_name')) ? $request->input('contact_name'):'';
            $patient_emergecy_contact->save();

            
            $response["status"]	 	=	"success";
            $response["msg"]		=	trans("messages.profile_updated_successfully");
            $response["data"]		=	(object)array();

        }
            
    }else {
        $response["status"]		=	"error";
        $response["msg"]		=	trans("messages.Invalid_Request");
        $response["data"]		=	(object)array();
    }
    return json_encode($response);

}




public function updateContactnumber(Request $request){

    $formData	=	$request->all();
    $response	=	array();
    if(!empty($formData)){
        $request->replace($this->arrayStripTags($request->all()));
        $validator = Validator::make(
            $request->all(),
            array(
                'contact_number'					    => 'required',
                'phone_number_prefix'					=> 'required',
                'phone_number_country_code'				=> 'required',
            ),
            array(
                "contact_number.required"		 				=> trans("messages.The_contact_number_field_is_required"),
                "phone_number_prefix.required"		 			=> trans("messages.The_phone_number_prefix_field_is_required"),
                "phone_number_country_code.required"		 	=> trans("messages.The_phone_number_country_code_field_is_required"),
            )
        );
        if ($validator->fails()){
            $response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
        }else{
            $userId = Auth::guard('api')->user()->id;
            $checkIfPatientECOntactExists = UserEmergencyContact::where('user_id',$userId)->first();
            if(!empty($checkIfPatientECOntactExists)){
                $patient_emergecy_contact								=  UserEmergencyContact::find($checkIfPatientECOntactExists->id);
            }else{
                $patient_emergecy_contact								= new UserEmergencyContact;
                $patient_emergecy_contact->user_id						= $userId;
            }
            $patient_emergecy_contact->phone_number_prefix 		    = !empty($request->input('phone_number_prefix')) ? $request->input('phone_number_prefix'):'';
            $patient_emergecy_contact->phone_number_country_code 	= !empty($request->input('phone_number_country_code')) ? $request->input('phone_number_country_code'):'';
            $patient_emergecy_contact->contact 	                    = !empty($request->input('contact_number')) ? $request->input('contact_number'):'';
            $patient_emergecy_contact->save();

            
            $response["status"]	 	=	"success";
            $response["msg"]		=	trans("messages.profile_updated_successfully");
            $response["data"]		=	(object)array();

        }
            
    }else {
        $response["status"]		=	"error";
        $response["msg"]		=	trans("messages.Invalid_Request");
        $response["data"]		=	(object)array();
    }
    return json_encode($response);

}



public function updateidentification(Request $request){
    $formData	=	$request->all();
    $response	=	array();
    if(!empty($formData)){
        $request->replace($this->arrayStripTags($request->all()));
        $validator = Validator::make(
            $request->all(),
            array(
                'identification_document'       => 'nullable|mimes:'.IMAGE_EXTENSION_DOCUMENTS,
            ),
            array(
                "identification_document.required"			=> trans("messages.The_identification_document_field_is_required"),
                "identification_document.mimes"				=>	trans("messages.The_identification_document_must_be_a_file_of_type"),
            )
        );
        if ($validator->fails()){
            $response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
        }else{
            $userId = Auth::guard('api')->user()->id;

            $checkIfPatientPatientDetail = PatientDetail::where('patient_id',$userId)->first();
            if(!empty($checkIfPatientPatientDetail)){
                $patient_details								=  PatientDetail::find($checkIfPatientPatientDetail->id);
            }else{
                $patient_details								= new PatientDetail;
                $patient_details->patient_id					= $userId;
            }
            if($request->hasFile('identification_document')){
                $extension 		=	 $request->file('identification_document')->getClientOriginalExtension();
                $original_identification_document_name 	=	 $request->file('identification_document')->getClientOriginalName();
                $fileName	=	time().'-identification_document.'.$extension;

                $folderName     	= 	strtoupper(date('M'). date('Y'))."/";
                $folderPath			=	USER_IMAGE_ROOT_PATH.$folderName;
                if(!File::exists($folderPath)) {
                    File::makeDirectory($folderPath, $mode = 0777,true);
                }
                if($request->file('identification_document')->move($folderPath, $fileName)){
                    $patient_details->identification_document	=	$folderName.$fileName;
                    $patient_details->original_identification_document_name	=	$original_identification_document_name;
                }
            
            }
            $patient_details->save();
            
            $response["status"]	 	=	"success";
            $response["msg"]		=	trans("messages.profile_updated_successfully");
            $response["data"]		=	(object)array();

        }
            
    }else {
        $response["status"]		=	"error";
        $response["msg"]		=	trans("messages.Invalid_Request");
        $response["data"]		=	(object)array();
    }
    return json_encode($response);

}



public function deleteIdentificationDoc(Request $request){
    $response = array();
    $userId   =   Auth::guard('api')->user()->id;

    $doc  =   PatientDetail::where('patient_id',$userId)->first();
    if(!empty($doc)){
        PatientDetail::where('patient_id',$userId)->update(array("identification_document"=>"","original_identification_document_name"=>""));
        $response["status"]		=	"success";
        $response["msg"]		=	trans("messages.identification_document_deleted_successfully");
        $response["data"]		=	(object)array();
    }else{
        $response["status"]		=	"error";
        $response["msg"]		=	trans("messages.Invalid_Request");
        $response["data"]		=	(object)array();
    }
    return json_encode($response);
}




    public function getSymptoms(Request $request){
        $response = array();
        $sub_cat  = (!empty($request->input('sub_category_id'))) ? explode(",",$request->input('sub_category_id')): array();
        if(empty($request->input('sub_category_id'))){
            $response["status"]		=	"error";
            $response["msg"]		=	trans("messages.The_sub_category_id_field_is_required");
            $response["data"]		=	(object)array();
            return json_encode($response);
        }
        $SymptomSubcategories  =   SymptomSubcategories::whereIn('subcategory_id',$sub_cat)
                                                        ->leftjoin('symptom_descriptions','symptom_descriptions.parent_id','symptom_subcategories.symtom_id')
                                                        ->select('symptom_descriptions.name','symptom_subcategories.symtom_id as id')
                                                        ->groupBy('symptom_subcategories.symtom_id')
                                                        ->get()->toArray();
        
        $response["status"]		=	"success";
        $response["msg"]		=	"";
        $response["data"]		=	$SymptomSubcategories;
        return json_encode($response);
    }


   
    


}
