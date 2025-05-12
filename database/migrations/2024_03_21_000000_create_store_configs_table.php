<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('store_configs', function (Blueprint $table) {
            $table->id();
            $table->string('store_url');
            $table->timestamp('last_sync')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('store_configs');
    }
}; 