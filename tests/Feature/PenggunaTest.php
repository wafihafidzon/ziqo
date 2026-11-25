<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StatsService;
use App\Services\UserService;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PenggunaTest extends TestCase
{
    use RefreshDatabase;

    /** The in-memory DB is migrated once per run; seed it here too so other test classes keep their seed data. */
    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        // Empty tables inside the per-test transaction (rolled back afterwards) so fixture numbers are exact.
        foreach (['user_badges', 'user_answer_options', 'user_answers', 'quiz_attempts', 'answer_options',
            'questions', 'quizzes', 'badges', 'subcategories', 'categories', 'users'] as $tabel) {
            DB::table($tabel)->delete();
        }
    }

    private function buatUser(string $nama, string $email, string $role = 'peserta', string $status = 'aktif', string $password = 'rahasia123'): User
    {
        $id = DB::table('users')->insertGetId([
            'nama' => $nama, 'email' => $email, 'password_hash' => Hash::make($password),
            'role' => $role, 'status' => $status, 'created_at' => now()->format('Y-m-d H:i:s'),
        ]);
        return User::findOrFail($id);
    }

    private function toasts(): array
    {
        return array_column(session('toasts', []), 'pesan');
    }

    // ---------- Daftar & masuk ----------

    public function test_register_selalu_peserta_aktif_dengan_email_dinormalisasi(): void
    {
        $this->post('/register', [
            'nama' => 'Peserta Baru', 'email' => 'Baru@ZIQO.id', 'password' => 'rahasia123',
            'konfirmasi' => 'rahasia123', 'role' => 'admin', 'status' => 'nonaktif',
        ])->assertRedirect('/dashboard');

        $u = User::where('email', 'baru@ziqo.id')->firstOrFail();
        $this->assertSame(['peserta', 'aktif'], [$u->role, $u->status]);
        $this->assertTrue(Hash::check('rahasia123', $u->password_hash));
        $this->assertAuthenticatedAs($u);
        $this->assertContains('Akun berhasil dibuat. Selamat datang di ZIQO!', $this->toasts());
    }

    public function test_register_validasi_dan_email_ganda(): void
    {
        $this->buatUser('Lama', 'lama@ziqo.id');
        $this->post('/register', ['nama' => 'Lama Lagi', 'email' => 'LAMA@ziqo.id', 'password' => 'rahasia123', 'konfirmasi' => 'rahasia123'])
            ->assertSessionHasErrors(['email' => 'Email sudah terdaftar. Silakan masuk.']);
        $this->post('/register', ['nama' => 'Ab', 'email' => 'bukan-email', 'password' => 'pendek', 'konfirmasi' => 'lain'])
            ->assertSessionHasErrors(['nama', 'email', 'password', 'konfirmasi']);
        $this->assertSame(1, User::count());
        $this->assertGuest();
    }

    public function test_login_pesan_galat_dan_berhasil(): void
    {
        $u = $this->buatUser('Rina Putri', 'rina@ziqo.id');

        $this->post('/login', ['email' => '', 'password' => ''])->assertSessionHas('login_error', 'Email dan password wajib diisi.');
        $this->post('/login', ['email' => 'rina@ziqo.id', 'password' => 'salah123'])->assertSessionHas('login_error', 'Email atau password belum sesuai.');
        $this->post('/login', ['email' => 'tidakada@ziqo.id', 'password' => 'rahasia123'])->assertSessionHas('login_error', 'Email atau password belum sesuai.');
        $this->assertGuest();

        $this->post('/login', ['email' => '  RINA@ziqo.id ', 'password' => 'rahasia123', 'ingat' => '1'])
            ->assertRedirect('/dashboard')
            ->assertCookie('ziqo_email', 'rina@ziqo.id', false);
        $this->assertAuthenticatedAs($u);
        $this->assertContains('Selamat datang kembali, Rina Putri!', $this->toasts());
    }

    public function test_login_akun_nonaktif_ditolak(): void
    {
        $this->buatUser('Nonaktif', 'off@ziqo.id', status: 'nonaktif');
        $this->post('/login', ['email' => 'off@ziqo.id', 'password' => 'rahasia123'])
            ->assertSessionHas('login_error', 'Akun kamu sedang dinonaktifkan. Hubungi administrator.');
        $this->assertGuest();
    }

    public function test_login_memperbarui_hash_lama(): void
    {
        $u = $this->buatUser('Lama', 'lama@ziqo.id');
        $lama = password_hash('rahasia123', PASSWORD_BCRYPT, ['cost' => 10]);
        DB::table('users')->where('id', $u->id)->update(['password_hash' => $lama]);
        $this->assertTrue(Hash::needsRehash($lama));

        $this->post('/login', ['email' => 'lama@ziqo.id', 'password' => 'rahasia123'])->assertRedirect('/dashboard');
        $baru = $u->fresh()->password_hash;
        $this->assertNotSame($lama, $baru);
        $this->assertFalse(Hash::needsRehash($baru));
        $this->assertTrue(Hash::check('rahasia123', $baru));
    }

    // ---------- Profil ----------

    public function test_profil_validasi_dan_hanya_kolom_profil_yang_berubah(): void
    {
        $u = $this->buatUser('Rina Putri', 'rina@ziqo.id');

        $this->actingAs($u)->post('/profil', ['aksi' => 'info', 'nama' => 'Ri', 'telepon' => '08-12', 'jenjang' => 'xx', 'bio' => str_repeat('a', 301)])
            ->assertSessionHasErrors(['nama', 'telepon' => 'No. telepon hanya boleh angka (8–15 digit).', 'jenjang', 'bio'])
            ->assertSessionHas('tab', 'info');
        $this->assertSame('Rina Putri', $u->fresh()->nama);

        $this->actingAs($u)->post('/profil', [
            'aksi' => 'info', 'nama' => 'Rina Baru', 'telepon' => '+628123456789', 'jenjang' => 'sma', 'bio' => 'Halo',
            'email' => 'ganti@ziqo.id', 'role' => 'admin', 'status' => 'nonaktif',
        ])->assertRedirect('/profil');
        $f = $u->fresh();
        $this->assertSame(['Rina Baru', '+628123456789', 'sma', 'Halo'], [$f->nama, $f->telepon, $f->jenjang, $f->bio]);
        $this->assertSame(['rina@ziqo.id', 'peserta', 'aktif'], [$f->email, $f->role, $f->status]);
        $this->assertContains('Profil berhasil diperbarui.', $this->toasts());
    }

    public function test_ganti_password(): void
    {
        $u = $this->buatUser('Rina', 'rina@ziqo.id');

        $this->actingAs($u)->post('/profil', ['aksi' => 'password', 'password_lama' => 'salah999', 'password_baru' => 'barubaru1', 'konfirmasi' => 'barubaru1'])
            ->assertSessionHasErrors(['password_lama' => 'Password saat ini salah.'])
            ->assertSessionHas('tab', 'password');
        $this->actingAs($u)->post('/profil', ['aksi' => 'password', 'password_lama' => 'rahasia123', 'password_baru' => 'pendek', 'konfirmasi' => 'beda'])
            ->assertSessionHasErrors(['password_baru', 'konfirmasi']);
        $this->assertTrue(Hash::check('rahasia123', $u->fresh()->password_hash));

        $this->actingAs($u)->post('/profil', ['aksi' => 'password', 'password_lama' => 'rahasia123', 'password_baru' => 'barubaru1', 'konfirmasi' => 'barubaru1'])
            ->assertRedirect('/profil?tab=password');
        $this->assertTrue(Hash::check('barubaru1', $u->fresh()->password_hash));
        $this->assertContains('Password berhasil diganti.', $this->toasts());
    }

    public function test_ganti_password_mengeluarkan_sesi_lain_tapi_sesi_ini_tetap_masuk(): void
    {
        config(['session.driver' => 'database']);
        $this->buatUser('Rina', 'rina@ziqo.id');
        $cookie = config('session.cookie');

        // Tiap request = satu "browser": hanya membawa cookie sesinya sendiri, tanpa guard/store sisa request sebelumnya.
        $kirim = function (string $method, string $uri, array $data = [], ?string $sesi = null) use ($cookie) {
            $this->app['auth']->forgetGuards();
            $this->app['session']->forgetDrivers();
            $this->app->forgetInstance('session.store');
            $this->defaultCookies = $sesi ? [$cookie => $sesi] : [];
            $res = $method === 'GET' ? $this->get($uri) : $this->post($uri, $data);
            $this->defaultCookies = [];
            return $res;
        };
        $idSesi = fn ($res) => $res->getCookie($cookie)?->getValue();

        $a = $idSesi($kirim('POST', '/login', ['email' => 'rina@ziqo.id', 'password' => 'rahasia123'])->assertRedirect('/dashboard'));
        $b = $idSesi($kirim('POST', '/login', ['email' => 'rina@ziqo.id', 'password' => 'rahasia123'])->assertRedirect('/dashboard'));
        $this->assertNotEmpty($a);
        $this->assertNotSame($a, $b);
        $kirim('GET', '/dashboard', [], $a)->assertOk(); // kontrol: sesi A memang masuk

        $res = $kirim('POST', '/profil', ['aksi' => 'password', 'password_lama' => 'rahasia123', 'password_baru' => 'barubaru1', 'konfirmasi' => 'barubaru1'], $b)
            ->assertRedirect('/profil?tab=password');
        $b2 = $idSesi($res);
        $this->assertNotSame($b, $b2);
        $this->assertDatabaseMissing('sessions', ['id' => $b]); // ID sesi lama dihancurkan

        $kirim('GET', '/dashboard', [], $a)->assertRedirect('/login'); // sesi lain dikeluarkan
        $kirim('GET', '/dashboard', [], $b)->assertRedirect('/login');
        $kirim('GET', '/dashboard', [], $b2)->assertOk(); // sesi yang mengganti password tetap masuk
        $kirim('GET', '/profil', [], $b2)->assertOk();
    }

    public function test_login_dibatasi_setelah_lima_percobaan(): void
    {
        $this->buatUser('Rina', 'rina@ziqo.id');
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'rina@ziqo.id', 'password' => 'salah123'])->assertSessionHas('login_error');
        }

        // Percobaan ke-6 ditolak walau password benar; email beda huruf besar tetap dihitung sama.
        $this->post('/login', ['email' => ' RINA@ziqo.id', 'password' => 'rahasia123'])
            ->assertStatus(429)->assertHeader('Retry-After')->assertSee('Terlalu banyak percobaan. Coba lagi dalam beberapa saat.');
        $this->postJson('/login', ['email' => 'rina@ziqo.id', 'password' => 'rahasia123'])
            ->assertStatus(429)->assertExactJson(['ok' => false, 'pesan' => 'Terlalu banyak percobaan. Coba lagi dalam beberapa saat.']);
        $this->assertGuest();

        // Email lain dari IP yang sama belum kena batas per email
        $this->post('/login', ['email' => 'lain@ziqo.id', 'password' => 'salah123'])->assertSessionHas('login_error');
    }

    public function test_register_dibatasi_per_ip(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', ['nama' => '', 'email' => "x{$i}@ziqo.id"])->assertSessionHasErrors();
        }
        $this->post('/register', ['nama' => 'Baru', 'email' => 'baru@ziqo.id', 'password' => 'rahasia123', 'konfirmasi' => 'rahasia123'])
            ->assertStatus(429);
        $this->assertDatabaseMissing('users', ['email' => 'baru@ziqo.id']);
    }

    public function test_seeder_hanya_membuat_akun_demo_di_lokal_atau_testing(): void
    {
        config(['app.admin.email' => null, 'app.admin.password' => null]);
        $this->app->detectEnvironment(fn () => 'production');
        try {
            (new DatabaseSeeder)->run();
        } finally {
            $this->app->detectEnvironment(fn () => 'testing');
        }
        $this->assertDatabaseMissing('users', ['email' => 'admin@ziqo.id']);
        $this->assertSame([0, 0], [DB::table('users')->count(), DB::table('quizzes')->count()]);
        $this->assertGreaterThan(0, DB::table('categories')->count());
        $this->assertGreaterThan(0, DB::table('badges')->count());

        // Pembanding: di testing akun demo tetap dibuat
        foreach (['badges', 'subcategories', 'categories'] as $tabel) {
            DB::table($tabel)->delete();
        }
        (new DatabaseSeeder)->run();
        $this->assertDatabaseHas('users', ['email' => 'admin@ziqo.id', 'role' => 'admin']);
        $this->assertGreaterThan(0, DB::table('quizzes')->count());
    }

    public function test_admin_seeder_membuat_admin_dari_env_tanpa_menimpa_akun(): void
    {
        $seed = fn () => (new AdminSeeder)->run();

        // Env kosong: dilewati
        config(['app.admin.email' => null, 'app.admin.password' => null]);
        $seed();
        $this->assertDatabaseMissing('users', ['email' => 'kepala@sekolah.id']);

        // Env terisi: admin aktif dibuat, email dinormalisasi, password di-hash, bisa login
        config(['app.admin.nama' => 'Kepala Sekolah', 'app.admin.email' => ' Kepala@Sekolah.id ', 'app.admin.password' => 'rahasia-admin-1']);
        $seed();
        $admin = User::where('email', 'kepala@sekolah.id')->firstOrFail();
        $this->assertSame(['Kepala Sekolah', 'admin', 'aktif'], [$admin->nama, $admin->role, $admin->status]);
        $this->assertTrue(Hash::check('rahasia-admin-1', $admin->password_hash));
        $this->post('/login', ['email' => 'kepala@sekolah.id', 'password' => 'rahasia-admin-1'])->assertRedirect('/dashboard');
        $this->get('/admin/pengguna')->assertOk();

        // Dijalankan ulang dengan password lain: akun yang ada tidak ditimpa
        config(['app.admin.password' => 'password-lain-2']);
        $seed();
        $this->assertSame(1, User::where('email', 'kepala@sekolah.id')->count());
        $this->assertTrue(Hash::check('rahasia-admin-1', $admin->fresh()->password_hash));

        // Email yang sudah dipakai akun non-admin juga tidak diubah jadi admin
        $this->buatUser('Peserta Lama', 'lama@ziqo.id');
        config(['app.admin.email' => 'lama@ziqo.id']);
        $seed();
        $this->assertSame('peserta', User::where('email', 'lama@ziqo.id')->value('role'));

        // Password terlalu pendek ditolak
        config(['app.admin.email' => 'baru@sekolah.id', 'app.admin.password' => 'pendek']);
        $this->expectException(\RuntimeException::class);
        $seed();
    }

    // ---------- Admin: role & status ----------

    public function test_aturan_admin_mengubah_role_dan_status(): void
    {
        $admin = $this->buatUser('Admin Satu', 'admin@ziqo.id', 'admin');
        $admin2 = $this->buatUser('Admin Dua', 'admin2@ziqo.id', 'admin');
        $p = $this->buatUser('Peserta', 'p@ziqo.id');

        $kirim = fn (array $data) => $this->actingAs($admin)->post('/admin/pengguna', $data);
        $kasus = [
            [['aksi' => 'role', 'id' => $admin->id, 'role' => 'peserta'], 'Kamu tidak dapat mengubah role akunmu sendiri.'],
            [['aksi' => 'status', 'id' => $admin->id, 'status' => 'nonaktif'], 'Kamu tidak dapat menonaktifkan akunmu sendiri.'],
            [['aksi' => 'role', 'id' => $admin2->id, 'role' => 'peserta'], 'Akun administrator dilindungi dan tidak dapat diubah.'],
            [['aksi' => 'status', 'id' => $admin2->id, 'status' => 'nonaktif'], 'Akun administrator dilindungi dan tidak dapat dinonaktifkan.'],
            [['aksi' => 'role', 'id' => $p->id, 'role' => 'admin'], 'Role tidak valid.'],
            [['aksi' => 'status', 'id' => $p->id, 'status' => 'hapus'], 'Status tidak valid.'],
            [['aksi' => 'role', 'id' => 9999, 'role' => 'pengajar'], 'Pengguna tidak ditemukan.'],
            [['aksi' => 'hapus', 'id' => $p->id], 'Aksi tidak dikenal.'],
        ];
        foreach ($kasus as [$data, $pesan]) {
            session()->forget('toasts');
            $kirim($data)->assertRedirect('/admin/pengguna');
            $this->assertSame([['tipe' => 'error', 'pesan' => $pesan]], session('toasts'), $pesan);
        }
        $this->assertSame(['admin', 'aktif'], [$admin->fresh()->role, $admin->fresh()->status]);
        $this->assertSame(['admin', 'aktif'], [$admin2->fresh()->role, $admin2->fresh()->status]);
        $this->assertSame(['peserta', 'aktif'], [$p->fresh()->role, $p->fresh()->status]);

        session()->forget('toasts');
        $kirim(['aksi' => 'role', 'id' => $p->id, 'role' => 'pengajar', 'qs' => 'q=pes&role=peserta'])
            ->assertRedirect('/admin/pengguna?q=pes&role=peserta');
        $this->assertSame([['tipe' => 'sukses', 'pesan' => 'Akun Peserta diperbarui: Pengajar, aktif.']], session('toasts'));

        session()->forget('toasts');
        $kirim(['aksi' => 'status', 'id' => $p->id, 'status' => 'nonaktif', 'kembali' => 'detail'])
            ->assertRedirect('/admin/pengguna/' . $p->id);
        $this->assertSame([['tipe' => 'sukses', 'pesan' => 'Akun Peserta diperbarui: Pengajar, nonaktif.']], session('toasts'));
        $this->assertSame(['pengajar', 'nonaktif'], [$p->fresh()->role, $p->fresh()->status]);

        $kirim(['aksi' => 'role', 'id' => $p->id, 'role' => 'peserta', 'qs' => '<script>'])->assertRedirect('/admin/pengguna');
    }

    // ---------- Admin: cari & filter ----------

    public function test_pencarian_dan_filter_pengguna(): void
    {
        $this->buatFixture();
        $svc = app(UserService::class);

        $nama = fn (array $in) => $svc->search($svc->filters($in), 50, 0)->pluck('nama')->all();

        $this->assertSame(['Siti Peserta', 'Sari Dua', 'Tono Lain', 'Tari Guru', 'Admin Utama'], $nama([]));
        $this->assertSame(['Siti Peserta', 'Sari Dua'], $nama(['q' => 's']));
        $this->assertSame(['Tari Guru'], $nama(['q' => 'tari']));
        $this->assertSame(['Sari Dua'], $nama(['q' => 'sari2@']));
        $this->assertSame(['Tono Lain', 'Tari Guru'], $nama(['role' => 'pengajar']));
        $this->assertSame(['Sari Dua'], $nama(['status' => 'nonaktif']));
        $this->assertSame(['Siti Peserta'], $nama(['role' => 'peserta', 'status' => 'aktif']));
        $this->assertSame(['role' => '', 'status' => ''], array_slice($svc->filters(['role' => 'raja', 'status' => 'mati']), 1));
        $this->assertSame(5, count($nama(['role' => 'raja', 'status' => 'mati'])));
        $this->assertSame(100, mb_strlen($svc->filters(['q' => str_repeat('x', 150)])['q']));

        $this->assertSame(2, $svc->searchCount($svc->filters(['role' => 'peserta'])));
        $this->assertSame(['Sari Dua'], $svc->search($svc->filters(['role' => 'peserta']), 1, 1)->pluck('nama')->all());

        $rows = $svc->search($svc->filters([]), 50, 0)->keyBy('nama');
        $this->assertSame(5, (int) $rows['Siti Peserta']['jumlah_percobaan']);
        $this->assertSame(0, (int) $rows['Siti Peserta']['jumlah_kuis']);
        $this->assertSame(2, (int) $rows['Sari Dua']['jumlah_percobaan']);
        $this->assertSame(2, (int) $rows['Tari Guru']['jumlah_kuis']);
        $this->assertSame(1, (int) $rows['Tono Lain']['jumlah_kuis']);

        $this->assertEquals(['admin' => 1, 'pengajar' => 2, 'peserta' => 2], $svc->countByRole());

        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->get('/admin/pengguna?q=tari')->assertOk()->assertSee('Tari Guru')->assertDontSee('Siti Peserta');
        $this->actingAs($admin)->get('/admin/pengguna?q=zzz')->assertOk()->assertSee('Pengguna tidak ditemukan');
    }

    // ---------- Statistik ----------

    public function test_level_tiap_level_butuh_100_xp_lebih(): void
    {
        $s = app(StatsService::class);
        $this->assertSame(['level' => 1, 'progres' => 0.0, 'butuh' => 100], $s->level(0));
        $this->assertSame(['level' => 1, 'progres' => 99.0, 'butuh' => 100], $s->level(99));
        $this->assertSame(['level' => 2, 'progres' => 0.0, 'butuh' => 200], $s->level(100));
        $this->assertSame(['level' => 2, 'progres' => 140.0, 'butuh' => 200], $s->level(240));
        $this->assertSame(['level' => 3, 'progres' => 0.0, 'butuh' => 300], $s->level(300));
        $this->assertSame(['level' => 3, 'progres' => 299.0, 'butuh' => 300], $s->level(599));
        $this->assertSame(['level' => 4, 'progres' => 0.0, 'butuh' => 400], $s->level(600));
    }

    public function test_statistik_pribadi_hanya_selesai_dan_tidak_diarsipkan(): void
    {
        $fx = $this->buatFixture();
        $s = app(StatsService::class)->user($fx['siti']);

        $this->assertSame(3, (int) $s['total']);
        $this->assertEqualsWithDelta(80, (float) $s['rata'], 0.001);
        $this->assertEqualsWithDelta(100, (float) $s['tertinggi'], 0.001);
        $this->assertSame([12, 3], [(int) $s['benar'], (int) $s['salah']]);
        $this->assertEqualsWithDelta(240, (float) $s['xp'], 0.001);
        $this->assertSame(180, (int) $s['waktu']);
        $this->assertSame(1, (int) $s['menunggu']);
        $this->assertSame(1, (int) $s['badge']);

        $kosong = app(StatsService::class)->user($fx['tari']);
        $this->assertSame(0, (int) $kosong['total']);
        $this->assertNull($kosong['rata']);
        $this->assertNull($kosong['tertinggi']);
        $this->assertEquals(0, $kosong['xp']);
    }

    public function test_trend_kategori_dan_aktivitas(): void
    {
        $fx = $this->buatFixture();
        $s = app(StatsService::class);

        $trend = $s->trend($fx['siti']);
        $this->assertSame([$fx['a3'], $fx['a1'], $fx['a2']], $trend->pluck('id')->all());
        $this->assertSame(['Kuis Info', 'Kuis Mat', 'Kuis Mat'], $trend->pluck('judul')->all());
        $this->assertSame([$fx['a1'], $fx['a2']], $s->trend($fx['siti'], 2)->pluck('id')->all());

        $kat = $s->perCategory($fx['siti']);
        $this->assertSame(['Informatika', 'Matematika'], $kat->pluck('nama')->all());
        $this->assertSame(['hijau', 'biru'], $kat->pluck('warna')->all());
        $this->assertSame([1, 2], $kat->pluck('jumlah')->map(fn ($n) => (int) $n)->all());
        $this->assertEqualsWithDelta(70, (float) $kat[1]['rata'], 0.001);

        $recent = $s->recent($fx['siti'], 5);
        $this->assertSame([$fx['a6'], $fx['a2'], $fx['a1'], $fx['a4'], $fx['a3']], $recent->pluck('id')->all());
        $this->assertSame(['Kuis Mat', 'Matematika', 'biru', 'berlangsung'], [$recent[0]['judul'], $recent[0]['kategori'], $recent[0]['warna'], $recent[0]['status']]);
        $this->assertSame('Kuis Draft', $recent[3]['judul']);
        $this->assertCount(2, $s->recent($fx['siti'], 2));
    }

    public function test_daily_attempts_mengisi_hari_kosong_dengan_nol(): void
    {
        $fx = $this->buatFixture();
        $s = app(StatsService::class);
        $hari = fn (int $mundur) => today()->subDays($mundur)->format('Y-m-d');

        $semua = $s->dailyAttempts(14);
        $this->assertCount(14, $semua);
        $this->assertSame(['tgl' => $hari(13), 'n' => 0], $semua[0]);
        $this->assertSame(['tgl' => $hari(0), 'n' => 2], $semua[13]);
        $this->assertSame(['tgl' => $hari(1), 'n' => 2], $semua[12]);
        $this->assertSame(['tgl' => $hari(3), 'n' => 1], $semua[10]);
        $this->assertSame(['tgl' => $hari(5), 'n' => 1], $semua[8]);
        $this->assertSame(6, array_sum(array_column($semua, 'n')));

        $guru = $s->dailyAttempts(14, $fx['tari']);
        $this->assertSame([2, 2, 0, 1], [$guru[13]['n'], $guru[12]['n'], $guru[10]['n'], $guru[8]['n']]);
        $this->assertSame(5, array_sum(array_column($guru, 'n')));

        $tiga = $s->dailyAttempts(3);
        $this->assertSame([$hari(2), $hari(1), $hari(0)], array_column($tiga, 'tgl'));
        $this->assertSame([0, 2, 2], array_column($tiga, 'n'));
    }

    public function test_statistik_pengajar_platform_dan_esai_menunggu(): void
    {
        $fx = $this->buatFixture();
        $s = app(StatsService::class);

        $g = $s->teacher($fx['tari']);
        $this->assertSame([2, 1, 6, 2, 2], [$g['kuis'], $g['terbit'], $g['percobaan'], $g['peserta'], $g['menunggu']]);
        $this->assertEqualsWithDelta(47.5, (float) $g['rata'], 0.001);
        $this->assertNull($s->teacher($fx['admin'])['rata']);

        $p = $s->platform();
        $this->assertEquals(['admin' => 1, 'pengajar' => 2, 'peserta' => 2], $p['pengguna']);
        $this->assertSame([2, 1, 3, 2, 5, 1], [$p['kuis'], $p['draft'], $p['soal'], $p['kategori'], $p['percobaan'], $p['nonaktif']]);
        $this->assertEqualsWithDelta(58, (float) $p['rata'], 0.001);

        $esai = $s->pendingEssays(null);
        $this->assertSame([$fx['a4'], $fx['b2']], $esai->pluck('id')->all());
        $this->assertSame(['Kuis Draft', 'Siti Peserta'], [$esai[0]['judul'], $esai[0]['nama']]);
        $this->assertSame($fx['qDraft'], (int) $esai[0]['quiz_id']);
        $this->assertNotNull($esai[0]['selesai_pada']);
        $this->assertCount(2, $s->pendingEssays($fx['tari']));
        $this->assertCount(1, $s->pendingEssays($fx['tari'], 1));
        $this->assertCount(0, $s->pendingEssays($fx['tono']));
    }

    public function test_halaman_dashboard_profil_dan_detail_pengguna_tampil(): void
    {
        $fx = $this->buatFixture();
        $admin = User::findOrFail($fx['admin']);
        $siti = User::findOrFail($fx['siti']);
        $tari = User::findOrFail($fx['tari']);

        $this->actingAs($siti)->get('/dashboard')->assertOk()->assertSee('Kamu sudah menyelesaikan 3 kuis');
        $this->actingAs($tari)->get('/dashboard')->assertOk()->assertSee('2 pengerjaan menunggu penilaian esai');
        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Statistik platform');
        $this->actingAs($siti)->get('/profil')->assertOk()->assertSee('Siti Peserta');
        $this->actingAs($admin)->get('/admin/pengguna/' . $fx['siti'])->assertOk()->assertSee('Kuis Mat');
        $this->actingAs($admin)->get('/admin/pengguna/' . $fx['tari'])->assertOk()->assertSee('Kuis yang dibuat');
        $this->actingAs($admin)->get('/admin/pengguna/9999')->assertNotFound();
    }

    /**
     * Siti: a1 Mat selesai 80 (H-1 10:00), a2 Mat selesai 60 (H 00:00:30), a3 Info selesai 100 (H-3),
     * a4 Draft menunggu (H-1 08:00), a5 Mat selesai 10 diarsipkan (H-5), a6 Mat berlangsung.
     * Sari (nonaktif): b1 Mat selesai 40 (H-20), b2 Draft menunggu (H 00:00:10).
     * Tari memiliki Mat (terbit, 2 soal) dan Draft (1 soal); Tono memiliki Info (terbit, 1 soal).
     */
    private function buatFixture(): array
    {
        $t = fn (Carbon $c) => $c->format('Y-m-d H:i:s');
        $now = now();
        $buat = function (string $nama, string $email, string $role, string $status, Carbon $dibuat) {
            return DB::table('users')->insertGetId([
                'nama' => $nama, 'email' => $email, 'password_hash' => Hash::make('rahasia123'),
                'role' => $role, 'status' => $status, 'created_at' => $dibuat->format('Y-m-d H:i:s'),
            ]);
        };
        $fx = [
            'admin' => $buat('Admin Utama', 'admin@ziqo.id', 'admin', 'aktif', $now->copy()->subDays(5)),
            'tari' => $buat('Tari Guru', 'tari@ziqo.id', 'pengajar', 'aktif', $now->copy()->subDays(4)),
            'tono' => $buat('Tono Lain', 'tono@ziqo.id', 'pengajar', 'aktif', $now->copy()->subDays(3)),
            'sari' => $buat('Sari Dua', 'sari2@ziqo.id', 'peserta', 'nonaktif', $now->copy()->subDays(2)),
            'siti' => $buat('Siti Peserta', 'siti@ziqo.id', 'peserta', 'aktif', $now->copy()->subDays(1)),
        ];
        $mat = DB::table('categories')->insertGetId(['nama' => 'Matematika', 'slug' => 'matematika', 'warna' => 'biru', 'created_at' => $t($now)]);
        $info = DB::table('categories')->insertGetId(['nama' => 'Informatika', 'slug' => 'informatika', 'warna' => 'hijau', 'created_at' => $t($now)]);

        $kuis = fn (int $owner, int $cat, string $judul, string $status) => DB::table('quizzes')->insertGetId([
            'user_id' => $owner, 'category_id' => $cat, 'judul' => $judul, 'status' => $status, 'created_at' => $t($now),
        ]);
        $fx['qMat'] = $kuis($fx['tari'], $mat, 'Kuis Mat', 'terbit');
        $fx['qDraft'] = $kuis($fx['tari'], $mat, 'Kuis Draft', 'draft');
        $fx['qInfo'] = $kuis($fx['tono'], $info, 'Kuis Info', 'terbit');
        foreach ([$fx['qMat'], $fx['qMat'], $fx['qDraft'], $fx['qInfo']] as $i => $q) {
            DB::table('questions')->insert(['quiz_id' => $q, 'tipe' => 'pg', 'teks' => 'Soal ' . $i, 'poin' => 10, 'urutan' => $i]);
        }

        $h = fn (int $mundur, string $jam) => $t(today()->subDays($mundur)->setTimeFromTimeString($jam));
        $coba = function (string $user, string $quiz, string $status, ?string $selesai, ?float $skor = null, int $benar = 0, int $salah = 0, int $durasi = 0, bool $arsip = false) use ($fx, $t, $now) {
            return DB::table('quiz_attempts')->insertGetId([
                'quiz_id' => $fx[$quiz], 'user_id' => $fx[$user], 'status' => $status,
                'mulai_pada' => $selesai ?? $t($now), 'selesai_pada' => $selesai,
                'durasi_detik' => $selesai ? $durasi : null,
                'poin_didapat' => $skor, 'poin_maks' => $skor === null ? null : 100, 'skor' => $status === 'selesai' ? $skor : null,
                'jumlah_benar' => $selesai ? $benar : null, 'jumlah_salah' => $selesai ? $salah : null,
                'diarsipkan' => $arsip,
            ]);
        };
        $fx['a1'] = $coba('siti', 'qMat', 'selesai', $h(1, '10:00:00'), 80, 4, 1, 100);
        $fx['a2'] = $coba('siti', 'qMat', 'selesai', $h(0, '00:00:30'), 60, 3, 2, 50);
        $fx['a3'] = $coba('siti', 'qInfo', 'selesai', $h(3, '12:00:00'), 100, 5, 0, 30);
        $fx['a4'] = $coba('siti', 'qDraft', 'menunggu', $h(1, '08:00:00'), 20, 1, 0, 20);
        $fx['a5'] = $coba('siti', 'qMat', 'selesai', $h(5, '12:00:00'), 10, 0, 5, 40, true);
        $fx['a6'] = $coba('siti', 'qMat', 'berlangsung', null);
        $fx['b1'] = $coba('sari', 'qMat', 'selesai', $h(20, '12:00:00'), 40, 2, 3, 60);
        $fx['b2'] = $coba('sari', 'qDraft', 'menunggu', $h(0, '00:00:10'), 0, 0, 0, 10);

        $badge = DB::table('badges')->insertGetId(['kode' => 'first', 'nama' => 'First Step', 'deskripsi' => 'Kuis pertama', 'syarat' => 'jumlah_kuis', 'nilai_syarat' => 1]);
        DB::table('user_badges')->insert(['user_id' => $fx['siti'], 'badge_id' => $badge, 'attempt_id' => $fx['a3'], 'diperoleh_pada' => $h(3, '12:00:00')]);

        return $fx;
    }
}
