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
        Schema::create('alarms', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('created_by')->length(20);
            $table->string('room_name');
            $table->date('date');
            $table->string('time');
            $table->bigInteger('customer_id')->length(20);
            $table->bigInteger('agent_id')->length(20);
            $table->string('city');
            $table->string('address');
            $table->string('alarm_type');
            $table->smallInteger('is_guest_reached')->default(0);
            $table->smallInteger('is_manager_contacted')->default(0);
            $table->text('guest_details')->nullable();
            $table->text('manager_details')->nullable();
            $table->text('comments')->nullable();
            $table->string('caller_name')->nullable();
            $table->string('caller_phone_number')->nullable();
            $table->string('caller_location')->nullable();
            $table->string('alarm_status');
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
        Schema::dropIfExists('alarms');
    }
};
