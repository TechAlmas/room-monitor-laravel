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

            $table->string('company_name')->change()->nullable();
            $table->string('vat')->change()->nullable();
            $table->string('iban')->change()->nullable();
            $table->string('gocardless_id')->change()->nullable();
            $table->string('accounting_id')->change()->nullable();
            $table->string('subscription')->change()->nullable();
            $table->string('contact')->change()->nullable();

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
