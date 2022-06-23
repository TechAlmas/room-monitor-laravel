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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_role');
            $table->string('user_name')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone_number');
            $table->string('password');
            $table->string('validate_string');
            $table->string('vehicle_registration_number');
            $table->tinyInteger('is_active')->default('1');
            $table->tinyInteger('is_verified')->default('0');
            $table->tinyInteger('is_deleted')->default('0');
            $table->rememberToken();
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
        Schema::dropIfExists('users');
    }
};
