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
        Schema::create('alarms_import', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->length(20);
            $table->string('hour');
            $table->string('types_of_alarms');
            $table->string('room_name');
            $table->string('user');
            $table->string('alarms');
            $table->string('agent_sent');
            $table->string('agent_name');
            $table->string('guest_reached');
            $table->string('guest_name');
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
        Schema::dropIfExists('alarms_import');
    }
};
