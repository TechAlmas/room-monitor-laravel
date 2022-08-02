<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithEvents;

use File;

class CustomersImport implements ToModel,WithBatchInserts, WithChunkReading,WithStartRow
{
    public $duplicateAccountIdArr = [];
    /**
     * @param array $row
     *
     * @return User|null
     */

    public function model(array $row)
    {
        // print_r($row);die;
        $data = $this->clean($row);
        if(!empty($data[7])){

            $checkIfAccountingIdAlreadyExists = Customer::where('accounting_id',$data[7])->first();
            if(!empty($checkIfAccountingIdAlreadyExists)){
              $filename = time().'-'.$checkIfAccountingIdAlreadyExists->accounting_id.'.csv';
              $filePath = public_path('/uploads/csv/').$filename;
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
                $this->duplicateAccountIdArr[] = url('/uploads/csv/').'/'.$filename;
              }
              foreach ($data as $row) {
                fputcsv($f, $row);
              }

              fclose($f);

            }else{
                return new Customer([
                    'company_name'     => !empty($data[0]) ? $data[0] : '' ,
                    'alias'    => !empty($data[1]) ? $data[1] : '', 
                    'date'    => !empty($data[2]) ? $data[2] : '' ,
                    'vat'    => !empty($data[3]) ? preg_replace('/\s+/', '', $data[3]) : '', 
                    'iban'    => !empty($data[4]) ? preg_replace('/\s+/', '', $data[4]): '',
                    'origin'    => !empty($data[5]) ? $data[5] : '' ,
                    'gocardless_id'    => !empty($data[6]) ? $data[6] : '' ,
                    'accounting_id'    => !empty($data[7]) ? $data[7] : '' ,
                    'subscription'    => !empty($data[8]) ? $data[8] : '' ,
                    'contact'    => !empty($data[9]) ? $data[9] : '' ,
                    'username'    => !empty($data[10]) ? $data[10] : '' ,
                    'phone_number'    => !empty($data[11]) ? $data[11] : '' ,
                    'billing_email'    => !empty($data[12]) ? $data[12] : '' ,
                    'reports_email'    => !empty($data[13]) ? $data[13] : '' ,
                    'status'    => 'completed'
                ]);
        
            }
          
          }
      
    }

    public function clean($record)
    {
        foreach ($record as $key => $value) {
            $value = utf8_encode($value);
            $value = trim($value);
            $value = $value === '' ? null : $value;
    
            $record[$key] = $value;
        }
    
        return $record;
    }
    public function startRow(): int 
    {
         return 2;
    }

    public function batchSize(): int
    {
        return 500;
    }
    
    public function chunkSize(): int
    {
        return 250;
    }
}