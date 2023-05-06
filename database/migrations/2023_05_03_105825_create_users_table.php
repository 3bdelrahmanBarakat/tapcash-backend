<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique();
            $table->string('pin_code')->nullable();
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->boolean('enabled')->default(true);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->enum('type', ['user', 'kid'])->default('user');
            $table->timestamp('mobile_verified_at')->nullable();
            $table->string('mobile_verify_code')->nullable();
            $table->tinyInteger('mobile_attempts_left')->default(0);
            $table->timestamp('mobile_last_attempt_date')->nullable();
            $table->timestamp('mobile_verify_code_sent_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->foreign('parent_id')->references('id')->on('users')->onDelete('cascade');
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
