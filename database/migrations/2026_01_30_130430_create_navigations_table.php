<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigations', function (Blueprint $table) {
            $table->id();
            $table->string('logo');
            $table->string('name');
            $table->string('link');
            $table->string('header');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigations');
    }
};
