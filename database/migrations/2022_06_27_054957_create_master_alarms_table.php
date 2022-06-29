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
        Schema::create('master_alarms', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->length(20);
            $table->string('name');
            $table->string('phone_number');
            $table->string('zipcode');
            $table->string('city');
            $table->string('address')->nullable();
            $table->string('subject_name');
            $table->string('time_called');
            $table->string('incident_date');
            $table->smallInteger('is_intervention_needed')->default(0);
            $table->smallInteger('is_manager_contacted')->default(0);
            $table->text('manager_details')->nullable();
            $table->string('intervention_time')->nullable();
            $table->string('intervention_type')->nullable();
            $table->string('intervention_concierges')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('master_alarms');
    }
};
