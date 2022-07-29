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
class CustomersController extends Controller{


	public function __construct(Request $request) {
		parent::__construct();
        $this->request              =   $request;
    }

    
    public function addCustomer(Request $request){
      $getLoggedInUserId = Auth::guard('api')->user()->id;
      $formData	=	$request->all();
      $response	=	array();
      if(!empty($formData)){
        $validator 					=	Validator::make(
          $request->all(),
          array(
            // 'company_name'							=> 'required',
            'alias'                       => 'required',
            'date'				            => 'required',
            // 'vat'					    => ['required','regex:/^(FR)?[0-9A-Z]{2}[0-9]{9}$/i'],
            // 'iban'                       => ['required','regex:/^FR\d{12}[A-Z0-9]{11}\d{2}$/i'],
            'origin'       => 'required',
            // 'gocardless_id'       => 'required',
            // 'accounting_id'       => 'required',
            // 'subscription'       => 'required',
            // 'contact'       => 'required',
            'username'       => 'required|unique:customers',
            'phone_number'       => 'required',
            'billing_email'       => 'required',
            'reports_email'       => 'required',
            'status'              => 'required'
          )
        );
      
        if ($validator->fails()){
          $response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
        }else{
          $password     =     Str::random(8);
          DB::beginTransaction();
          $obj 									=  new Customer;
          $obj->company_name 								=  !empty($request->input('company_name')) ? $request->input('company_name') : '';
          $obj->alias 								=  $request->input('alias');
          $obj->date 								=  date('Y-m-d',strtotime($request->input('date')));
          $obj->vat 								=  !empty($request->input('vat')) ? $request->input('vat') : '';
          $obj->iban 								=  !empty($request->input('iban')) ? $request->input('iban') : '';
          $obj->origin 								=  $request->input('origin');
          $obj->gocardless_id 								=  !empty($request->input('gocardless_id')) ? $request->input('gocardless_id') : '';
          $obj->accounting_id 								=  !empty($request->input('accounting_id')) ? $request->input('accounting_id') : '';
          $obj->subscription 								=  !empty($request->input('subscription')) ? $request->input('subscription') : '';
          $obj->contact 								=  !empty($request->input('contact')) ? $request->input('contact') : '';
          $obj->username 								=  $request->input('username');
          $obj->phone_number 								=  $request->input('phone_number');
          $obj->billing_email 								=  $request->input('billing_email');
          $obj->reports_email 								=  $request->input('reports_email');
          $obj->status                        =   $request->input('status');	
          $obj->save();
          $userId  = $obj->id;
                  

          if($userId){
              DB::commit();
              
              $getUsersData = Customer::orderBy('updated_at','desc')->get();
            
              $response				=	array();
              $response["status"]		=	"success";
              $response["data"]		=	$getUsersData;
         
              $response["msg"]		=	trans("Customer added successfully.");
              
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

    public function updateCustomer(Request $request){
      if(!empty($request->id)){
          $getLoggedInUserId = Auth::guard('api')->user()->id;
          $formData	=	$request->all();
          $response	=	array();
          if(!empty($formData)){
            $validator 					=	Validator::make(
              $request->all(),
              array(
                // 'company_name'							=> 'required',
                'alias'                       => 'required',
                'date'				            => 'required',
                // 'vat'					    => ['required','regex:/^(FR)?[0-9A-Z]{2}[0-9]{9}$/i'],
                // 'iban'                       => ['required','regex:/^FR\d{12}[A-Z0-9]{11}\d{2}$/i'],
                'origin'       => 'required',
                // 'gocardless_id'       => 'required',
                // 'accounting_id'       => 'required',
                // 'subscription'       => 'required',
                // 'contact'       => 'required',
                'username'				            => 'required|unique:customers,username,'.$request->id,
                'phone_number'       => 'required',
                'billing_email'       => 'required',
                'reports_email'       => 'required',       
              )
            );
          
            if ($validator->fails()){
              $response				=	$this->change_error_msg_layout($validator->errors()->getMessages());
            }else{
              
              DB::beginTransaction();
              $obj 									=  Customer::find($request->id);
              $obj->company_name 								=  !empty($request->input('company_name')) ? $request->input('company_name') : '';
              $obj->alias 								=  $request->input('alias');
              $obj->date 								=  date('Y-m-d',strtotime($request->input('date')));
              $obj->vat 								=  !empty($request->input('vat')) ? $request->input('vat') : '';
              $obj->iban 								=  !empty($request->input('iban')) ? $request->input('iban') : '';
              $obj->origin 								=  $request->input('origin');
              $obj->gocardless_id 								=  !empty($request->input('gocardless_id')) ? $request->input('gocardless_id') : '';
              $obj->accounting_id 								=  !empty($request->input('accounting_id')) ? $request->input('accounting_id') : '';
              $obj->subscription 								=  !empty($request->input('subscription')) ? $request->input('subscription') : '';
              $obj->contact 								=  !empty($request->input('contact')) ? $request->input('contact') : '';
              $obj->username 								=  $request->input('username');
              $obj->phone_number 								=  $request->input('phone_number');
              $obj->billing_email 								=  $request->input('billing_email');
              $obj->reports_email 								=  $request->input('reports_email');
              
              $obj->save();
              $userId  = $obj->id;
                      

              if($userId){
                  DB::commit();
                  
                  $getUsersData = Customer::orderBy('updated_at','desc')->get();
                  $response				=	array();
                  $response["status"]		=	"success";
                  $response["data"]		=	$getUsersData;
                  $response["msg"]		=	trans("Customer updated successfully.");
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
        $response["msg"]		=	trans("The customer id field is required.");
        $response["http_code"]	=	401;
        return response()->json($response,200);

      }
    }

    public function fetchCustomerDetail(Request $request){
      if(!empty($request->id)){
        $getCustomerDetails = Customer::where('id',$request->id)->first();
          if(!empty($getCustomerDetails)){
            $getCustomerDetails->created_date = date('d-m-Y',strtotime($getCustomerDetails->created_at));
            $getCustomerDetails->created_time = date('H:i',strtotime($getCustomerDetails->created_at));
            
              $response				=	array();
              $response["status"]		=	"success";
              $response["data"]		=	$getCustomerDetails;
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
          $response["msg"]		=	trans("The customer id field is required.");
          $response["http_code"]	=	401;
          return response()->json($response,200);

      }
    }

    public function fetchCustomers(Request $request){
      $getLoggedInUserId = Auth::guard('api')->user()->id;

      $getCustomersData = Customer::query();
    
      $getCustomersData = $getCustomersData->orderBy('customers.updated_at','desc')->get();
     
           
      if($getCustomersData->isNotEmpty()){
        
          $response				=	array();
          $response["status"]		=	"success";
          $response["data"]		=	$getCustomersData;
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
