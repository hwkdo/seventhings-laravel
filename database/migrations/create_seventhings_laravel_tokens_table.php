<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seventhings_laravel_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token',4096);
            $table->text('refresh_token')->nullable();
            $table->datetime('expiration');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seventhings_laravel_tokens');
    }
};