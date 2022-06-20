<?php

namespace App\Http\Controllers\Api\v1;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\BaseController;
use App\Model\User;
use App\Model\AffiliatedHospital;
use App\Model\HcpServices;
use App\Model\HcpConsulation;
use App\Model\Certification;
use App\Model\WorkingHour;
use App\Model\HcpHoliday;
use App\Model\HcpHolidayTime;
use App\Model\Booking;
use App\Model\BookingSymptom;
use Illuminate\Support\Facades\Auth;
use Validator; 
use Helper,Hash,File,Config,DB,PDF;
use Stripe;

class BookingsController extends BaseController
{ 

	public function __construct(Request $request) {
		parent::__construct();
        $this->request              =   $request;
    }


	/**
	* Function use for search hcp
	*
	* @param null
	*
	* @return response
	*/
	public function searchHcp(Request $request){
		$formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$request->replace($this->arrayStripTags($request->all()));
			$validator = Validator::make(
				$request->all(),
				array(
					'keyword' 				=> 'required',
					'category_id' 			=> 'required',
				),
				array(
					"keyword.required"		 	=> trans("messages.The_keyword_field_is_required."),
					"category_id.required"		 	=> trans("messages.The_Category_field_is_required."),
				)
			);
			if ($validator->fails()){
				$response		=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$keyword		=	$request->input("keyword");
				$lang			=	App::getLocale();
				$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");

				$services		=	DB::table('lookups')->where("lookup_descriptions.code",'like','%'.$keyword.'%')->where('lookup_type','services')->leftJoin('lookup_descriptions', 'lookup_descriptions.parent_id', '=', 'lookups.id')->where("lookup_descriptions.language_id",$language_id)->where("lookups.is_active",1)->select("lookups.id","lookup_descriptions.code as name",DB::raw("('service') as type"));

				$sub_category	=	DB::table('sub_categories')->where("sub_categories_descriptions.name",'like','%'.$keyword.'%')->leftJoin('sub_categories_descriptions', 'sub_categories_descriptions.parent_id', '=', 'sub_categories.id')->where("sub_categories.category_id",$request->input("category_id"))->where("sub_categories_descriptions.language_id",$language_id)->select("sub_categories.id","sub_categories_descriptions.name",DB::raw("('speciality') as type"));

				$symptoms		=	DB::table('symptoms')->where("symptom_descriptions.name",'like','%'.$keyword.'%')->leftJoin('symptom_descriptions', 'symptom_descriptions.parent_id', '=', 'symptoms.id')->where("symptom_descriptions.language_id",$language_id)->select("symptoms.id","symptom_descriptions.name",DB::raw("('symptom') as type"));

				$sortBy  		=   'name';
				$order   		=   'ASC';
				$result  		= 	$services->union($sub_category)->union($symptoms)->orderBy($sortBy,$order)->get()->toArray(); 
				if(!empty($result)){
					foreach($result as &$result_v){
						if($result_v->type == "symptom"){
							$result_v->symptom_subcategories	=	DB::table("symptom_subcategories")->where("symtom_id",$result_v->id)->select("subcategory_id")->get()->toArray();
						}else {
							$result_v->symptom_subcategories	=	array();
						}
					}
				}
					

				$hcps		=	DB::table('users')
										->where(function ($query) use($keyword){
											$query->Orwhere("users.first_name",'like','%'.$keyword.'%');
											$query->Orwhere("users.last_name",'like','%'.$keyword.'%');
											//$query->Orwhere("users.professional_email",'like','%'.$keyword.'%');
											$query->Orwhere("hcp_details.bio",'like','%'.$keyword.'%');
										})
										->leftjoin('hcp_details', 'users.id', '=', 'hcp_details.hcp_id')
										->leftjoin('hcp_categories', 'users.id', '=', 'hcp_categories.hcp_id')
										->where("is_approved",1)->where("is_active",1)->where("hcp_categories.category_id",$request->input("category_id"))->where("is_deleted",0)->where("user_role_id",DOCTOR_ROLE_ID)->orderBy("first_name","ASC")->where("first_name","!=","")->select("users.id","users.first_name","users.last_name","users.image","hcp_details.year_of_graduation")->get()->toArray(); 
				if(!empty($hcps)){
					foreach($hcps as &$user_details){
						if(!empty($user_details->image) && file_exists(USER_IMAGE_ROOT_PATH.$user_details->image)){
							$user_details->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$user_details->image;
						}else{
							$user_details->image = WEBSITE_IMG_URL.'noimage.png';
						}
						$specialities     = DB::table('hcp_qualifications')->leftjoin('lookups', 'lookups.id', '=', 'hcp_qualifications.qualification')->where('hcp_qualifications.hcp_id',$user_details->id)->leftJoin('lookup_descriptions', 'lookup_descriptions.parent_id', '=', 'lookups.id')->where("lookup_descriptions.language_id",$language_id)->select("lookup_descriptions.code as name")->get()->toArray();

						$user_details->qualifications	=	$specialities;

						$hcp_consulation = 	DB::table('hcp_consultaion')->leftjoin('lookups', 'lookups.id', '=', 'hcp_consultaion.consultaion_id')->where('hcp_consultaion.hcp_id',$user_details->id)->leftJoin('lookup_descriptions', 'lookup_descriptions.parent_id', '=', 'lookups.id')->where("lookup_descriptions.language_id",$language_id)->select("lookup_descriptions.code as name")->get()->toArray();
						$user_details->consulations	=	$hcp_consulation;

						$service_fees	=	DB::table("hcp_consultaion")->where("hcp_consultaion.hcp_id",$user_details->id)->orderBy("hcp_consultaion.service_fees","ASC")->value("service_fees");
						if($service_fees == ""){
							$service_fees	=	0;
						}

						$year_of_graduation	=	$user_details->year_of_graduation;
						if(is_numeric($year_of_graduation)){
							if(date("Y") > $year_of_graduation){
								$total_experience	=	date("Y")-$year_of_graduation;
							}else {
								$total_experience	=	0;
							}
						}else {
							$total_experience	=	0;
						}
						$user_details->service_fees		=	"$".$service_fees;
						$user_details->total_reviews	=	0;
						$user_details->total_experience	=	$total_experience;
					}
				}


				$response["status"]		=	"success";
				$response["msg"]		=	"";
				$response["data"]		=	$result;
				$response["hcp"]		=	$hcps;
				return json_encode($response);
			}
		}else {
			$response["status"]		=	"erro";
			$response["msg"]		=	trans("messages.Invalid_Request");
			$response["data"]		=	(object)array();
		}
		return json_encode($response);
	}



	/**
	* Function use for search hcp for third step
	*
	* @param null
	*
	* @return response
	*/
	public function searchHcpThirdStep(Request $request){
		$formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$request->replace($this->arrayStripTags($request->all()));
			$validator = Validator::make(
				$request->all(),
				array(
					'category_id' 			=> 'required',
				),
				array(
					"category_id.required"		 	=> trans("messages.The_Category_field_is_required."),
				)
			);
			if ($validator->fails()){
				$response		=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				//$keyword		=	$request->input("keyword");
				$searchVariable		=	array();
				$lang			=	App::getLocale();
				$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");

				//$records_per_page	=	($request->input('per_page')) ? $request->input('per_page') : Config::get("Reading.records_per_page");
				$records_per_page	=	50;
				$DB		=	DB::table('users')
								->leftjoin('hcp_categories', 'users.id', '=', 'hcp_categories.hcp_id')
								->leftjoin('hcp_specialities', 'users.id', '=', 'hcp_specialities.hcp_id')
								->leftjoin('hcp_details', 'users.id', '=', 'hcp_details.hcp_id')
								->leftjoin('hcp_services', 'users.id', '=', 'hcp_services.hcp_id')
								->leftjoin('hcp_clinics', 'users.id', '=', 'hcp_clinics.hcp_id')
								/* ->leftjoin('areas', 'hcp_clinics.area', '=', 'areas.id')
								->leftjoin('districts', 'hcp_clinics.district', '=', 'districts.id') */
								->leftjoin('hcp_consultaion', 'users.id', '=', 'hcp_consultaion.hcp_id')
								->where("users.is_approved",1)->where("users.is_active",1)->where("hcp_categories.category_id",$request->input("category_id"))->where("users.is_deleted",0)->where("users.user_role_id",DOCTOR_ROLE_ID)->where("users.first_name","!=","")->select("users.id","users.first_name","users.last_name","users.image","hcp_details.year_of_graduation","hcp_details.bio","hcp_specialities.sub_category_id","hcp_categories.category_id","hcp_services.services_id","hcp_consultaion.consultaion_id");
				
					if (!empty($request->input('gender'))) {
						$DB->where("users.gender",$request->input('gender'));
					}
					if (!empty($request->input('gender'))) {
						$DB->where("users.gender",$request->input('gender'));
					}
					if (!empty($request->input('category_id'))) {
						$DB->where('hcp_categories.category_id',$request->input('category_id'));
					}
					if (!empty($request->input('area'))) {
						$DB->where('hcp_clinics.area',$request->input('area'));
					}
					if (!empty($request->input('district'))) {
						$DB->where('hcp_clinics.district',$request->input('district'));
					}
					if (!empty($request->input('keyword'))) {
						$keyword  = $request->input('keyword');
						$DB->where(function ($query) use($keyword){
							$query->Orwhere("users.first_name",'like','%'.$keyword.'%');
							$query->Orwhere("users.last_name",'like','%'.$keyword.'%');
							//$query->Orwhere("users.professional_email",'like','%'.$keyword.'%');
							$query->Orwhere("hcp_details.bio",'like','%'.$keyword.'%');
						});
					}
					if (!empty($request->input('service_type'))) {
						$service_type	=	explode(",",$request->input('service_type'));
						$DB->whereIn('hcp_consultaion.consultaion_id',$service_type);
					}
					if (!empty($request->input('services'))) {
						$services	=	explode(",",$request->input('services'));
						$DB->whereIn('hcp_services.services_id',$services);
					}
					if (!empty($request->input('sub_category'))) {
						$sub_category	=	explode(",",$request->input('sub_category'));
						$DB->whereIn('hcp_specialities.sub_category_id',$sub_category);
					}
				
				
				$hcps		=	$DB->orderBy("users.first_name","ASC")->groupBy("users.id")->paginate($records_per_page);


				if(!empty($hcps)){
					foreach($hcps as &$user_details){
						if(!empty($user_details->image) && file_exists(USER_IMAGE_ROOT_PATH.$user_details->image)){
							$user_details->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$user_details->image;
						}else{
							$user_details->image = WEBSITE_IMG_URL.'noimage.png';
						}
						$specialities     = DB::table('hcp_qualifications')->leftjoin('lookups', 'lookups.id', '=', 'hcp_qualifications.qualification')->where('hcp_qualifications.hcp_id',$user_details->id)->leftJoin('lookup_descriptions', 'lookup_descriptions.parent_id', '=', 'lookups.id')->where("lookup_descriptions.language_id",$language_id)->select("lookup_descriptions.code as name")->get()->toArray();

						$user_details->qualifications	=	$specialities;

						$hcp_consulation = 	DB::table('hcp_consultaion')->leftjoin('lookups', 'lookups.id', '=', 'hcp_consultaion.consultaion_id')->where('hcp_consultaion.hcp_id',$user_details->id)->leftJoin('lookup_descriptions', 'lookup_descriptions.parent_id', '=', 'lookups.id')->where("lookup_descriptions.language_id",$language_id)->select("lookup_descriptions.code as name")->get()->toArray();
						$user_details->consulations	=	$hcp_consulation;
						
						$service_fees	=	DB::table("hcp_consultaion")->where("hcp_consultaion.hcp_id",$user_details->id)->orderBy("hcp_consultaion.service_fees","ASC")->value("service_fees");
						if($service_fees == ""){
							$service_fees	=	0;
						}

						$year_of_graduation	=	$user_details->year_of_graduation;
						if(is_numeric($year_of_graduation)){
							if(date("Y") > $year_of_graduation){
								$total_experience	=	date("Y")-$year_of_graduation;
							}else {
								$total_experience	=	0;
							}
						}else {
							$total_experience	=	0;
						}
						$user_details->total_experience	=	$total_experience;
						$user_details->service_fees		=	"$".$service_fees;
						$user_details->total_reviews	=	0;
						
					}
				}


				$response["status"]		=	"success";
				$response["msg"]		=	"";
				$response["data"]		=	$hcps;
				return json_encode($response);
			}
		}else {
			$response["status"]		=	"error";
			$response["msg"]		=	trans("messages.Invalid_Request");
			$response["data"]		=	(object)array();
		}
		return json_encode($response);
	}


	public function viewHcpProfile(Request $request,$healthcare_id) {
		$lang			=	App::getLocale();
		$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");
		$model = DB::table('users')
			->leftjoin('hcp_details', 'users.id', '=', 'hcp_details.hcp_id')
			->leftjoin('countries', 'countries.id', '=', 'hcp_details.country')
			// ->leftjoin('area_descriptions as per_areas', 'hcp_details.area', '=', 'per_areas.parent_id')
			// ->where('per_areas.language_id',$language_id)
			// ->leftjoin('districts as per_districts', 'hcp_details.district', '=', 'per_districts.id')
			// ->leftjoin('building_names as per_building_names', 'hcp_details.building_name', '=', 'per_building_names.id')
			 ->leftjoin('hcp_clinics', 'users.id', '=', 'hcp_clinics.hcp_id')
			 ->leftjoin('countries as clinic_country', 'clinic_country.id', '=', 'hcp_clinics.country')
			// ->leftjoin('areas', 'hcp_clinics.area', '=', 'areas.id')
			// ->leftjoin('districts', 'hcp_clinics.district', '=', 'districts.id')
			// ->leftjoin('building_names', 'hcp_clinics.building_name', '=', 'building_names.id')
			->leftjoin('hcp_categories', 'users.id', '=', 'hcp_categories.hcp_id')
			->leftjoin('categories', 'hcp_categories.category_id', '=', 'categories.id')
			->leftjoin('hcp_specialities', 'users.id', '=', 'hcp_specialities.hcp_id')
			->select('users.*','hcp_details.professional_email as email','hcp_details.professional_phone_number_prefix as phone_number_prefix','hcp_details.professional_phone_number_country_code as phone_number_country_code','hcp_details.professional_email','hcp_details.professional_phone_number_prefix','hcp_details.professional_phone_number_country_code','hcp_details.professional_phone_number','hcp_details.professional_phone_number as phone_number','hcp_details.bio','hcp_details.country','hcp_details.address_line_1','hcp_details.address_line_2','hcp_details.address_line_3','hcp_details.area','hcp_details.district','hcp_details.building_name','hcp_details.year_of_graduation','hcp_categories.category_id','hcp_specialities.sub_category_id','hcp_clinics.country as hcp_clinic_countryname','hcp_clinics.address_line_1 as hcp_address_line_1','hcp_clinics.address_line_2 as hcp_address_line_2','hcp_clinics.address_line_3 as hcp_address_line_3','countries.name as country_name','clinic_country.name as clinic_country_name','categories.name as category_name',DB::raw("(select name from area_descriptions where parent_id=hcp_clinics.area and language_id='$language_id') as clinic_area"),DB::raw("(select name from area_descriptions where parent_id=hcp_clinics.area and language_id='$language_id') as hcp_area"),DB::raw("(select name from district_descriptions where parent_id=hcp_clinics.district and language_id='$language_id') as clinic_district"),DB::raw("(select name from district_descriptions where parent_id=hcp_clinics.district and language_id='$language_id') as hcp_district"),DB::raw("(select name from building_name_descriptions where parent_id=hcp_clinics.building_name and language_id='$language_id') as hcp_building_name"),DB::raw("(select name from building_name_descriptions where parent_id=hcp_clinics.building_name and language_id='$language_id') as clinic_building_name"))
			->where('users.id', $healthcare_id)
			->first();
		if($model->gender == "male" && $lang == 'en'){
			$model->gender = "Male";
		}else if($model->gender == "female" && $lang == 'en'){
			$model->gender = "Female";
		}else if($model->gender == "other" && $lang == 'en'){
			$model->gender = "Other";
		}else if($model->gender == "male" && $lang == 'ch'){
			$model->gender = "男性";
		}else if($model->gender == "female" && $lang == 'ch'){
			$model->gender = "女性";
		}else if($model->gender == "other" && $lang == 'ch'){
			$model->gender = "其他";
		}	




		if(empty($model)){
			$response["status"]		=	"error";
			$response["msg"]		=	trans("messages.Invalid_Request");
			$response["data"]		=	(object)array();
			return json_encode($response);
		}

		$year_of_graduation	=	$model->year_of_graduation;
		if(is_numeric($year_of_graduation)){
			if(date("Y") > $year_of_graduation){
				$total_experience	=	date("Y")-$year_of_graduation;
			}else {
				$total_experience	=	0;
			}
		}else {
			$total_experience	=	0;
		}
		$model->total_experience	=	$total_experience;

		if(!empty($model->image) && file_exists(USER_IMAGE_ROOT_PATH.$model->image)){
			$model->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$model->image;
		}else{
			$model->image = WEBSITE_IMG_URL.'noimage.png';
		}
		$affiliatedHospitals  = AffiliatedHospital::where('hcp_id',$model->id)->get();

		$qualifications  = DB::table('hcp_qualifications')->where('hcp_id',$healthcare_id)->leftjoin('lookup_descriptions','lookup_descriptions.parent_id','hcp_qualifications.qualification')->where('lookup_descriptions.language_id',$language_id)->select('hcp_qualifications.*','lookup_descriptions.code as qualification_name')->get();

		$private_hospital  = DB::table('hcp_affiliated_private_hospital')->where('hcp_id',$healthcare_id)->leftjoin('lookup_descriptions','lookup_descriptions.parent_id','hcp_affiliated_private_hospital.affliated_hospital_id')->where('lookup_descriptions.language_id',$language_id)->select('hcp_affiliated_private_hospital.*','lookup_descriptions.code as private_hospital_name')->get();
	
		$hcp_specialities     = DB::table('hcp_specialities')->where('hcp_id',$healthcare_id)->get();
	
		$sub_category = DB::table('hcp_specialities')
			->leftjoin('sub_categories_descriptions', 'sub_categories_descriptions.parent_id', '=', 'hcp_specialities.sub_category_id')
			->where('sub_categories_descriptions.language_id',$language_id)
			->select('hcp_specialities.*','sub_categories_descriptions.name as sub_name')
			->where('hcp_specialities.hcp_id', $healthcare_id)
			->get()->toArray();

		$services = 	HcpServices::where('hcp_id',$model->id)->leftjoin('lookup_descriptions','lookup_descriptions.parent_id','hcp_services.services_id')->where('lookup_descriptions.language_id',$language_id)->where('lookup_descriptions.language_id',$language_id)->pluck('lookup_descriptions.code as services')->toArray();

		$Consulation = 	HcpConsulation::where('hcp_id',$model->id)->leftjoin('lookup_descriptions','lookup_descriptions.parent_id','hcp_consultaion.consultaion_id')->where('lookup_descriptions.language_id',$language_id)->leftjoin('lookup_descriptions as lookup_descriptions2','lookup_descriptions2.parent_id','hcp_consultaion.time_duration')->where('lookup_descriptions2.language_id',$language_id)->select('hcp_consultaion.*','lookup_descriptions.code as consultaion_name','lookup_descriptions2.code as timeduration')->get();
	
		$certificates   = Certification::where('hcp_id',$model->id)->where('is_private',0)->select('id','image','name','is_private')->get();
		if($certificates->isNotEmpty()){
			foreach($certificates as $certificate){
				if(!empty($certificate->image) && file_exists(USER_IMAGE_ROOT_PATH.$certificate->image)){
					$certificate->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$certificate->image;
				}else{
					$certificate->image		 = "";
				}
			}
		}
		
		$schedules  = WorkingHour::where('user_id',$healthcare_id)
						->select("week_name","open_time","close_time","consultation_method")->get()->toArray();

		$res_data	= HcpConsulation::where('hcp_id',$healthcare_id)->select('consultaion_id','time_duration','service_fees')->get();

		$daysArr = array("0"=>"monday", "1"=>"tuesday", "2"=>"wednesday", "3"=>"thursday", "4"=>"friday", "5"=>"saturday", "6"=>"sunday" );
		
		$Daysarray = array();
		
		foreach($daysArr as $key=>$value){
			$Daysarray[$key]['days'] = $value;
			$Daysarray[$key]['time'] =  WorkingHour::where('user_id',$healthcare_id)->where('week_name',$value)
			->select("week_name","open_time","close_time","consultation_method")->get()->toArray();
		}

		$doctorHoliday		= HcpHoliday::where('user_id',$healthcare_id)->get();

		$response_arr							=	array();
		$response_arr["detail"]					=	$model;
		$response_arr["affiliatedHospitals"]	=	$affiliatedHospitals;
		$response_arr["qualifications"]			=	$qualifications;
		$response_arr["sub_category"]			=	$sub_category;
		$response_arr["certificates"]			=	$certificates;
		$response_arr["services"]				=	$services;
		$response_arr["Consulation"]			=	$Consulation;
		$response_arr["private_hospital"]		=	$private_hospital;
		$response_arr["daysArr"]				=	$daysArr;
		$response_arr["schedules"]				=	$schedules;
		$response_arr["HcpConsulation"]			=	$res_data;
		$response_arr["Daysarray"]				=	$Daysarray;
		$response_arr["doctorHoliday"]			=	$doctorHoliday;

		$response["status"]		=	"success";
		$response["msg"]		=	"";
		$response["data"]		=	$response_arr;
		return json_encode($response);
	}
	public function BookingDetailhcp(Request $request,$healthcare_id) {
		$lang			=	App::getLocale();
		$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");
		$model = DB::table('users')
			->leftjoin('hcp_details', 'users.id', '=', 'hcp_details.hcp_id')
			->leftjoin('countries', 'countries.id', '=', 'hcp_details.country')
			// ->leftjoin('area_descriptions as per_areas', 'hcp_details.area', '=', 'per_areas.parent_id')
			// ->where('per_areas.language_id',$language_id)
			// ->leftjoin('districts as per_districts', 'hcp_details.district', '=', 'per_districts.id')
			// ->leftjoin('building_names as per_building_names', 'hcp_details.building_name', '=', 'per_building_names.id')
			 ->leftjoin('hcp_clinics', 'users.id', '=', 'hcp_clinics.hcp_id')
			 ->leftjoin('countries as clinic_country', 'clinic_country.id', '=', 'hcp_clinics.country')
			// ->leftjoin('areas', 'hcp_clinics.area', '=', 'areas.id')
			// ->leftjoin('districts', 'hcp_clinics.district', '=', 'districts.id')
			// ->leftjoin('building_names', 'hcp_clinics.building_name', '=', 'building_names.id')
			->leftjoin('hcp_categories', 'users.id', '=', 'hcp_categories.hcp_id')
			->leftjoin('categories', 'hcp_categories.category_id', '=', 'categories.id')
			->leftjoin('hcp_specialities', 'users.id', '=', 'hcp_specialities.hcp_id')
			->select('users.*','hcp_details.professional_email as email','hcp_details.professional_phone_number_prefix as phone_number_prefix','hcp_details.professional_phone_number_country_code as phone_number_country_code','hcp_details.professional_email','hcp_details.professional_phone_number_prefix','hcp_details.professional_phone_number_country_code','hcp_details.professional_phone_number','hcp_details.professional_phone_number as phone_number','hcp_details.bio','hcp_details.country','hcp_details.address_line_1','hcp_details.address_line_2','hcp_details.address_line_3','hcp_details.area','hcp_details.district','hcp_details.building_name','hcp_details.year_of_graduation','hcp_categories.category_id','hcp_specialities.sub_category_id','hcp_clinics.country as hcp_clinic_countryname','hcp_clinics.address_line_1 as hcp_address_line_1','hcp_clinics.address_line_2 as hcp_address_line_2','hcp_clinics.address_line_3 as hcp_address_line_3','countries.name as country_name','clinic_country.name as clinic_country_name','categories.name as category_name',DB::raw("(select name from area_descriptions where parent_id=hcp_clinics.area and language_id='$language_id') as clinic_area"),DB::raw("(select name from area_descriptions where parent_id=hcp_clinics.area and language_id='$language_id') as hcp_area"),DB::raw("(select name from district_descriptions where parent_id=hcp_clinics.district and language_id='$language_id') as clinic_district"),DB::raw("(select name from district_descriptions where parent_id=hcp_clinics.district and language_id='$language_id') as hcp_district"),DB::raw("(select name from building_name_descriptions where parent_id=hcp_clinics.building_name and language_id='$language_id') as hcp_building_name"),DB::raw("(select name from building_name_descriptions where parent_id=hcp_clinics.building_name and language_id='$language_id') as clinic_building_name"))
			->where('users.id', $healthcare_id)
			->first();
		if($model->gender == "male" && $lang == 'en'){
			$model->gender = "Male";
		}else if($model->gender == "female" && $lang == 'en'){
			$model->gender = "Female";
		}else if($model->gender == "other" && $lang == 'en'){
			$model->gender = "Other";
		}else if($model->gender == "male" && $lang == 'ch'){
			$model->gender = "男性";
		}else if($model->gender == "female" && $lang == 'ch'){
			$model->gender = "女性";
		}else if($model->gender == "other" && $lang == 'ch'){
			$model->gender = "其他";
		}	

		if(empty($model)){
			$response["status"]		=	"error";
			$response["msg"]		=	trans("messages.Invalid_Request");
			$response["data"]		=	(object)array();
			return json_encode($response);
		}

		if(!empty($model->image) && file_exists(USER_IMAGE_ROOT_PATH.$model->image)){
			$model->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$model->image;
		}else{
			$model->image = WEBSITE_IMG_URL.'noimage.png';
		}
		$affiliatedHospitals  = AffiliatedHospital::where('hcp_id',$model->id)->get();

		$qualifications  = DB::table('hcp_qualifications')->where('hcp_id',$healthcare_id)->leftjoin('lookup_descriptions','lookup_descriptions.parent_id','hcp_qualifications.qualification')->where('lookup_descriptions.language_id',$language_id)->select('hcp_qualifications.*','lookup_descriptions.code as qualification_name')->get();

		$private_hospital  = DB::table('hcp_affiliated_private_hospital')->where('hcp_id',$healthcare_id)->leftjoin('lookup_descriptions','lookup_descriptions.parent_id','hcp_affiliated_private_hospital.affliated_hospital_id')->where('lookup_descriptions.language_id',$language_id)->select('hcp_affiliated_private_hospital.*','lookup_descriptions.code as private_hospital_name')->get();
	
		$hcp_specialities     = DB::table('hcp_specialities')->where('hcp_id',$healthcare_id)->get();
	
		$sub_category = DB::table('hcp_specialities')
			->leftjoin('sub_categories_descriptions', 'sub_categories_descriptions.parent_id', '=', 'hcp_specialities.sub_category_id')
			->where('sub_categories_descriptions.language_id',$language_id)
			->select('hcp_specialities.*','sub_categories_descriptions.name as sub_name')
			->where('hcp_specialities.hcp_id', $healthcare_id)
			->get()->toArray();

		$services = 	HcpServices::where('hcp_id',$model->id)->leftjoin('lookup_descriptions','lookup_descriptions.parent_id','hcp_services.services_id')->where('lookup_descriptions.language_id',$language_id)->where('lookup_descriptions.language_id',$language_id)->pluck('lookup_descriptions.code as services')->toArray();

		$Consulation = 	HcpConsulation::where('hcp_id',$model->id)->leftjoin('lookup_descriptions','lookup_descriptions.parent_id','hcp_consultaion.consultaion_id')->where('lookup_descriptions.language_id',$language_id)->leftjoin('lookup_descriptions as lookup_descriptions2','lookup_descriptions2.parent_id','hcp_consultaion.time_duration')->where('lookup_descriptions2.language_id',$language_id)->select('hcp_consultaion.*','lookup_descriptions.code as consultaion_name','lookup_descriptions2.code as timeduration')->get();
	
		$certificates   = Certification::where('hcp_id',$model->id)->where('is_private',0)->select('id','image','name','is_private')->get();
		if($certificates->isNotEmpty()){
			foreach($certificates as $certificate){
				if(!empty($certificate->image) && file_exists(USER_IMAGE_ROOT_PATH.$certificate->image)){
					$certificate->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$certificate->image;
				}else{
					$certificate->image		 = "";
				}
			}
		}
		
		$schedules  = WorkingHour::where('user_id',$healthcare_id)
						->select("week_name","open_time","close_time","consultation_method")->get()->toArray();

		$res_data	= HcpConsulation::where('hcp_id',$healthcare_id)->select('consultaion_id','time_duration','service_fees')->get();

		$daysArr = array("0"=>"monday", "1"=>"tuesday", "2"=>"wednesday", "3"=>"thursday", "4"=>"friday", "5"=>"saturday", "6"=>"sunday" );
		
		$Daysarray = array();
		
		foreach($daysArr as $key=>$value){
			$Daysarray[$key]['days'] = $value;
			$Daysarray[$key]['time'] =  WorkingHour::where('user_id',$healthcare_id)->where('week_name',$value)
			->select("week_name","open_time","close_time","consultation_method")->get()->toArray();
		}

		$doctorHoliday		= HcpHoliday::where('user_id',$healthcare_id)->get();

		$response_arr							=	array();
		$response_arr["detail"]					=	$model;
		$response_arr["affiliatedHospitals"]	=	$affiliatedHospitals;
		$response_arr["qualifications"]			=	$qualifications;
		$response_arr["sub_category"]			=	$sub_category;
		$response_arr["certificates"]			=	$certificates;
		$response_arr["services"]				=	$services;
		$response_arr["Consulation"]			=	$Consulation;
		$response_arr["private_hospital"]		=	$private_hospital;
		$response_arr["daysArr"]				=	$daysArr;
		$response_arr["schedules"]				=	$schedules;
		$response_arr["HcpConsulation"]			=	$res_data;
		$response_arr["Daysarray"]				=	$Daysarray;
		$response_arr["doctorHoliday"]			=	$doctorHoliday;

		$response["status"]		=	"success";
		$response["msg"]		=	"";
		$response["data"]		=	$response_arr;
		return json_encode($response);
	}

	public function getAvailability(Request $request){
		$lang		=	App::getLocale();
		$langId	=	DB::table("languages")->where("lang_code",$lang)->value("id");
		$formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$request->replace($this->arrayStripTags($request->all()));
			$validator = Validator::make(
				$request->all(),
				array(
					'date' 			=> 'required',
					'hcp_id' 			=> 'required',
				),
				array(
					"date.required"		 	=> trans("messages.The_date_is_required"),
					"hcp_id.required"		 	=> trans("messages.The_hcp_id_is_required"),
				)
			);
			if ($validator->fails()){
				$response		=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$date = $request->date;
				$hcp_id = $request->hcp_id;

				$day = date('l', strtotime($date));
				$timeSlotArray = [];
				$finalTimeSlotArray = [];
				$getHolidaysData = HcpHoliday::where('user_id',$hcp_id)->where('date',$date)->first();
				if(!empty($getHolidaysData) && $getHolidaysData->close_for_all_day == 1){
					$response["status"]		=	"success";
					$response["msg"]		=	'';
					$response["data"]		=	array();
					return json_encode($response);
				}else if(!empty($getHolidaysData) && $getHolidaysData->close_for_all_day == 0){
					$getHolidayTImeData = HcpHolidayTime::where('hcp_holiday_id',$getHolidaysData->id)->where('user_id',$hcp_id)->where('date',$date)->get()->toArray();
					

				}
				
			
			
				$getWorkingHoursData = WorkingHour::where('user_id',$hcp_id)->where('week_name',strtolower($day))->get()->toArray();
				if(!empty($getWorkingHoursData)){
					$count = 0;
					foreach($getWorkingHoursData as $workingHour){
						// $consultationMethod = explode(',',$workingHour['consultation_method']);
						// $consultationNameArray = [];
						// if(!empty($consultationMethod)){
						// 	foreach($consultationMethod as $methodVal){
						// 		$consultation           = DB::table('lookups')->where('id',$methodVal)->value('code');
						// 		$consultationNameArray[] = $consultation;
						// 	}
						// }

						// $consultationMethod = implode(',',$consultationNameArray);
					
						// $timeSlotArray[$count]['consultation']  = !empty($consultationMethod) ? $consultationMethod : '';

						$consultation           = DB::table('lookup_descriptions')->where('language_id',$langId)->whereIn('parent_id',explode(',',$workingHour['consultation_method']))->select('parent_id as id','code')->get()->toArray();
						
						$timeSlotArray[$count]['consultation']  = !empty($consultation) ? $consultation : '';

						$getTimeInterval = HcpConsulation::leftJoin('lookups','lookups.id','hcp_consultaion.time_duration')->where('hcp_consultaion.hcp_id',$hcp_id)->whereIn('hcp_consultaion.consultaion_id',explode(',',$workingHour['consultation_method']))->max('lookups.code');
						
						if(empty($getTimeInterval)){
							$getTimeInterval = '15';
						}
						
						$timeSlotArray[$count]['slots'] = $this->getTimeSlot($date,$getTimeInterval, $workingHour['open_time'],$workingHour['close_time']);
						if(!empty($getHolidayTImeData)){
							$slotsArray = [];
							
							$sCount = 0;
							foreach($getHolidayTImeData as $holidayTime){
								if(!empty($timeSlotArray[$count]['slots'])){
									foreach($timeSlotArray[$count]['slots'] as $slotKey => $slotVal){
										$slotsToRemove = [];
										if($slotVal >= $holidayTime['start_time'] && $slotVal <= $holidayTime['end_time'] ){
											unset($timeSlotArray[$count]['slots'][$slotKey]);
										}
									}
								}
								
							}
							
						}

							 
						
						$count++;
					}
				}
				
				$morningSlotsArray = [];
				$afternoonSlotsArray = []; 
				$eveningSlotsArray = [];

				// print_r($timeSlotArray);die;
				if(!empty($timeSlotArray)){
					foreach($timeSlotArray as $timeSlot){
						if(!empty($timeSlot['slots'])){
							foreach($timeSlot['slots'] as $slotVal){
								if(strtotime($slotVal) >= strtotime('06:00') && strtotime($slotVal) <= strtotime('12:00') ){
									$morningSlotsArray[] = ['time' => $slotVal ,'time_string' => date('h:i A',strtotime($slotVal)), 'consultation' =>$timeSlot['consultation']  ];
								}else if(strtotime($slotVal) >= strtotime('12:00') && strtotime($slotVal) <= strtotime('16:00') ){
									$afternoonSlotsArray[] = ['time' => $slotVal ,'time_string' => date('h:i A',strtotime($slotVal)), 'consultation' =>$timeSlot['consultation']  ];
								}else{
									$eveningSlotsArray[] = ['time' => $slotVal ,'time_string' => date('h:i A',strtotime($slotVal)), 'consultation' =>$timeSlot['consultation']  ];
								}
							}
						}
					}

				}

				$response["status"]		=	"success";
				$response["msg"]		=	trans("messages.Availability_Data_Found");
				$response["morning_slots"]		=	$morningSlotsArray;
				$response["afternoon_slots"]	=	$afternoonSlotsArray;
				$response["evening_slots"]		=	$eveningSlotsArray;
				
			
				
			}
		}else {
			$response["status"]		=	"error";
			$response["msg"]		=	trans("messages.Invalid_Request");
			$response["data"]		=	(object)array();
		}
		return json_encode($response);
	}

	function getTimeSlot($date,$interval, $StartTime , $EndTime)
	{	
		$timeData = array ();// Define output
		$StartTime = $this->blockMinutesRound($StartTime);

		$start_time    = strtotime ($StartTime); //Get Timestamp
	
		$end_time      = strtotime ($EndTime); //Get Timestamp
			if($EndTime == '' || $EndTime == null || $EndTime == 'undefined'){
				$end_time  =  strtotime("+30 minutes",strtotime($StartTime));
			}
			if(date("H:i:s",strtotime($EndTime)) == "00:00:00"){
				
				$newTime      = date("Y-m-d",strtotime($EndTime))." "."23:59:00";
				$end_time      = strtotime ($newTime);
			}
			
			$AddMins  = $interval * 60;
			
			while ($start_time < $end_time) //Run loop
			{
				
				if($date == date('Y-m-d')){
					if($StartTime >= date('H:i')){
						$timeData[] = date ("H:i",$start_time);
					}
	
				}else{
					$timeData[] = date ("H:i",$start_time);
				}
				
				$start_time += $AddMins; //Endtime check

				
			}
		// }
		
		return $timeData;
	}

	function blockMinutesRound($hour, $minutes = '5', $format = "H:i") {
		$seconds = strtotime($hour);
		$rounded = round($seconds / ($minutes * 60)) * ($minutes * 60);
		return date($format, $rounded);
	 }

	function acknowledgeAppointments(){
		$lang				=	App::getLocale();
		$language_id		=	DB::table("languages")->where("lang_code",$lang)->value("id");
		$records_per_page	=	Config::get('Reading.records_per_page');
		$appointments		=	DB::table('bookings')
								->where('bookings.hcp_id',Auth::guard('api')->user()->id)
								->leftJoin('lookup_descriptions as consultation','consultation.parent_id','bookings.consultation_method')
								->where("language_id",$language_id)
								->leftJoin('users','bookings.patient_id','users.id')
								->where('bookings.status','accepted')
								// ->where('bookings.booking_date_time','>=',date('Y-m-d H:i:s'))
								->select('bookings.id','bookings.booking_date','bookings.booking_time','bookings.booking_date_time','bookings.consultation_method as consultation_id','consultation.code as consultation_method','users.name','users.image','bookings.status')
								->orderBy("bookings.booking_date_time","ASC")
								->groupBy("bookings.id")
								->paginate($records_per_page);
		if($appointments->isNotEmpty()){
			foreach($appointments as $appVal){
				$appVal->booking_time = date('h:i A',strtotime($appVal->booking_time));
				$appVal->booking_date = date('d M, Y',strtotime($appVal->booking_date));

				if(!empty($appVal->image) && file_exists(USER_IMAGE_ROOT_PATH.$appVal->image)){
					$appVal->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$appVal->image;
				}else{
					$appVal->image = WEBSITE_IMG_URL.'noimage.png';
				}

			}
		}
		$response["status"]		=	"success";
		$response["msg"]		=	trans("messages.Appointment_data_found");
		$response["data"]		=	$appointments;
		return json_encode($response);

	 }
	 function requestedAppointments(){
		$lang			=	App::getLocale();
		$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");
		$records_per_page	=	Config::get('Reading.records_per_page');
		$appointments		=	DB::table('bookings')->where('bookings.hcp_id',Auth::guard('api')->user()->id)->leftJoin('lookup_descriptions as consultation','consultation.parent_id','bookings.consultation_method')->where("language_id",$language_id)->leftJoin('users','bookings.patient_id','users.id')->where('bookings.status','pending')->select('bookings.id','bookings.booking_date','bookings.booking_time','bookings.booking_date_time','bookings.consultation_method as consultation_id','consultation.code as consultation_method','users.name','users.image','bookings.status')->orderBy("bookings.booking_date_time","ASC")->groupBy("bookings.id")->paginate($records_per_page);
		if($appointments->isNotEmpty()){
			foreach($appointments as $appVal){
				$appVal->booking_time = date('h:i A',strtotime($appVal->booking_time));
				$appVal->booking_date = date('d M, Y',strtotime($appVal->booking_date));

				if(!empty($appVal->image) && file_exists(USER_IMAGE_ROOT_PATH.$appVal->image)){
					$appVal->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$appVal->image;
				}else{
					$appVal->image = WEBSITE_IMG_URL.'noimage.png';
				}

			}
		}
		$response["status"]		=	"success";
		$response["msg"]		=	trans("messages.Appointment_data_found");
		$response["data"]		=	$appointments;
		return json_encode($response);

	 }
	 function completedAppointments(){
		$lang			=	App::getLocale();
		$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");
		$records_per_page	=	Config::get('Reading.records_per_page');
		$DB		=	DB::table('bookings')->where('bookings.hcp_id',Auth::guard('api')->user()->id)->leftJoin('lookup_descriptions as consultation','consultation.parent_id','bookings.consultation_method')->where("language_id",$language_id)->leftJoin('users','bookings.patient_id','users.id');
		
		$DB->where(static function ($query) {
			$query->where('bookings.status','completed')
				->orWhere([['bookings.status', 'accepted'],['bookings.booking_date_time','<=', date('Y-m-d H:i:s')]]);
				
		});
		$appointments = $DB->select('bookings.id','bookings.booking_date','bookings.booking_time','bookings.booking_date_time','bookings.consultation_method as consultation_id','consultation.code as consultation_method','users.name','users.image','bookings.status')->orderBy("bookings.booking_date_time","ASC")->groupBy("bookings.id")->paginate($records_per_page);
		
		

		if($appointments->isNotEmpty()){
			foreach($appointments as $appVal){
				$appVal->booking_time = date('h:i A',strtotime($appVal->booking_time));
				$appVal->booking_date = date('d M, Y',strtotime($appVal->booking_date));

				if(!empty($appVal->image) && file_exists(USER_IMAGE_ROOT_PATH.$appVal->image)){
					$appVal->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$appVal->image;
				}else{
					$appVal->image = WEBSITE_IMG_URL.'noimage.png';
				}

			}
		}
		$response["status"]		=	"success";
		$response["msg"]		=	trans("messages.Appointment_data_found");
		$response["data"]		=	$appointments;
		return json_encode($response);

	 }
	 function cancelledAppointments(){
		$lang			=	App::getLocale();
		$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");
		$records_per_page	=	Config::get('Reading.records_per_page');
		$appointments		=	DB::table('bookings')->where('bookings.hcp_id',Auth::guard('api')->user()->id)->leftJoin('lookup_descriptions as consultation','consultation.parent_id','bookings.consultation_method')->where("language_id",$language_id)->leftJoin('users','bookings.patient_id','users.id')->where('users.user_type','patient')->where('bookings.status','cancelled')->select('bookings.id','bookings.booking_date','bookings.booking_time','bookings.booking_date_time','bookings.consultation_method as consultation_id','consultation.code as consultation_method','users.name','users.image','bookings.status')->orderBy("bookings.booking_date_time","ASC")->groupBy("bookings.id")->paginate($records_per_page);
		if($appointments->isNotEmpty()){
			foreach($appointments as $appVal){
				$appVal->booking_time = date('h:i A',strtotime($appVal->booking_time));
				$appVal->booking_date = date('d M, Y',strtotime($appVal->booking_date));

				if(!empty($appVal->image) && file_exists(USER_IMAGE_ROOT_PATH.$appVal->image)){
					$appVal->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$appVal->image;
				}else{
					$appVal->image = WEBSITE_IMG_URL.'noimage.png';
				}

			}
		}
		$response["status"]		=	"success";
		$response["msg"]		=	trans("messages.Appointment_data_found");
		$response["data"]		=	$appointments;
		return json_encode($response);

	 }
	 function acknowledgeAppointmentsPatient(){
		$lang			=	App::getLocale();
		$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");
		$records_per_page	=	Config::get('Reading.records_per_page');
		$appointments		=	DB::table('bookings')
		->where('bookings.patient_id',Auth::guard('api')->user()->id)
		->leftJoin('lookup_descriptions as consultation','consultation.parent_id','bookings.consultation_method')
		->where("language_id",$language_id)
		->leftJoin('users','bookings.hcp_id','users.id')->where('bookings.status','accepted')
		// ->where('bookings.booking_date_time','>=',date('Y-m-d H:i:s'))
		->select('bookings.id','bookings.booking_date','bookings.booking_time','bookings.booking_date_time','bookings.consultation_method as consultation_id','consultation.code as consultation_method','users.name','users.image','bookings.status')
		->orderBy("bookings.booking_date_time","ASC")
		->groupBy("bookings.id")
		->paginate($records_per_page);
		if($appointments->isNotEmpty()){
			foreach($appointments as $appVal){
				$appVal->booking_time = date('h:i A',strtotime($appVal->booking_time));
				$appVal->booking_date = date('d M, Y',strtotime($appVal->booking_date));

				if(!empty($appVal->image) && file_exists(USER_IMAGE_ROOT_PATH.$appVal->image)){
					$appVal->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$appVal->image;
				}else{
					$appVal->image = WEBSITE_IMG_URL.'noimage.png';
				}

			}
		}
		$response["status"]		=	"success";
		$response["msg"]		=	trans("messages.Appointment_data_found");
		$response["data"]		=	$appointments;
		return json_encode($response);

	 }
	 function requestedAppointmentsPatient(){
		$lang			=	App::getLocale();
		$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");
		$records_per_page	=	Config::get('Reading.records_per_page');
		$appointments		=	DB::table('bookings')->where('bookings.patient_id',Auth::guard('api')->user()->id)->leftJoin('lookup_descriptions as consultation','consultation.parent_id','bookings.consultation_method')->where("language_id",$language_id)->leftJoin('users','bookings.hcp_id','users.id')->where('bookings.status','pending')->select('bookings.id','bookings.booking_date','bookings.booking_time','bookings.booking_date_time','bookings.consultation_method as consultation_id','consultation.code as consultation_method','users.name','users.image','bookings.status')->orderBy("bookings.booking_date_time","ASC")->groupBy("bookings.id")->paginate($records_per_page);
		if($appointments->isNotEmpty()){
			foreach($appointments as $appVal){
				$appVal->booking_time = date('h:i A',strtotime($appVal->booking_time));
				$appVal->booking_date = date('d M, Y',strtotime($appVal->booking_date));

				if(!empty($appVal->image) && file_exists(USER_IMAGE_ROOT_PATH.$appVal->image)){
					$appVal->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$appVal->image;
				}else{
					$appVal->image = WEBSITE_IMG_URL.'noimage.png';
				}

			}
		}
		$response["status"]		=	"success";
		$response["msg"]		=	trans("messages.Appointment_data_found");
		$response["data"]		=	$appointments;
		return json_encode($response);

	 }
	 function completedAppointmentsPatient(){
		$lang			=	App::getLocale();
		$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");
		$records_per_page	=	Config::get('Reading.records_per_page');
		$DB		=	DB::table('bookings')->where('bookings.patient_id',Auth::guard('api')->user()->id)->leftJoin('lookup_descriptions as consultation','consultation.parent_id','bookings.consultation_method')->where("language_id",$language_id)->leftJoin('users','bookings.hcp_id','users.id');
		
		$DB->where(static function ($query) {
			$query->where('bookings.status','completed')
				->orWhere([['bookings.status', 'accepted'],['bookings.booking_date_time','<=', date('Y-m-d H:i:s')]]);
				
		});
		$appointments = $DB->select('bookings.id','bookings.booking_date','bookings.booking_time','bookings.booking_date_time','bookings.consultation_method as consultation_id','consultation.code as consultation_method','users.name','users.image','bookings.status')->orderBy("bookings.booking_date_time","ASC")->groupBy("bookings.id")->paginate($records_per_page);
		if($appointments->isNotEmpty()){
			foreach($appointments as $appVal){
				$appVal->booking_time = date('h:i A',strtotime($appVal->booking_time));
				$appVal->booking_date = date('d M, Y',strtotime($appVal->booking_date));

				if(!empty($appVal->image) && file_exists(USER_IMAGE_ROOT_PATH.$appVal->image)){
					$appVal->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$appVal->image;
				}else{
					$appVal->image = WEBSITE_IMG_URL.'noimage.png';
				}

			}
		}
		$response["status"]		=	"success";
		$response["msg"]		=	trans("messages.Appointment_data_found");
		$response["data"]		=	$appointments;
		return json_encode($response);

	 }
	 function cancelledAppointmentsPatient(){
		$lang			=	App::getLocale();
		$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");
		$records_per_page	=	Config::get('Reading.records_per_page');
		$appointments		=	DB::table('bookings')->where('bookings.patient_id',Auth::guard('api')->user()->id)->leftJoin('lookup_descriptions as consultation','consultation.parent_id','bookings.consultation_method')->where("language_id",$language_id)->leftJoin('users','bookings.hcp_id','users.id')->where('bookings.status','cancelled')->select('bookings.id','bookings.booking_date','bookings.booking_time','bookings.booking_date_time','bookings.consultation_method as consultation_id','consultation.code as consultation_method','users.name','users.image','bookings.status')->orderBy("bookings.booking_date_time","ASC")->groupBy("bookings.id")->paginate($records_per_page);
		if($appointments->isNotEmpty()){
			foreach($appointments as $appVal){
				$appVal->booking_time = date('h:i A',strtotime($appVal->booking_time));
				$appVal->booking_date = date('d M, Y',strtotime($appVal->booking_date));

				if(!empty($appVal->image) && file_exists(USER_IMAGE_ROOT_PATH.$appVal->image)){
					$appVal->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$appVal->image;
				}else{
					$appVal->image = WEBSITE_IMG_URL.'noimage.png';
				}

			}
		}
		$response["status"]		=	"success";
		$response["msg"]		=	trans("messages.Appointment_data_found");
		$response["data"]		=	$appointments;
		return json_encode($response);

	 }
	function acceptAppointment(Request $request){
		$lang			=	App::getLocale();
		$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");
		$formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$request->replace($this->arrayStripTags($request->all()));
			$validator = Validator::make(
				$request->all(),
				array(
					'appointment_id' 			=> 'required',
				),
				array(
					"appointment_id.required"		 	=> trans("messages.The_appointment_id_field_is_required"),
				)
			);
			if ($validator->fails()){
				$response		=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$appointment_id = $request->appointment_id;
				$checkIfAppointmentExists = DB::table('bookings')->where('bookings.hcp_id',Auth::guard('api')->user()->id)->where('bookings.id',$appointment_id)->first();
				if(!empty($checkIfAppointmentExists)){
					DB::table('bookings')->where('bookings.hcp_id',Auth::guard('api')->user()->id)->where('bookings.id',$appointment_id)->update(['status' => 'accepted']);

					$appointments		=	DB::table('bookings')
					->where("bookings.id",$appointment_id)
					->where('bookings.hcp_id',Auth::guard('api')->user()->id)
					->leftJoin('lookup_descriptions as consultation','consultation.parent_id','bookings.consultation_method')
					->where("language_id",$language_id)
					->leftJoin('users','bookings.hcp_id','users.id')
					->where('bookings.status','accepted')
					// ->where('bookings.booking_date_time','>=',date('Y-m-d H:i:s'))
					->select('bookings.id','bookings.booking_date','bookings.booking_time','bookings.booking_date_time','bookings.consultation_method as consultation_id','consultation.code as consultation_method','users.name','users.image','bookings.status')
					->first();
					if(!empty($appointments)){
						$appointments->booking_time = date('h:i A',strtotime($appointments->booking_time));
						$appointments->booking_date = date('d M, Y',strtotime($appointments->booking_date));

						if(!empty($appointments->image) && file_exists(USER_IMAGE_ROOT_PATH.$appointments->image)){
							$appointments->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$appointments->image;
						}else{
							$appointments->image = WEBSITE_IMG_URL.'noimage.png';
						}
					}
					$response["status"]		=	"success";
					$response["msg"]		=	trans("messages.Appointment_accepted_successfully");
					$response["data"]		=	$appointments;
				}else{
					$response["status"]		=	"error";
					$response["msg"]		=	trans("messages.Appointment_does_not_exists");
					$response["data"]		=	(object)array();
				}
			}
		}else {
			$response["status"]		=	"error";
			$response["msg"]		=	trans("messages.Invalid_Request");
			$response["data"]		=	(object)array();
		}
		return json_encode($response);

	}
	function rejectAppointment(Request $request){
		$lang			=	App::getLocale();
		$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");
		$formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$request->replace($this->arrayStripTags($request->all()));
			$validator = Validator::make(
				$request->all(),
				array(
					'appointment_id' 			=> 'required',
				),
				array(
					"appointment_id.required"		 	=> trans("messages.appointment_id_required"),
				)
			);
			if ($validator->fails()){
				$response		=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$appointment_id = $request->appointment_id;
				$checkIfAppointmentExists = DB::table('bookings')->where('bookings.hcp_id',Auth::guard('api')->user()->id)->where('bookings.id',$appointment_id)->first();
				if(!empty($checkIfAppointmentExists)){
					DB::table('bookings')->where('bookings.hcp_id',Auth::guard('api')->user()->id)->where('bookings.id',$appointment_id)->update(['status' => 'cancelled']);
					//$records_per_page	=	Config::get('Reading.records_per_page');
					// $appointments		=	DB::table('bookings')->where('bookings.hcp_id',Auth::guard('api')->user()->id)->leftJoin('lookup_descriptions as consultation','consultation.parent_id','bookings.consultation_method')->where("language_id",$language_id)->leftJoin('users','bookings.hcp_id','users.id')->where('bookings.status','cancelled')->select('bookings.id','bookings.booking_date','bookings.booking_time','bookings.booking_date_time','bookings.consultation_method as consultation_id','consultation.code as consultation_method','users.name','users.image','bookings.status')->orderBy("bookings.booking_date_time","ASC")->groupBy("bookings.id")->paginate($records_per_page);
					// if($appointments->isNotEmpty()){
					// 	foreach($appointments as $appVal){
					// 		$appVal->booking_time = date('h:i A',strtotime($appVal->booking_time));
					// 		$appVal->booking_date = date('d M, Y',strtotime($appVal->booking_date));
			
					// 		if(!empty($appVal->image) && file_exists(USER_IMAGE_ROOT_PATH.$appVal->image)){
					// 			$appVal->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$appVal->image;
					// 		}else{
					// 			$appVal->image = WEBSITE_IMG_URL.'noimage.png';
					// 		}
			
					// 	}
					// }


					$appointments		=	DB::table('bookings')
					->where("bookings.id",$appointment_id)
					->where('bookings.hcp_id',Auth::guard('api')->user()->id)
					->leftJoin('lookup_descriptions as consultation','consultation.parent_id','bookings.consultation_method')
					->where("language_id",$language_id)
					->leftJoin('users','bookings.hcp_id','users.id')
					->where('bookings.status','cancelled')
					// ->where('bookings.booking_date_time','>=',date('Y-m-d H:i:s'))
					->select('bookings.id','bookings.booking_date','bookings.booking_time','bookings.booking_date_time','bookings.consultation_method as consultation_id','consultation.code as consultation_method','users.name','users.image','bookings.status')->first();
					if(!empty($appointments)){
						$appointments->booking_time = date('h:i A',strtotime($appointments->booking_time));
						$appointments->booking_date = date('d M, Y',strtotime($appointments->booking_date));

						if(!empty($appointments->image) && file_exists(USER_IMAGE_ROOT_PATH.$appointments->image)){
							$appointments->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$appointments->image;
						}else{
							$appointments->image = WEBSITE_IMG_URL.'noimage.png';
						}
					}

					$response["status"]		=	"success";
					$response["msg"]		=	trans("messages.Appointment_rejected_successfully.");
					$response["data"]		=	$appointments;
				}else{
					$response["status"]		=	"error";
					$response["msg"]		=	trans("messages.Appointment_does_not_exists");
					$response["data"]		=	(object)array();
				}
			}
		}else {
			$response["status"]		=	"error";
			$response["msg"]		=	trans("messages.Invalid_Request");
			$response["data"]		=	(object)array();
		}
		return json_encode($response);

	}
	function markAsCompleteAppointment(Request $request){
		$formData	=	$request->all();
		$response	=	array();
		if(!empty($formData)){
			$request->replace($this->arrayStripTags($request->all()));
			$validator = Validator::make(
				$request->all(),
				array(
					'appointment_id' 			=> 'required',
				),
				array(
					"appointment_id.required"		 	=> trans("messages.appointment_id_required"),
				)
			);
			if ($validator->fails()){
				$response		=	$this->change_error_msg_layout($validator->errors()->getMessages());
			}else{
				$appointment_id = $request->appointment_id;
				$checkIfAppointmentExists = DB::table('bookings')->where('bookings.hcp_id',Auth::guard('api')->user()->id)->where('bookings.id',$appointment_id)->first();
				if(!empty($checkIfAppointmentExists)){
					DB::table('bookings')->where('bookings.hcp_id',Auth::guard('api')->user()->id)->where('bookings.id',$appointment_id)->update(['status' => 'completed']);
					$response["status"]		=	"success";
					$response["msg"]		=	trans("messages.Appointment_completed_successfully");
					$response["data"]		=	(object)array();
				}else{
					$response["status"]		=	"error";
					$response["msg"]		=	trans("messages.Appointment_does_not_exists");
					$response["data"]		=	(object)array();
				}
			}
		}else {
			$response["status"]		=	"error";
			$response["msg"]		=	trans("messages.Invalid_Request");
			$response["data"]		=	(object)array();
		}
		return json_encode($response);

	}



	public function BookNow(Request $request){
        $formData	=	$request->all();
        $response	=	array();
        if(!empty($formData)){
            $request->replace($this->arrayStripTags($request->all()));
            $validator = Validator::make(
                $request->all(),
                array(
                    'hcp_id'                    => 'required',
                    'booking_date'              => 'required',
                    'booking_time'              => 'required',
                    'consultation_method'       => 'required',
                ),
                array(
                    "hcp_id.required"			        => trans("messages.The_hcp_id_field_is_required"),
                    "booking_date.required"			    => trans("messages.The_booking_date_field_is_required"),
                    "booking_time.required"			    => trans("messages.The_booking_time_field_is_required"),
                    "consultation_method.required"		=> trans("messages.The_consultation_method_field_is_required"),
                )
            );
            if ($validator->fails()){
                $response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
            }else{
                $patient_id             = Auth::guard('api')->user()->id;   
                $booking_date     =   !empty($request->input('booking_date')) ? date('Y-m-d',strtotime($request->input('booking_date'))) : "";
                $booking_time     =   !empty($request->input('booking_time')) ? date('H:i:s',strtotime($request->input('booking_time'))) : "";

                $booking                                =  new Booking;
                $booking->patient_id                    =  $patient_id;
                $booking->hcp_id                        =  $request->input('hcp_id');
                $booking->booking_date                  =  !empty($request->input('booking_date')) ? date('Y-m-d',strtotime($request->input('booking_date'))) : "";
                $booking->booking_time                  =  !empty($request->input('booking_time')) ? date('H:i:s',strtotime($request->input('booking_time'))) : "";
                $booking->booking_date_time             =  $booking_date.' '.$booking_time;
                $booking->consultation_method           =  $request->input('consultation_method');
                $booking->descriptions                  =  $request->input('descriptions');
                $booking->country_id                    =  !empty($request->input('country_id')) ? $request->input('country_id') : "";
                $booking->address_line_1                =  !empty($request->input('address_line_1')) ? $request->input('address_line_1') : "";
                $booking->address_line_2                =  !empty($request->input('address_line_2')) ? $request->input('address_line_2') : "";
                $booking->google_location_address       =  !empty($request->input('google_location_address')) ? $request->input('google_location_address') : "";
                $booking->area_id                       =  !empty($request->input('area_id')) ? $request->input('area_id') : "";
                $booking->district_id                   =  !empty($request->input('district_id')) ? $request->input('district_id') : "";
                $booking->building_id                   =  !empty($request->input('building_id')) ? $request->input('building_id') : "";
                $booking->status                        =  "pending";
                $booking->save();
                $booking_id   =  $booking->id;

                if(!empty($formData['symptom_id'])){
                    foreach($formData['symptom_id'] as $symptoms){
                        $BookingSymptom                =   new BookingSymptom;
                        $BookingSymptom->booking_id    =  $booking_id;
                        $BookingSymptom->symptom_id    =   $symptoms;
                        $BookingSymptom->save();
                    }
                }
                $response["status"]	 	=	"success";
                $response["msg"]		=	trans("messages.booking_has_been_requested_successfully");
                $response["data"]		=	(object)array();    
            }   
        }else {
            $response["status"]		=	"error";
            $response["msg"]		=	trans("messages.Invalid_Request");
            $response["data"]		=	(object)array();
        }
        return json_encode($response);
    }




    public function BookingDetail(Request $request , $booking_id= 0){
        $BookingData  = Booking::find($booking_id);
        if(empty($BookingData)){
            $response["status"]		=	"error";
            $response["msg"]		=	"";
            $response["data"]		=	(object)array();
            return json_encode($response);
        }
        $response    = array();
        $lang			=	App::getLocale();
		$language_id	=	DB::table("languages")->where("lang_code",$lang)->value("id");
        $user_role_id   =   Auth::guard('api')->user()->user_role_id;
        if($user_role_id  == PATIENT_ROLE_ID){
            $booking_details  =   DB::table("bookings")->where("bookings.id",$booking_id)->where("patient_id",Auth::guard('api')->user()->id)->leftjoin("lookup_descriptions","lookup_descriptions.parent_id","bookings.consultation_method")->where("language_id",$language_id)->select("bookings.*","bookings.booking_date","bookings.booking_time","bookings.booking_date_time","bookings.descriptions","bookings.status","lookup_descriptions.code")->first();
            if(!empty($booking_details)){
                $hcp_details  = DB::table("users")->where('users.id',$booking_details->hcp_id)->select("users.id as hcp_id","users.name","users.phone_number_prefix","users.phone_number","users.email","users.gender","users.image")->first();

                $symptoms   =  DB::table("booking_symptoms")->where('booking_id',$booking_details->id)->leftjoin("symptom_descriptions","symptom_descriptions.parent_id","booking_symptoms.symptom_id")->where("language_id",$language_id)->select("symptom_descriptions.name","booking_symptoms.symptom_id")->get()->toArray();
                $booking_details->symptoms  = $symptoms;

                if($booking_details->status == 'pending'){
                    if($lang == 'en'){
                        $status_string  = "Pending";
                    }else{
                        $status_string  = "待辦的"; 
                    }
                }else if($booking_details->status == 'accepted'){
                    if($lang == 'en'){
                        $status_string  = "In process";
                    }else{
                        $status_string  = "進行中"; 
                    }
                }else if($booking_details->status == 'cancelled'){
                    if($lang == 'en'){
                        $status_string  = "Cancelled";
                    }else{
                        $status_string  = "取消"; 
                    }
                }else if($booking_details->status == 'completed'){
                    if($lang == 'en'){
                        $status_string  = "Completed";
                    }else{
                        $status_string  = "完全的"; 
                    }
                }
                $booking_details->status_string  =  $status_string;


                if($hcp_details->gender == "male" && $lang == 'en'){
                    $hcp_details->gender = "Male";
                }else if($hcp_details->gender == "female" && $lang == 'en'){
                    $hcp_details->gender = "Female";
                }else if($hcp_details->gender == "other" && $lang == 'en'){
                    $hcp_details->gender = "Other";
                }else if($hcp_details->gender == "male" && $lang == 'ch'){
                    $hcp_details->gender = "男性";
                }else if($hcp_details->gender == "female" && $lang == 'ch'){
                    $hcp_details->gender = "女性";
                }else if($hcp_details->gender == "other" && $lang == 'ch'){
                    $hcp_details->gender = "其他";
                }	

                if(!empty($hcp_details->image) && file_exists(USER_IMAGE_ROOT_PATH.$hcp_details->image)){
                    $hcp_details->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$hcp_details->image;
                }else{
                    $hcp_details->image = WEBSITE_IMG_URL.'noimage.png';
                }

                $booking_details->hcp_details        = $hcp_details;


                $response["status"]		=	"success";
                $response["msg"]		=	"";
                $response["data"]		=	$booking_details;
                return json_encode($response);

            }else{
                $response["status"]		=	"error";
                $response["msg"]		=	trans("messages.Invalid_Request");
                $response["data"]		=	(object)array(); 
                return json_encode($response);
            }
        }else{
            $booking_details  =   DB::table("bookings")->where("bookings.id",$booking_id)->where("hcp_id",Auth::guard('api')->user()->id)->leftjoin("lookup_descriptions","lookup_descriptions.parent_id","bookings.consultation_method")->where("lookup_descriptions.language_id",$language_id)->select("bookings.*","bookings.booking_date","bookings.booking_time","bookings.booking_date_time","bookings.descriptions","bookings.status","lookup_descriptions.code")->first();
            if(!empty($booking_details)){
                $careseeker_detail  =  DB::table("users")->where('users.id',$booking_details->patient_id)->select("users.id as patient_id","users.name","users.phone_number_prefix","users.phone_number","users.email","users.gender","users.image")->first();
				
                $symptoms   =  DB::table("booking_symptoms")->where('booking_id',$booking_details->id)->leftjoin("symptom_descriptions","symptom_descriptions.parent_id","booking_symptoms.symptom_id")->where("language_id",$language_id)->select("symptom_descriptions.name","booking_symptoms.symptom_id")->get()->toArray();
                $booking_details->symptoms  = $symptoms;

                if($booking_details->status == 'pending'){
                    if($lang == 'en'){
                        $status_string  = "Pending";
                    }else{
                        $status_string  = "待辦的"; 
                    }
                }else if($booking_details->status == 'accepted'){
                    if($lang == 'en'){
                        $status_string  = "In process";
                    }else{
                        $status_string  = "進行中"; 
                    }
                }else if($booking_details->status == 'cancelled'){
                    if($lang == 'en'){
                        $status_string  = "Cancelled";
                    }else{
                        $status_string  = "取消"; 
                    }
                }else if($booking_details->status == 'completed'){
                    if($lang == 'en'){
                        $status_string  = "Completed";
                    }else{
                        $status_string  = "完全的"; 
                    }
                }
                $booking_details->status_string  =  $status_string;


                if($careseeker_detail->gender == "male" && $lang == 'en'){
                    $careseeker_detail->gender = "Male";
                }else if($careseeker_detail->gender == "female" && $lang == 'en'){
                    $careseeker_detail->gender = "Female";
                }else if($careseeker_detail->gender == "other" && $lang == 'en'){
                    $careseeker_detail->gender = "Other";
                }else if($careseeker_detail->gender == "male" && $lang == 'ch'){
                    $careseeker_detail->gender = "男性";
                }else if($careseeker_detail->gender == "female" && $lang == 'ch'){
                    $careseeker_detail->gender = "女性";
                }else if($careseeker_detail->gender == "other" && $lang == 'ch'){
                    $careseeker_detail->gender = "其他";
                }	

                if(!empty($careseeker_detail->image) && file_exists(USER_IMAGE_ROOT_PATH.$careseeker_detail->image)){
                    $careseeker_detail->image		 = WEBSITE_URL."image.php?height=300px&image=".USER_IMAGE_URL.$careseeker_detail->image;
                }else{
                    $careseeker_detail->image = WEBSITE_IMG_URL.'noimage.png';
                }

                $booking_details->careseeker_detail  = $careseeker_detail;

                $response["status"]		=	"success";
                $response["msg"]		=	"";
                $response["data"]		=	$booking_details;
                return json_encode($response);

            }else{
                $response["status"]		=	"error";
                $response["msg"]		=	trans("messages.Invalid_Request");
                $response["data"]		=	(object)array(); 
                return json_encode($response);
            }
        }
        
        return json_encode($response);
    }



}
