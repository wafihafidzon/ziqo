<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\BadgeService;
use App\Services\QuizService;
use App\Services\StatsService;

class HomeController extends Controller
{
    public function __invoke(StatsService $stats, QuizService $quizzes, BadgeService $badges)
    {
        return view('landing', [
            'title' => '',
            'menu' => 'beranda',
            'stat' => $stats->platform(),
            'kategori' => Category::withPublishedCount()->filter(fn ($c) => $c->jumlah_kuis > 0)->values(),
            'populer' => $quizzes->popular(6),
            'badge' => $badges->all()->take(3)->values(),
        ]);
    }
}
