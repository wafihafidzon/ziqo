<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('kode_akses', 8)->unique();
            $table->enum('status', ['buka', 'tutup'])->default('buka');
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->index(['quiz_id', 'status']);
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_id');
        });
        Schema::dropIfExists('rooms');
    }
};
