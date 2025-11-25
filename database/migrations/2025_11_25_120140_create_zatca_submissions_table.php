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
        Schema::create('zatca_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id')->index();
            $table->string('uuid')->nullable()->index();
            $table->string('invoice_hash')->nullable();
            $table->string('status')->default('pending'); // pending, success, failed
            $table->string('submission_type')->default('compliance'); // compliance, reporting
            $table->text('request_data')->nullable(); // JSON encoded request data
            $table->text('response_data')->nullable(); // JSON encoded response data
            $table->text('error_message')->nullable();
            $table->string('zatca_request_id')->nullable();
            $table->string('validation_status')->nullable(); // VALID, INVALID
            $table->text('validation_errors')->nullable(); // JSON encoded errors
            $table->string('invoice_file_path')->nullable();
            $table->string('signed_invoice_file_path')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->string('environment')->default('sandbox'); // sandbox, production
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index(['environment', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zatca_submissions');
    }
};
