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
        Schema::table('customers', function (Blueprint $table) {

            $table->string('alias')->change()->nullable();
            $table->string('date')->change()->nullable();
            $table->string('origin')->change()->nullable();
            $table->string('username')->change()->nullable();
            $table->string('phone_number')->change()->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
