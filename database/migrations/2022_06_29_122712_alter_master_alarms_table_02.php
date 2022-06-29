<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('master_alarms', function (Blueprint $table) {
            $table->text('incident_details')->nullable();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('master_alarms', function($table) {
            $table->dropColumn('status');
        });
        // $table->enum('status', ['ongoing', 'pending', 'submitted'])->change();
    }
};
