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
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_role');
            $table->string('validate_string');
            $table->string('vehicle_registration_number');
            $table->smallInteger('is_active')->tinyInteger('is_active')->default(1)->change();
            $table->smallInteger('is_verified')->default(0);
            $table->smallInteger('is_deleted')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table->tinyInteger('is_active')->change();
    }
};
