<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 80)->unique();
            $table->string('slug', 90)->unique();
            $table->string('warna', 10)->default('ungu');
            $table->string('deskripsi')->nullable();
            $table->dateTime('created_at');
        });

        Schema::create('subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('nama', 80);
            $table->unique(['category_id', 'nama']);
        });

        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained()->nullOnDelete();
            $table->string('judul', 150);
            $table->text('deskripsi')->nullable();
            $table->text('petunjuk')->nullable();
            $table->enum('kesulitan', ['mudah', 'menengah', 'sulit'])->default('mudah');
            $table->enum('jenjang', ['sd', 'smp', 'sma', 'pt', 'umum'])->default('umum');
            $table->unsignedSmallInteger('durasi_menit')->default(0);
            $table->boolean('tampilkan_feedback')->default(false);
            $table->boolean('navigasi_bebas')->default(true);
            $table->enum('status', ['draft', 'terbit'])->default('draft');
            $table->dateTime('created_at');
            $table->dateTime('updated_at')->nullable();
            $table->index(['status', 'category_id', 'kesulitan', 'jenjang'], 'idx_quiz_filter');
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->enum('tipe', ['pg', 'pgk', 'bs', 'isian', 'esai']);
            $table->text('teks');
            $table->unsignedSmallInteger('poin')->default(10);
            $table->text('pembahasan')->nullable();
            $table->enum('pencocokan', ['longgar', 'ketat'])->default('longgar');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->index(['quiz_id', 'urutan']);
        });

        Schema::create('answer_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('teks');
            $table->boolean('benar')->default(false);
            $table->unsignedSmallInteger('urutan')->default(0);
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['berlangsung', 'menunggu', 'selesai'])->default('berlangsung');
            $table->dateTime('mulai_pada');
            $table->dateTime('selesai_pada')->nullable();
            $table->unsignedInteger('durasi_detik')->nullable();
            $table->decimal('poin_didapat', 8, 2)->nullable();
            $table->decimal('poin_maks', 8, 2)->nullable();
            $table->decimal('skor', 5, 2)->nullable();
            $table->unsignedSmallInteger('jumlah_benar')->nullable();
            $table->unsignedSmallInteger('jumlah_salah')->nullable();
            $table->boolean('diarsipkan')->default(false);
            $table->dateTime('diarsipkan_pada')->nullable();
            $table->index(['user_id', 'diarsipkan', 'status']);
            $table->index(['quiz_id', 'status']);
        });

        Schema::create('user_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('quiz_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('jawaban_teks')->nullable();
            $table->boolean('dikunci')->default(false);
            $table->boolean('benar')->nullable();
            $table->decimal('poin', 6, 2)->nullable();
            $table->string('catatan', 500)->nullable();
            $table->foreignId('dinilai_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['attempt_id', 'question_id']);
        });

        Schema::create('user_answer_options', function (Blueprint $table) {
            $table->foreignId('user_answer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('answer_options')->cascadeOnDelete();
            $table->primary(['user_answer_id', 'option_id']);
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 40)->unique();
            $table->string('nama', 60);
            $table->string('deskripsi');
            $table->string('ikon', 20)->default('bintang');
            $table->string('warna', 10)->default('ungu');
            $table->enum('syarat', ['jumlah_kuis', 'skor_sempurna', 'kuis_kategori', 'ragam_kategori']);
            $table->unsignedSmallInteger('nilai_syarat')->default(1);
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attempt_id')->nullable()->constrained('quiz_attempts')->nullOnDelete();
            $table->dateTime('diperoleh_pada');
            $table->primary(['user_id', 'badge_id']);
        });
    }

    public function down(): void
    {
        foreach (['user_badges', 'badges', 'user_answer_options', 'user_answers', 'quiz_attempts',
            'answer_options', 'questions', 'quizzes', 'subcategories', 'categories'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
