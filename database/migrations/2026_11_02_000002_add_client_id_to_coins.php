<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coins', function (Blueprint $table) {
            $table->string('client_id', 64)->nullable()->after('received_at');
            $table->unique(['device_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::table('coins', function (Blueprint $table) {
            $table->dropUnique(['device_id', 'client_id']);
            $table->dropColumn('client_id');
        });
    }
};
