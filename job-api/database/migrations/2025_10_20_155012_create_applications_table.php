<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_applications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->onDelete('cascade');
            $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
            $table->text('cover_letter');
            $table->string('resume')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'accepted', 'rejected'])->default('pending');
            $table->text('feedback')->nullable();
            $table->timestamps();
            
            $table->unique(['job_id', 'candidate_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('applications');
    }
};