<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('mac_address')->unique();
            $table->string('name')->default('My Tibuwise');
            $table->string('api_key', 64)->unique();
            $table->string('pairing_code', 6)->nullable();
            $table->timestamp('pairing_code_expires_at')->nullable();
            $table->string('wifi_ssid')->nullable();
            $table->string('firmware_version')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->boolean('is_online')->default(false);
            $table->smallInteger('signal_strength')->nullable();
            $table->unsignedInteger('pending_uploads')->default(0);
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
