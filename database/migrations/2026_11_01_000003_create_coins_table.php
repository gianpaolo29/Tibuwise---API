<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('denomination');
            $table->timestamp('deposited_at')->useCurrent();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();

            $table->index(['device_id', 'deposited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coins');
    }
};
