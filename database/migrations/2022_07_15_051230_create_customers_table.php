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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('alias');
            $table->string('date');
            $table->string('vat');
            $table->string('iban');
            $table->string('origin');
            $table->string('gocardless_id');
            $table->string('accounting_id');
            $table->string('subscription');
            $table->string('contact');
            $table->string('username');
            $table->string('phone_number');
            $table->string('billing_email')->nullable();
            $table->string('reports_email')->nullable();

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
        Schema::dropIfExists('customers');
    }
};
