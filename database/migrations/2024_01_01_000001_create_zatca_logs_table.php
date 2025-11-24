<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zatca_logs', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_uuid')->nullable()->index();
            $table->string('invoice_number')->nullable();
            $table->longText('request_xml')->nullable();
            $table->longText('response')->nullable();
            $table->integer('status_code')->nullable();
            $table->string('status')->nullable()->index(); // success, error, warning
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zatca_logs');
    }
};

