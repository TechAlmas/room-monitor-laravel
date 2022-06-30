<?php
return [
    'REPORT_FILE' => ['REPORT_FILE_URL' =>(app()->runningInConsole() ? '' : url('/')) . '/'.'uploads/reports' , 'REPORT_FILE_PATH' =>(app()->runningInConsole() ? '' : base_path()) . DIRECTORY_SEPARATOR. 'uploads' . DIRECTORY_SEPARATOR. 'reports'.DIRECTORY_SEPARATOR ]
];