<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\QuizService;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function index(Request $request, QuizService $quizzes)
    {
        $status = (string) $request->input('status');
        $f = [
            'q' => mb_substr((string) $request->input('q'), 0, 100),
            'status' => in_array($status, ['draft', 'terbit'], true) ? $status : '',
            'kategori' => (string) $request->input('kategori'),
        ];
        $pg = paginate($quizzes->adminCount($f), 15);
        return view('admin.kuis', [
            'title' => 'Pantau Kuis',
            'menu' => 'admin-kuis',
            'f' => $f,
            'rows' => $quizzes->adminList($f, $pg['limit'], $pg['offset']),
            'pg' => $pg,
            'kategori' => Category::urutNama()->get(),
        ]);
    }
}
