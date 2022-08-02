<?php

namespace App\Imports;

use App\Models\Room;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithEvents;


class RoomsImport implements ToModel,WithBatchInserts, WithChunkReading,WithStartRow
{
    /**
     * @param array $row
     *
     * @return User|null
     */

    public function model(array $row)
    {
        // print_r($row);die;
        $row = $this->clean($row);
        return new Room([
            'username'     => !empty($row[0]) ? $row[0] : '' ,
            'address'    => !empty($row[1]) ? $row[1] : '' 
        ]);

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