<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\QuizService;
use App\Services\StatsService;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private UserService $users)
    {
    }

    public function index(Request $request)
    {
        $f = $this->users->filters($request->only('q', 'role', 'status'));
        $pg = paginate($this->users->searchCount($f), 15);
        return view('admin.pengguna', [
            'title' => 'Pengguna',
            'menu' => 'admin-pengguna',
            'f' => $f,
            'rows' => $this->users->search($f, $pg['limit'], $pg['offset']),
            'pg' => $pg,
            'admin' => $this->user(),
            'jumlahRole' => $this->users->countByRole(),
        ]);
    }

    public function update(Request $request)
    {
        $id = $request->integer('id');
        $aksi = $request->input('aksi');
        $err = in_array($aksi, ['role', 'status'], true)
            ? $this->users->adminSet($this->user(), $id, $aksi, (string) $request->input($aksi))
            : 'Aksi tidak dikenal.';
        if ($err) {
            flash('error', $err);
        } else {
            $t = User::find($id);
            flash('sukses', 'Akun ' . $t->nama . ' diperbarui: ' . ROLE[$t->role] . ', ' . $t->status . '.');
        }

        if ($request->input('kembali') === 'detail') return redirect()->route('admin.pengguna.show', $id);
        $qs = $request->input('qs');
        $qs = is_string($qs) && preg_match('/^[\w=&%.\-+]*$/', $qs) ? $qs : '';
        return redirect(route('admin.pengguna') . ($qs !== '' ? '?' . $qs : ''));
    }

    public function show(int $id, StatsService $stats, QuizService $quizzes, BadgeService $badges)
    {
        $u = User::findOrFail($id);
        return view('admin.pengguna-detail', [
            'title' => $u->nama,
            'menu' => 'admin-pengguna',
            'u' => $u,
            'admin' => $this->user(),
            'stat' => $stats->user($id),
            'aktivitas' => $stats->recent($id, 8),
            'kuis' => $quizzes->forOwner($id),
            'badges' => $badges->collection($id)->filter(fn ($b) => $b['diperoleh_pada'])->values(),
        ]);
    }
}
