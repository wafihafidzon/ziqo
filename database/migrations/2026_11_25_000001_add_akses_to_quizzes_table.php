<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** publik = tampil di Jelajah dan bisa dikerjakan langsung; room = hanya lewat kode room. */
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->enum('akses', ['publik', 'room'])->default('publik')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('akses');
        });
    }
};
