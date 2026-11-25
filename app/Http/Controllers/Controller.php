<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\User;
use App\Services\QuizService;

abstract class Controller
{
    protected function user(): ?User
    {
        return auth()->user();
    }

    protected function kuisKelola(int $id): Quiz
    {
        abort_unless($this->user()?->hasRole('pengajar', 'admin'), 403);
        $quizzes = app(QuizService::class);
        $quiz = $quizzes->find($id);
        abort_unless($quiz, 404);
        abort_unless($quizzes->canManage($quiz, $this->user()), 403, 'Kuis ini milik pengajar lain. Kamu hanya dapat mengelola kuis buatanmu sendiri.');
        return $quiz;
    }

    protected function gagal(array $errors, array $extra = [])
    {
        return back()->withErrors($errors)->withInput(request()->except(['password', 'konfirmasi', 'password_lama', 'password_baru']))->with($extra);
    }
}
