<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use App\Models\Alarm;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Str,Mail;
use Validator; 
use Helper,Hash,File,Config,DB,PDF;
use Excel;
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

    public function uploadCustomerCsv(Request $request)
    { 
      if($request->hasFile('customer_file')){
        $path = $request->file('customer_file')->getRealPath();
        $fileExtension = $request->file('customer_file')->getClientOriginalExtension();

        $formats = ['csv'];
        if (! in_array($fileExtension, $formats)) {
          $response				=	array();
          $response["status"]		=	"error";
          $response["data"]		=	(object)array();
          $response["msg"]		=	trans("The uploaded file format is not allowed.");
          $response["http_code"]	=	401;
          return response()->json($response,200);
        }
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
        $duplicateAccountIdArr = [];
        foreach ($this->rows as $data) {
            if(!empty($data[7])){

              $checkIfAccountingIdAlreadyExists = Customer::where('accounting_id',$data[7])->first();
              if(!empty($checkIfAccountingIdAlreadyExists)){
                $filename = time().'-'.$checkIfAccountingIdAlreadyExists->accounting_id.'.csv';
                $filePath = public_path().'/'.$filename;
                if(\File::exists($filePath)){
                  $f = fopen($filePath, 'a');
                  $data = [
                    [!empty($data[0]) ? $data[0] : '', !empty($data[1]) ? $data[1] : '', !empty($data[2]) ? $data[2]: '', !empty($data[3]) ? preg_replace('/\s+/', '', $data[3]): '', !empty($data[4]) ? preg_replace('/\s+/', '', $data[4]): '', !empty($data[5]) ? $data[5]: '', !empty($data[6]) ? $data[6]: '', !empty($data[7]) ? $data[7]: '', !empty($data[8]) ? $data[8]: '', !empty($data[9]) ? $data[9]: '', !empty($data[10]) ? $data[10]: '',!empty($data[11]) ? $data[11]: '',!empty($data[12]) ? $data[12]: '',!empty($data[13]) ? $data[13]: '']
                  ];
                }else{ 
                  $f = fopen($filePath, 'w');
                  $data = [
                    ['Company Name', 'Alias', 'Date', 'VAT', 'IBAN', 'Origin', 'GoCardless ID', 'Accounting ID', 'Subscription', 'Contact', 'Username', 'Phone', 'Billing Email', 'Reports Email'],
                    [!empty($data[0]) ? $data[0] : '', !empty($data[1]) ? $data[1] : '', !empty($data[2]) ? $data[2]: '', !empty($data[3]) ? preg_replace('/\s+/', '', $data[3]): '', !empty($data[4]) ? preg_replace('/\s+/', '', $data[4]): '', !empty($data[5]) ? $data[5]: '', !empty($data[6]) ? $data[6]: '', !empty($data[7]) ? $data[7]: '', !empty($data[8]) ? $data[8]: '', !empty($data[9]) ? $data[9]: '', !empty($data[10]) ? $data[10]: '',!empty($data[11]) ? $data[11]: '',!empty($data[12]) ? $data[12]: '',!empty($data[13]) ? $data[13]: '']
                  ];
                  $duplicateAccountIdArr[] = url('/').'/'.$filename;
                }
                foreach ($data as $row) {
                  fputcsv($f, $row);
                }

                fclose($f);

              }else{
                
                $obj 									=  new Customer;
                $obj->company_name 								=  !empty($data[0]) ? $data[0] : '';
                $obj->alias 								=  !empty($data[1]) ? $data[1] : '';
                $obj->date 								=  !empty($data[2]) ? $data[2] : '';
                $obj->vat 								=  !empty($data[3]) ? preg_replace('/\s+/', '', $data[3]) : '';
                $obj->iban 								=  !empty($data[4]) ? preg_replace('/\s+/', '', $data[4]): '';
                $obj->origin 								=  !empty($data[5]) ? $data[5] : '';
                $obj->gocardless_id 								=  !empty($data[6]) ? $data[6] : '';
                $obj->accounting_id 								=  !empty($data[7]) ? $data[7] : '';
                $obj->subscription 								=  !empty($data[8]) ? $data[8] : '';
                $obj->contact 								=  !empty($data[9]) ? $data[9] : '';
                $obj->username 								=  !empty($data[10]) ? $data[10] : '';
                $obj->phone_number 								=  !empty($data[11]) ? $data[11] : '';
                $obj->billing_email 								=  !empty($data[12]) ? $data[12] : '';
                $obj->reports_email 								=  !empty($data[13]) ? $data[13] :  '';
                $obj->status 								=  'completed';
                
                $obj->save();
              }
            
            }
        }


        $response				=	array();
        $response["status"]		=	"success";
        $response["data"]		=	(object)array();
        $response["msg"]		=	trans("Customers Uploaded Successfully.");
        $response["duplicate_records"]		=	$duplicateAccountIdArr;
        $response["http_code"]	=	200;
        return response()->json($response,200);
      }else{
        $response				=	array();
        $response["status"]		=	"error";
        $response["data"]		=	(object)array();
        $response["msg"]		=	trans("The customer file field is required.");
        $response["http_code"]	=	401;
        return response()->json($response,200);
      }
        
      
    }
    public function uploadRoomCsv(Request $request)
    { 
      // print_r($request->all());die;
      if($request->hasFile('room_file')){
        $path = $request->file('room_file')->getRealPath();
        $fileExtension = $request->file('room_file')->getClientOriginalExtension();

        $formats = ['csv'];
        if (! in_array($fileExtension, $formats)) {
          $response				=	array();
          $response["status"]		=	"error";
          $response["data"]		=	(object)array();
          $response["msg"]		=	trans("The uploaded file format is not allowed.");
          $response["http_code"]	=	401;
          return response()->json($response,200);
        }
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
        $duplicateAccountIdArr = [];
        foreach ($this->rows as $data) {
           if(!empty($data[0]) || !empty($data[1])){
            
             $obj 									=  new Room;
             $obj->username 								=  !empty($data[0]) ? $data[0] : '';
             $obj->address 								=  !empty($data[1]) ? $data[1] : '';
            $obj->save();
           }
            
        }


        $response				=	array();
        $response["status"]		=	"success";
        $response["data"]		=	(object)array();
        $response["msg"]		=	trans("Rooms Uploaded Successfully.");
        $response["http_code"]	=	200;
        return response()->json($response,200);
      }else{
        $response				=	array();
        $response["status"]		=	"error";
        $response["data"]		=	(object)array();
        $response["msg"]		=	trans("The room file field is required.");
        $response["http_code"]	=	401;
        return response()->json($response,200);
      }
        
      
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



	

}
