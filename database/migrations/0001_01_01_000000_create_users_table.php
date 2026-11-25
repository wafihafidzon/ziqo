<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('email', 190)->unique();
            $table->string('password_hash');
            $table->enum('role', ['peserta', 'pengajar', 'admin'])->default('peserta');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->string('telepon', 20)->nullable();
            $table->string('jenjang', 20)->nullable();
            $table->string('bio', 300)->nullable();
            $table->string('avatar', 100)->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
