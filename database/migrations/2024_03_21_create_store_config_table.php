<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('store_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->string('store_url')->default('https://github.com/fearlessgeekmedia/FearlessCMS-Store.git');
            $table->timestamp('last_sync')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('store_config');
    }
}; 