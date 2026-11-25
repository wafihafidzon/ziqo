<?php

namespace Tests\Feature;

use App\Models\AnswerOption;
use App\Models\Attempt;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subcategory;
use App\Models\User;
use App\Services\QuestionService;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KuisTest extends TestCase
{
    use RefreshDatabase;

    // ZiqoTest butuh data seed; database in-memory hanya dimigrasi sekali per run.
    protected bool $seed = true;

    private Category $katA;
    private Category $katB;
    private Subcategory $topikA;
    private User $guru;
    private User $guruLain;
    private User $admin;
    private User $peserta;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['user_answer_options', 'user_answers', 'user_badges', 'quiz_attempts', 'answer_options', 'questions', 'quizzes'] as $t) {
            DB::table($t)->delete();
        }
        $this->katA = Category::create(['nama' => 'Uji Alpha', 'slug' => 'uji-alpha', 'warna' => 'hijau']);
        $this->katB = Category::create(['nama' => 'Uji Beta', 'slug' => 'uji-beta', 'warna' => 'biru']);
        $this->topikA = Subcategory::create(['category_id' => $this->katA->id, 'nama' => 'Trigonometri Uji']);
        $this->guru = $this->buatUser('Guru Satu', 'pengajar');
        $this->guruLain = $this->buatUser('Guru Dua', 'pengajar');
        $this->admin = $this->buatUser('Admin Uji', 'admin');
        $this->peserta = $this->buatUser('Peserta Uji', 'peserta', 'sma');
    }

    private function buatUser(string $nama, string $role, ?string $jenjang = null): User
    {
        return User::create([
            'nama' => $nama, 'email' => strtolower(str_replace(' ', '', $nama)) . '@uji.test', 'password_hash' => bcrypt('rahasia123'),
            'role' => $role, 'status' => 'aktif', 'jenjang' => $jenjang, 'created_at' => now(),
        ]);
    }

    private function buatKuis(array $atr = [], array $tipe = ['pg']): Quiz
    {
        $quiz = Quiz::create($atr + [
            'user_id' => $this->guru->id, 'category_id' => $this->katA->id, 'judul' => 'Kuis Uji', 'kesulitan' => 'mudah',
            'jenjang' => 'umum', 'durasi_menit' => 0, 'status' => 'terbit', 'created_at' => now(),
        ]);
        foreach ($tipe as $i => $t) {
            $soal = $quiz->questions()->create(['tipe' => $t, 'teks' => "Soal nomor $i", 'poin' => 10, 'urutan' => $i + 1]);
            if (in_array($t, ['pg', 'pgk', 'bs'], true)) {
                $soal->opsi()->createMany([['teks' => 'A', 'benar' => 1, 'urutan' => 1], ['teks' => 'B', 'benar' => 0, 'urutan' => 2]]);
            } elseif ($t === 'isian') {
                $soal->opsi()->create(['teks' => 'jawab', 'benar' => 1, 'urutan' => 1]);
            }
        }
        return $quiz;
    }

    private function percobaan(Quiz $quiz, User $user, string $status = 'selesai', ?float $skor = 80, ?int $durasi = 60): Attempt
    {
        return Attempt::create([
            'quiz_id' => $quiz->id, 'user_id' => $user->id, 'status' => $status, 'mulai_pada' => now()->format('Y-m-d H:i:s'),
            'selesai_pada' => $status === 'berlangsung' ? null : now()->format('Y-m-d H:i:s'),
            'skor' => $status === 'selesai' ? $skor : null, 'durasi_detik' => $status === 'berlangsung' ? null : $durasi,
        ]);
    }

    private function ids($quizzes): array
    {
        return collect($quizzes)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function jelajah(array $f, ?User $user = null): array
    {
        $f += ['sort' => 'terbaru'];
        return $this->ids(app(QuizService::class)->explore($f, 50, 0, $user));
    }

    private function assertToast(string $pesan): void
    {
        $this->assertContains($pesan, array_column(session('toasts', []), 'pesan'));
    }

    public function test_jelajah_hanya_kuis_terbit_yang_punya_soal(): void
    {
        $this->buatKuis(['judul' => 'Draft Bersoal', 'status' => 'draft']);
        $this->buatKuis(['judul' => 'Terbit Kosong'], []);
        $ok = $this->buatKuis(['judul' => 'Terbit Bersoal']);

        $this->assertSame([$ok->id], $this->jelajah([]));
        $this->assertSame(1, app(QuizService::class)->exploreCount([]));
        $this->get('/jelajah')->assertOk()->assertSee('Terbit Bersoal')->assertDontSee('Draft Bersoal')->assertDontSee('Terbit Kosong');
    }

    public function test_filter_jelajah(): void
    {
        $judul = $this->buatKuis(['judul' => 'Persamaan Kuadrat', 'kesulitan' => 'sulit']);
        $desk = $this->buatKuis(['judul' => 'Kuis Dua', 'deskripsi' => 'Latihan kuadrat dasar', 'category_id' => $this->katB->id], ['esai']);
        $topik = $this->buatKuis(['judul' => 'Kuis Tiga', 'subcategory_id' => $this->topikA->id, 'jenjang' => 'smp'], ['isian']);

        $this->assertEqualsCanonicalizing([$judul->id, $desk->id], $this->jelajah(['q' => 'kuadrat']));
        $this->assertSame([$topik->id], $this->jelajah(['q' => 'trigonometri']));
        $this->assertEqualsCanonicalizing([$judul->id, $topik->id], $this->jelajah(['kategori' => 'uji-alpha']));
        $this->assertSame([$topik->id], $this->jelajah(['topik' => $this->topikA->id]));
        $this->assertSame([$desk->id], $this->jelajah(['tipe' => 'esai']));
        $this->assertCount(3, $this->jelajah(['tipe' => 'tidak-ada']));
        $this->assertSame([$judul->id], $this->jelajah(['kesulitan' => 'sulit']));
        $this->assertSame([$topik->id], $this->jelajah(['jenjang' => 'smp']));
        $this->assertSame(1, app(QuizService::class)->exploreCount(['kategori' => 'uji-beta']));

        $this->get('/jelajah?kategori=uji-alpha&topik=' . $this->topikA->id)->assertOk()->assertSee('Kuis Tiga')->assertDontSee('Persamaan Kuadrat');
        // Topik di luar kategori aktif diabaikan.
        [$f] = app(QuizService::class)->exploreFilters(['kategori' => 'uji-beta', 'topik' => $this->topikA->id, 'sort' => 'rekomendasi'], false);
        $this->assertSame('', $f['topik']);
        $this->assertSame('populer', $f['sort']);
    }

    public function test_urutan_jelajah(): void
    {
        $lama = $this->buatKuis(['judul' => 'Bahasa', 'created_at' => now()->subDays(3)]);
        $tengah = $this->buatKuis(['judul' => 'Aljabar', 'created_at' => now()->subDays(2)]);
        $baru = $this->buatKuis(['judul' => 'Cuaca', 'created_at' => now()->subDay()]);
        $this->percobaan($lama, $this->peserta);
        $this->percobaan($lama, $this->guruLain, 'menunggu');
        $this->percobaan($tengah, $this->peserta);
        $this->percobaan($baru, $this->peserta, 'berlangsung');
        $this->percobaan($baru, $this->guruLain, 'berlangsung');

        $this->assertSame([$baru->id, $tengah->id, $lama->id], $this->jelajah(['sort' => 'terbaru']));
        $this->assertSame([$lama->id, $tengah->id, $baru->id], $this->jelajah(['sort' => 'populer']));
        $this->assertSame([$tengah->id, $lama->id, $baru->id], $this->jelajah(['sort' => 'judul']));
        $this->assertSame(2, (int) app(QuizService::class)->find($lama->id)->jumlah_percobaan);
        $this->assertSame(0, (int) app(QuizService::class)->find($baru->id)->jumlah_percobaan);

        foreach (array_keys(Quiz::SORT) as $sort) {
            $this->get("/jelajah?sort=$sort")->assertOk();
            $this->actingAs($this->peserta)->get("/jelajah?sort=$sort")->assertOk();
        }
    }

    public function test_urutan_rekomendasi(): void
    {
        // Peserta berjenjang sma dan sudah menyelesaikan satu kuis kategori A.
        $selesai = $this->buatKuis(['judul' => 'Sudah Selesai', 'jenjang' => 'sma', 'created_at' => now()->subDays(5)]);
        $katSama = $this->buatKuis(['judul' => 'Kategori Sama', 'created_at' => now()->subDays(4)]);
        $jenjang = $this->buatKuis(['judul' => 'Jenjang Sama', 'category_id' => $this->katB->id, 'jenjang' => 'sma', 'created_at' => now()->subDays(3)]);
        $lain = $this->buatKuis(['judul' => 'Lainnya', 'category_id' => $this->katB->id, 'created_at' => now()->subDays(2)]);
        $this->percobaan($selesai, $this->peserta);
        // Percobaan berlangsung tidak menambah skor kategori B.
        $this->percobaan($lain, $this->peserta, 'berlangsung');

        // skor: katSama 3, jenjang 2, selesai 3+2-5=0 (1 percobaan), lain 0 (0 percobaan)
        $this->assertSame([$katSama->id, $jenjang->id, $selesai->id, $lain->id], $this->jelajah(['sort' => 'rekomendasi'], $this->peserta));
        $this->assertSame([$katSama->id, $jenjang->id, $selesai->id], $this->ids(app(QuizService::class)->recommended($this->peserta)));

        // Tanpa riwayat: hanya jenjang (+2); seri diurutkan jumlah_percobaan lalu created_at terbaru.
        $baru = $this->buatUser('Peserta Baru', 'peserta', 'sma');
        $this->assertSame([$selesai->id, $jenjang->id, $lain->id, $katSama->id],$this->jelajah(['sort' => 'rekomendasi'], $baru));
    }

    public function test_atribut_kartu_dan_statistik(): void
    {
        $quiz = $this->buatKuis(['subcategory_id' => $this->topikA->id], ['pg', 'esai', 'esai', 'bs']);
        $this->percobaan($quiz, $this->peserta, 'selesai', 80, 90);
        $this->percobaan($quiz, $this->peserta, 'selesai', 100, 120);
        $this->percobaan($quiz, $this->guruLain, 'menunggu');
        $this->percobaan($quiz, $this->admin, 'berlangsung');

        $k = app(QuizService::class)->find($quiz->id);
        $this->assertSame('Uji Alpha', $k['kategori']);
        $this->assertSame('uji-alpha', $k['kategori_slug']);
        $this->assertSame('hijau', $k['warna']);
        $this->assertSame('Trigonometri Uji', $k['topik']);
        $this->assertSame('Guru Satu', $k['pembuat']);
        $this->assertSame(4, (int) $k['jumlah_soal']);
        $this->assertSame(2, (int) $k['jumlah_esai']);
        $this->assertSame(3, (int) $k['jumlah_percobaan']);
        $this->assertEqualsCanonicalizing(['pg', 'esai', 'bs'], explode(',', $k['daftar_tipe']));
        $this->assertNull(app(QuizService::class)->find($this->buatKuis([], [])->id)->daftar_tipe);
        $this->assertNull(app(QuizService::class)->find(999999));

        $this->assertSame(['percobaan' => 3, 'peserta' => 2, 'rata' => 90.0, 'menunggu' => 1], app(QuizService::class)->stats($quiz->id));
        $papan = app(QuizService::class)->leaderboard($quiz->id);
        $this->assertCount(1, $papan);
        $this->assertSame('Peserta Uji', $papan[0]['nama']);
        $this->assertEquals(100, $papan[0]['skor_terbaik']);
        $this->assertEquals(90, $papan[0]['durasi_tercepat']);
        $this->assertSame(['percobaan' => 0, 'peserta' => 0, 'rata' => null, 'menunggu' => 0], app(QuizService::class)->stats(999999));

        $this->actingAs($this->peserta)->get("/kuis/{$quiz->id}")->assertOk()->assertSee('Peserta Uji')->assertSee('Kerjakan lagi');
        $this->get('/')->assertOk();
    }

    public function test_hak_kelola_pemilik_pengajar_lain_dan_admin(): void
    {
        $quiz = $this->buatKuis(['status' => 'draft']);

        $this->actingAs($this->guru)->get("/pengajar/kuis/{$quiz->id}/soal")->assertOk();
        $this->actingAs($this->guru)->get("/pengajar/kuis/{$quiz->id}/edit")->assertOk();
        $this->actingAs($this->guruLain)->get("/pengajar/kuis/{$quiz->id}/soal")
            ->assertForbidden()->assertSee('Kuis ini milik pengajar lain');
        $this->actingAs($this->guruLain)->post('/pengajar/kuis', ['aksi' => 'terbit', 'id' => $quiz->id])->assertForbidden();
        $this->actingAs($this->admin)->get("/pengajar/kuis/{$quiz->id}/soal")->assertOk();
        $this->actingAs($this->peserta)->get("/pengajar/kuis/{$quiz->id}/soal")->assertForbidden();
        $this->actingAs($this->guru)->get('/pengajar/kuis/999999/soal')->assertNotFound();

        // Draft hanya terlihat oleh pemilik dan admin.
        $this->actingAs($this->guru)->get("/kuis/{$quiz->id}")->assertOk();
        $this->actingAs($this->admin)->get("/kuis/{$quiz->id}")->assertOk();
        $this->actingAs($this->guruLain)->get("/kuis/{$quiz->id}")->assertNotFound();
        $this->actingAs($this->peserta)->get("/kuis/{$quiz->id}")->assertNotFound();

        $svc = app(QuizService::class);
        $this->assertTrue($svc->canManage($quiz, $this->guru));
        $this->assertTrue($svc->canManage($quiz, $this->admin));
        $this->assertFalse($svc->canManage($quiz, $this->guruLain));
        $this->assertFalse($svc->canManage($quiz, null));
    }

    public function test_draft_tidak_bisa_dimulai(): void
    {
        $quiz = $this->buatKuis(['status' => 'draft']);
        // Pengajar pemilik tidak mengerjakan kuis; peserta tidak bisa melihat draft sama sekali.
        $this->actingAs($this->guru)->post("/kuis/{$quiz->id}")->assertRedirect(route('kuis.show', $quiz->id));
        $this->assertToast('Pengajar dan administrator tidak mengerjakan kuis. Gunakan Kelola soal untuk memeriksa isi kuis.');
        $peserta = User::where('role', 'peserta')->firstOrFail();
        $this->actingAs($peserta)->post("/kuis/{$quiz->id}")->assertNotFound();
        $this->assertSame(0, Attempt::count());
    }

    public function test_buat_edit_terbit_draft_dan_hapus_kuis(): void
    {
        $this->actingAs($this->guru)->post('/pengajar/kuis/buat', ['judul' => 'abc', 'category_id' => 999, 'kesulitan' => 'x', 'jenjang' => 'umum', 'durasi_menit' => 0])
            ->assertSessionHasErrors(['judul', 'category_id' => 'Kategori tidak ditemukan.', 'kesulitan']);
        $this->post('/pengajar/kuis/buat', ['judul' => 'Kuis Topik Salah', 'category_id' => $this->katB->id, 'subcategory_id' => $this->topikA->id, 'kesulitan' => 'mudah', 'jenjang' => 'umum', 'durasi_menit' => 0])
            ->assertSessionHasErrors(['subcategory_id' => 'Topik tidak sesuai dengan kategori.']);

        $this->post('/pengajar/kuis/buat', ['judul' => 'Kuis Baru Uji', 'category_id' => $this->katA->id, 'subcategory_id' => $this->topikA->id, 'kesulitan' => 'menengah', 'jenjang' => 'sma', 'durasi_menit' => 15, 'navigasi_bebas' => '1']);
        $quiz = Quiz::where('judul', 'Kuis Baru Uji')->firstOrFail();
        $this->assertSame('draft', $quiz->status);
        $this->assertSame($this->guru->id, (int) $quiz->user_id);
        $this->assertTrue($quiz->navigasi_bebas);
        $this->assertFalse($quiz->tampilkan_feedback);

        $this->post('/pengajar/kuis', ['aksi' => 'terbit', 'id' => $quiz->id])->assertRedirect(route('pengajar.kuis'));
        $this->assertToast('Tambahkan minimal satu soal sebelum menerbitkan kuis.');
        $this->assertSame('draft', $quiz->fresh()->status);

        $this->post("/pengajar/kuis/{$quiz->id}/soal/buat?tipe=esai", ['teks' => 'Jelaskan sesuatu.', 'poin' => 20]);
        $this->post('/pengajar/kuis', ['aksi' => 'terbit', 'id' => $quiz->id]);
        $this->assertSame('terbit', $quiz->fresh()->status);
        $this->post('/pengajar/kuis', ['aksi' => 'draft', 'id' => $quiz->id, 'kembali' => 'admin'])->assertRedirect(route('admin.kuis'));
        $this->assertSame('draft', $quiz->fresh()->status);

        $this->post("/pengajar/kuis/{$quiz->id}/edit", ['judul' => 'Kuis Diubah', 'category_id' => $this->katA->id, 'kesulitan' => 'sulit', 'jenjang' => 'umum', 'durasi_menit' => 0, 'tampilkan_feedback' => '1'])
            ->assertRedirect(route('pengajar.soal', $quiz->id));
        $quiz->refresh();
        $this->assertSame(['Kuis Diubah', 'sulit', null, true, false], [$quiz->judul, $quiz->kesulitan, $quiz->subcategory_id, $quiz->tampilkan_feedback, $quiz->navigasi_bebas]);

        // Percobaan apa pun (termasuk berlangsung) mencegah penghapusan.
        $attempt = $this->percobaan($quiz, $this->peserta, 'berlangsung');
        $this->post('/pengajar/kuis', ['aksi' => 'hapus', 'id' => $quiz->id]);
        $this->assertToast('Kuis sudah pernah dikerjakan sehingga tidak dapat dihapus. Jadikan draft untuk menyembunyikannya.');
        $this->assertNotNull($quiz->fresh());

        $attempt->delete();
        $this->post('/pengajar/kuis', ['aksi' => 'hapus', 'id' => $quiz->id]);
        $this->assertToast('Kuis "Kuis Diubah" dihapus.');
        $this->assertNull($quiz->fresh());
        $this->assertSame(0, Question::where('quiz_id', $quiz->id)->count());
    }

    public function test_validasi_soal(): void
    {
        $quiz = $this->buatKuis(['status' => 'draft'], []);
        $url = "/pengajar/kuis/{$quiz->id}/soal/buat";
        $this->actingAs($this->guru);
        $dasar = ['teks' => 'Pertanyaan uji', 'poin' => 10];

        $kasus = [
            ['pg', ['opsi' => ['A', '', ''], 'kunci' => '0'], 'opsi', 'Isi minimal 2 opsi jawaban.'],
            ['pg', ['opsi' => ['A', 'B', 'C', 'D', 'E', 'F', 'G'], 'kunci' => '0'], 'opsi', 'Maksimal 6 opsi jawaban.'],
            ['pg', ['opsi' => ['Sama', 'sama'], 'kunci' => '0'], 'opsi', 'Opsi jawaban tidak boleh sama.'],
            ['pg', ['opsi' => ['A', 'B']], 'kunci', 'Pilih tepat satu kunci jawaban.'],
            ['pgk', ['opsi' => ['A', 'B', 'C']], 'kunci', 'Tandai minimal satu jawaban benar.'],
            ['pgk', ['opsi' => ['A', 'B'], 'kunci' => ['0', '1']], 'kunci', 'Tidak semua opsi boleh benar.'],
            ['pg', ['opsi' => [str_repeat('x', 256), 'B'], 'kunci' => '1'], 'opsi', 'Teks opsi maksimal 255 karakter.'],
            ['bs', [], 'kunci_bs', 'Pilih kunci jawaban Benar atau Salah.'],
            ['isian', ['jawaban' => ['', ' ']], 'jawaban', 'Isi minimal satu jawaban yang diterima.'],
            ['isian', ['jawaban' => array_map(fn ($i) => "j$i", range(1, 9))], 'jawaban', 'Maksimal 8 variasi jawaban.'],
        ];
        foreach ($kasus as [$tipe, $isi, $kunci, $pesan]) {
            $this->post("$url?tipe=$tipe", $dasar + $isi)->assertSessionHasErrors([$kunci => $pesan]);
        }
        $this->post("$url?tipe=esai", ['teks' => 'abc', 'poin' => 0])->assertSessionHasErrors(['teks', 'poin']);
        $this->assertSame(0, Question::count());

        $this->post("$url?tipe=pgk", $dasar + ['opsi' => ['A', '', 'C', 'D'], 'kunci' => ['2']])->assertRedirect(route('pengajar.soal', $quiz->id));
        $soal = Question::firstOrFail();
        $this->assertSame(1, $soal->urutan);
        $this->assertSame([['A', false, 1], ['C', true, 2], ['D', false, 3]], $soal->opsi->map(fn ($o) => [$o->teks, $o->benar, $o->urutan])->all());

        $this->post("$url?tipe=bs", $dasar + ['kunci_bs' => 'salah', 'lagi' => '1'])->assertRedirect(route('pengajar.soal.create', [$quiz->id, 'tipe' => 'bs']));
        $bs = Question::where('tipe', 'bs')->firstOrFail();
        $this->assertSame(2, $bs->urutan);
        $this->assertSame([['Benar', false], ['Salah', true]], $bs->opsi->map(fn ($o) => [$o->teks, $o->benar])->all());
    }

    public function test_edit_soal_mempertahankan_id_opsi_per_posisi(): void
    {
        $quiz = $this->buatKuis(['status' => 'draft'], []);
        $this->actingAs($this->guru)->post("/pengajar/kuis/{$quiz->id}/soal/buat?tipe=pg", ['teks' => 'Pilih satu', 'poin' => 10, 'opsi' => ['A', 'B', 'C', 'D'], 'kunci' => '0']);
        $soal = Question::firstOrFail();
        $idLama = $soal->opsi->modelKeys();
        $this->get("/pengajar/kuis/{$quiz->id}/soal/{$soal->id}/edit")->assertOk()->assertSee('value="D"', false);

        // Tipe dari form diabaikan: jenis soal yang sudah ada tidak berubah lewat form.
        $this->post("/pengajar/kuis/{$quiz->id}/soal/{$soal->id}/edit", ['tipe' => 'isian', 'teks' => 'Pilih satu lagi', 'poin' => 15, 'opsi' => ['A2', 'B2', 'C2'], 'kunci' => '2'])
            ->assertRedirect(route('pengajar.soal', $quiz->id));
        $soal->refresh();
        $this->assertSame(['pg', 15], [$soal->tipe, $soal->poin]);
        $this->assertSame(array_slice($idLama, 0, 3), $soal->opsi->modelKeys());
        $this->assertSame([['A2', false], ['B2', false], ['C2', true]], $soal->opsi->map(fn ($o) => [$o->teks, $o->benar])->all());
        $this->assertNull(AnswerOption::find($idLama[3]));

        $this->post("/pengajar/kuis/{$quiz->id}/soal/{$soal->id}/edit", ['teks' => 'Pilih satu lagi', 'poin' => 15, 'opsi' => ['A3', 'B3', 'C3', 'D3', 'E3'], 'kunci' => '4']);
        $baru = $soal->fresh()->opsi->modelKeys();
        $this->assertCount(5, $baru);
        $this->assertSame(array_slice($idLama, 0, 3), array_slice($baru, 0, 3));

        // Ganti jenis soal (via service) menghapus semua opsi lama.
        $svc = app(QuestionService::class);
        [$data, $opsi, $errors] = $svc->validate(['tipe' => 'isian', 'teks' => 'Isi jawabannya', 'poin' => 5, 'jawaban' => ['satu', 'dua']]);
        $this->assertSame([], $errors);
        $svc->save($quiz, $soal->fresh(), $data, $opsi);
        $opsiIsian = $soal->fresh()->opsi;
        $this->assertSame(['satu', 'dua'], $opsiIsian->pluck('teks')->all());
        $this->assertSame([], array_intersect($baru, $opsiIsian->modelKeys()));
    }

    public function test_urutkan_soal_naik_turun(): void
    {
        $quiz = $this->buatKuis([], ['pg', 'bs', 'esai']);
        [$a, $b, $c] = Question::where('quiz_id', $quiz->id)->urut()->pluck('id')->all();
        $urut = fn () => Question::where('quiz_id', $quiz->id)->urut()->pluck('urutan', 'id')->all();
        $url = "/pengajar/kuis/{$quiz->id}/soal";
        $this->actingAs($this->guru);

        $this->post($url, ['aksi' => 'naik', 'soal' => $a])->assertRedirect(route('pengajar.soal', $quiz->id));
        $this->assertSame([$a => 1, $b => 2, $c => 3], $urut());
        $this->post($url, ['aksi' => 'turun', 'soal' => $a]);
        $this->assertSame([$b => 1, $a => 2, $c => 3], $urut());
        $this->post($url, ['aksi' => 'naik', 'soal' => $c]);
        $this->assertSame([$b => 1, $c => 2, $a => 3], $urut());
        $this->post($url, ['aksi' => 'turun', 'soal' => $a]);
        $this->assertSame([$b => 1, $c => 2, $a => 3], $urut());

        $this->post($url, ['aksi' => 'naik', 'soal' => 999999])->assertNotFound();
        $lain = $this->buatKuis([], ['pg']);
        $this->post($url, ['aksi' => 'hapus', 'soal' => $lain->questions()->value('id')])->assertNotFound();
        $this->get($url)->assertOk();
    }

    public function test_hapus_soal_terakhir_kuis_terbit_ditolak(): void
    {
        $quiz = $this->buatKuis([], ['pg', 'bs']);
        [$a, $b] = Question::where('quiz_id', $quiz->id)->urut()->pluck('id')->all();
        $url = "/pengajar/kuis/{$quiz->id}/soal";
        $this->actingAs($this->guru);

        $this->post($url, ['aksi' => 'hapus', 'soal' => $a]);
        $this->assertToast('Soal dihapus.');
        $this->post($url, ['aksi' => 'hapus', 'soal' => $b]);
        $this->assertToast('Kuis terbit harus memiliki minimal satu soal. Jadikan draft terlebih dahulu.');
        $this->assertNotNull(Question::find($b));

        $quiz->update(['status' => 'draft']);
        $this->post($url, ['aksi' => 'hapus', 'soal' => $b]);
        $this->assertNull(Question::find($b));
    }

    public function test_daftar_kuis_pengajar_dan_admin(): void
    {
        $milik = $this->buatKuis(['judul' => 'Milik Guru Satu', 'status' => 'draft']);
        $lain = $this->buatKuis(['judul' => 'Milik Guru Dua', 'user_id' => $this->guruLain->id, 'category_id' => $this->katB->id]);

        $this->assertSame([$milik->id], $this->ids(app(QuizService::class)->forOwner($this->guru->id)));
        $this->actingAs($this->guru)->get('/pengajar/kuis')->assertOk()->assertSee('Milik Guru Satu')->assertDontSee('Milik Guru Dua');

        $svc = app(QuizService::class);
        $this->assertSame([$lain->id], $this->ids($svc->adminList(['q' => 'Guru Dua', 'status' => '', 'kategori' => ''], 15)));
        $this->assertSame([$milik->id], $this->ids($svc->adminList(['q' => '', 'status' => 'draft', 'kategori' => ''], 15)));
        $this->assertSame([$lain->id], $this->ids($svc->adminList(['q' => '', 'status' => '', 'kategori' => 'uji-beta'], 15)));
        $this->assertSame(2, $svc->adminCount(['q' => '', 'status' => 'semua', 'kategori' => '']));

        $this->actingAs($this->admin)->get('/admin/kuis?q=Guru+Dua')->assertOk()->assertSee('Milik Guru Dua')->assertDontSee('Milik Guru Satu');
        $this->actingAs($this->admin)->get('/admin/kuis?status=terbit&kategori=uji-alpha')->assertOk()->assertSee('Tidak ada kuis');
        $this->actingAs($this->guru)->get('/admin/kuis')->assertForbidden();
    }
}
