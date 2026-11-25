<?php

namespace Tests\Feature;

use App\Models\AnswerOption;
use App\Models\Attempt;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use App\Models\UserAnswer;
use App\Services\AttemptService;
use App\Services\BadgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PengerjaanTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $peserta;
    private User $guru;

    protected function setUp(): void
    {
        parent::setUp();
        $this->peserta = User::where('email', 'demo@ziqo.id')->firstOrFail();
        $this->guru = User::where('email', 'guru@ziqo.id')->firstOrFail();
    }

    private function service(): AttemptService
    {
        return app(AttemptService::class);
    }

    private function kategori(string $nama): Category
    {
        return Category::where('nama', $nama)->firstOrFail();
    }

    /** @param array<int, array{tipe: string, poin?: int, opsi?: array, pencocokan?: string, pembahasan?: string}> $soal */
    private function buatKuis(array $soal, array $attr = []): Quiz
    {
        $quiz = Quiz::forceCreate($attr + [
            'user_id' => $this->guru->id,
            'category_id' => $this->kategori('Matematika')->id,
            'judul' => 'Kuis Uji Pengerjaan',
            'durasi_menit' => 0,
            'tampilkan_feedback' => false,
            'navigasi_bebas' => true,
            'status' => 'terbit',
        ]);
        foreach ($soal as $i => $s) {
            $q = Question::forceCreate([
                'quiz_id' => $quiz->id, 'tipe' => $s['tipe'], 'teks' => 'Pertanyaan uji ' . ($i + 1),
                'poin' => $s['poin'] ?? 10, 'pembahasan' => $s['pembahasan'] ?? null,
                'pencocokan' => $s['pencocokan'] ?? 'longgar', 'urutan' => $i + 1,
            ]);
            foreach ($s['opsi'] ?? [] as $j => [$teks, $benar]) {
                AnswerOption::forceCreate(['question_id' => $q->id, 'teks' => $teks, 'benar' => $benar, 'urutan' => $j + 1]);
            }
        }
        return $quiz;
    }

    /** @return Question[] */
    private function soal(Quiz $quiz): array
    {
        return Question::where('quiz_id', $quiz->id)->orderBy('urutan')->with('answerOptions')->get()->all();
    }

    private function opsi(Question $q, string $teks): int
    {
        return (int) $q->answerOptions->firstWhere('teks', $teks)->id;
    }

    private function mulai(Quiz $quiz, ?User $user = null): Attempt
    {
        return $this->service()->start($quiz, ($user ?? $this->peserta)->id);
    }

    private function kirim(Attempt $a, array $data)
    {
        return $this->actingAs($this->peserta)->postJson("/kerjakan/{$a->id}", $data);
    }

    private function jawaban(Attempt $a, Question $q): ?UserAnswer
    {
        return UserAnswer::where('attempt_id', $a->id)->where('question_id', $q->id)->first();
    }

    private function selesai(Attempt $a): Attempt
    {
        $this->kirim($a, ['aksi' => 'selesai'])->assertOk()
            ->assertExactJson(['ok' => true, 'redirect' => route('hasil', [$a->id, 'baru' => 1])]);
        return $a->fresh();
    }

    public function test_pg_bs_dan_pgk_dinilai_dengan_kunci_persis(): void
    {
        $quiz = $this->buatKuis([
            ['tipe' => 'pg', 'opsi' => [['A', 0], ['B', 1], ['C', 0]]],
            ['tipe' => 'bs', 'opsi' => [['Benar', 1], ['Salah', 0]]],
            ['tipe' => 'pgk', 'opsi' => [['1', 1], ['2', 0], ['3', 1]]],
            ['tipe' => 'pgk', 'opsi' => [['x', 1], ['y', 0], ['z', 1]]],
            ['tipe' => 'pgk', 'opsi' => [['p', 1], ['q', 0], ['r', 1]]],
            ['tipe' => 'pg', 'opsi' => [['m', 1], ['n', 0]]],
        ]);
        [$pg, $bs, $pgkTepat, $pgkSebagian, $pgkLebih, $kosong] = $this->soal($quiz);
        $a = $this->mulai($quiz);

        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pg->id, 'nilai' => (string) $this->opsi($pg, 'B')])->assertOk()->assertExactJson(['ok' => true]);
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $bs->id, 'nilai' => [$this->opsi($bs, 'Salah')]])->assertOk();
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pgkTepat->id, 'nilai' => [$this->opsi($pgkTepat, '3'), $this->opsi($pgkTepat, '1')]])->assertOk();
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pgkSebagian->id, 'nilai' => [$this->opsi($pgkSebagian, 'x')]])->assertOk();
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pgkLebih->id, 'nilai' => [$this->opsi($pgkLebih, 'p'), $this->opsi($pgkLebih, 'q'), $this->opsi($pgkLebih, 'r')]])->assertOk();

        // option ids are stored as answer_options ids
        $this->assertDatabaseHas('user_answer_options', ['user_answer_id' => $this->jawaban($a, $pg)->id, 'option_id' => $this->opsi($pg, 'B')]);

        $a = $this->selesai($a);
        $this->assertSame('selesai', $a->status);
        $this->assertSame(2, $a->jumlah_benar);
        $this->assertSame(4, $a->jumlah_salah);
        $this->assertSame(20.0, (float) $a->poin_didapat);
        $this->assertSame(60.0, (float) $a->poin_maks);
        $this->assertSame(33.33, (float) $a->skor);

        foreach ([[$pg, true, 10.0], [$bs, false, 0.0], [$pgkTepat, true, 10.0], [$pgkSebagian, false, 0.0], [$pgkLebih, false, 0.0], [$kosong, false, 0.0]] as [$q, $benar, $poin]) {
            $j = $this->jawaban($a, $q);
            $this->assertSame($benar, $j->benar, "soal {$q->urutan}");
            $this->assertSame($poin, (float) $j->poin, "soal {$q->urutan}");
            $this->assertTrue($j->dikunci);
        }
        $this->kirim($a, ['aksi' => 'selesai'])->assertStatus(409)
            ->assertExactJson(['ok' => false, 'pesan' => 'Percobaan sudah selesai.', 'redirect' => route('hasil', $a->id)]);
    }

    public function test_isian_longgar_dan_ketat(): void
    {
        $svc = $this->service();
        $this->assertSame('ibu kota', $svc->normalisasi("  Ibu \n  Kota.!! ", 'longgar'));
        $this->assertSame('Ibu Kota.!!', $svc->normalisasi("  Ibu \n  Kota.!! ", 'ketat'));

        $quiz = $this->buatKuis([
            ['tipe' => 'isian', 'opsi' => [['Jakarta', 1], ['DKI Jakarta', 1]]],
            ['tipe' => 'isian', 'pencocokan' => 'ketat', 'opsi' => [['H2O', 1]]],
            ['tipe' => 'isian', 'pencocokan' => 'ketat', 'opsi' => [['H2O', 1]]],
            ['tipe' => 'isian', 'pencocokan' => 'ketat', 'opsi' => [['Fe', 1]]],
            ['tipe' => 'isian', 'opsi' => [['nol', 1]]],
        ]);
        [$longgar, $ketatSalah, $ketatBenar, $ketatTitik, $kosong] = $this->soal($quiz);
        $a = $this->mulai($quiz);
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $longgar->id, 'nilai' => '  dki   JAKARTA!. '])->assertOk();
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $ketatSalah->id, 'nilai' => 'h2o'])->assertOk();
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $ketatBenar->id, 'nilai' => '   H2O '])->assertOk();
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $ketatTitik->id, 'nilai' => 'Fe.'])->assertOk();
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $kosong->id, 'nilai' => '   '])->assertOk();
        $this->assertNull($this->jawaban($a, $kosong)->jawaban_teks);
        $this->assertSame('H2O', $this->jawaban($a, $ketatBenar)->jawaban_teks);

        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $longgar->id, 'nilai' => str_repeat('a', 201)])
            ->assertStatus(422)->assertExactJson(['ok' => false, 'pesan' => 'Jawaban maksimal 200 karakter.']);

        $a = $this->selesai($a);
        $this->assertSame([true, false, true, false, false], array_map(fn ($q) => $this->jawaban($a, $q)->benar, [$longgar, $ketatSalah, $ketatBenar, $ketatTitik, $kosong]));
        $this->assertSame(40.0, (float) $a->skor);
    }

    public function test_validasi_simpan_jawaban(): void
    {
        $quiz = $this->buatKuis([
            ['tipe' => 'pg', 'opsi' => [['A', 1], ['B', 0]]],
            ['tipe' => 'esai'],
        ]);
        $lain = $this->buatKuis([['tipe' => 'pg', 'opsi' => [['Z', 1]]]]);
        [$pg, $esai] = $this->soal($quiz);
        [$pgLain] = $this->soal($lain);
        $a = $this->mulai($quiz);

        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pg->id, 'nilai' => [$this->opsi($pgLain, 'Z')]])
            ->assertStatus(422)->assertExactJson(['ok' => false, 'pesan' => 'Opsi jawaban tidak valid.']);
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pg->id, 'nilai' => [$this->opsi($pg, 'A'), $this->opsi($pg, 'B')]])
            ->assertStatus(422)->assertExactJson(['ok' => false, 'pesan' => 'Soal ini hanya menerima satu jawaban.']);
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pgLain->id, 'nilai' => []])
            ->assertNotFound()->assertExactJson(['ok' => false, 'pesan' => 'Soal tidak ditemukan.']);
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $esai->id, 'nilai' => str_repeat('a', 5001)])
            ->assertStatus(422)->assertExactJson(['ok' => false, 'pesan' => 'Jawaban maksimal 5000 karakter.']);
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $esai->id, 'nilai' => str_repeat('a', 5000)])->assertOk();

        // a locked answer (kunci=1) can no longer change
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pg->id, 'nilai' => [$this->opsi($pg, 'B')], 'kunci' => '1'])->assertOk();
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pg->id, 'nilai' => [$this->opsi($pg, 'A')]])
            ->assertStatus(422)->assertExactJson(['ok' => false, 'pesan' => 'Jawaban soal ini sudah dikunci.']);

        $orangLain = User::where('email', 'usergantengcantik@gmail.com')->firstOrFail();
        $this->actingAs($orangLain)->postJson("/kerjakan/{$a->id}", ['aksi' => 'selesai'])->assertNotFound();
        $this->actingAs($orangLain)->get("/hasil/{$a->id}")->assertNotFound();
    }

    public function test_waktu_habis_dikumpulkan_otomatis_dengan_batas_sebagai_waktu_selesai(): void
    {
        Carbon::setTestNow('2026-03-02 08:00:00');
        $quiz = $this->buatKuis([['tipe' => 'pg', 'opsi' => [['A', 1], ['B', 0]]]], ['durasi_menit' => 1]);
        [$pg] = $this->soal($quiz);
        $a = $this->mulai($quiz);
        $this->assertSame('2026-03-02 08:00:00', $a->mulai_pada);

        $this->travelTo(Carbon::parse('2026-03-02 08:00:20'));
        $this->actingAs($this->peserta)->get("/kerjakan/{$a->id}")->assertOk()->assertViewHas('config', fn ($c) => $c['sisaDetik'] === 40);
        $this->assertSame($a->id, $this->mulai($quiz)->id, 'running attempt is resumed');

        // within the 15 s grace period answers are still accepted
        $this->travelTo(Carbon::parse('2026-03-02 08:01:14'));
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pg->id, 'nilai' => [$this->opsi($pg, 'A')]])->assertOk();
        $this->actingAs($this->peserta)->get("/kerjakan/{$a->id}")->assertOk()->assertViewHas('config', fn ($c) => $c['sisaDetik'] === 0);

        $this->travelTo(Carbon::parse('2026-03-02 08:01:16'));
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pg->id, 'nilai' => [$this->opsi($pg, 'B')]])
            ->assertStatus(409)
            ->assertExactJson(['ok' => false, 'pesan' => 'Waktu habis. Jawaban dikumpulkan otomatis.', 'redirect' => route('hasil', $a->id)]);

        $a->refresh();
        $this->assertSame('selesai', $a->status);
        $this->assertSame('2026-03-02 08:01:00', $a->selesai_pada);
        $this->assertSame(60, $a->durasi_detik);
        $this->assertSame(100.0, (float) $a->skor);

        // GET after expiry finishes and redirects; start() finishes the expired one and creates a new attempt
        $b = $this->mulai($quiz);
        $this->assertNotSame($a->id, $b->id);
        $this->travelTo(Carbon::parse('2026-03-02 08:05:00'));
        $this->actingAs($this->peserta)->get("/kerjakan/{$b->id}")->assertRedirect(route('hasil', $b->id));
        $this->assertSame('selesai', $b->fresh()->status);
        $this->assertSame('2026-03-02 08:02:16', $b->fresh()->selesai_pada);
        $c = $this->mulai($quiz);
        $this->travelTo(Carbon::parse('2026-03-02 08:10:00'));
        $d = $this->mulai($quiz);
        $this->assertNotSame($c->id, $d->id);
        $this->assertSame('selesai', $c->fresh()->status);
        $this->assertSame(60, $c->fresh()->durasi_detik);

        // finishing early records the real time
        $this->travelTo(Carbon::parse('2026-03-02 08:10:42'));
        $this->assertSame(42, $this->selesai($d)->durasi_detik);
        Carbon::setTestNow();
    }

    public function test_navigasi_berurutan_mengunci_soal_sebelumnya(): void
    {
        $opsi = [['A', 1], ['B', 0]];
        $quiz = $this->buatKuis([['tipe' => 'pg', 'opsi' => $opsi], ['tipe' => 'pg', 'opsi' => $opsi], ['tipe' => 'pg', 'opsi' => $opsi], ['tipe' => 'pg', 'opsi' => $opsi]], ['navigasi_bebas' => false]);
        [$s1, $s2, $s3, $s4] = $this->soal($quiz);
        $a = $this->mulai($quiz);

        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $s1->id, 'nilai' => [$this->opsi($s1, 'A')]])->assertOk();
        $this->assertFalse($this->jawaban($a, $s1)->dikunci);

        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $s3->id, 'nilai' => [$this->opsi($s3, 'A')]])->assertOk();
        $this->assertTrue($this->jawaban($a, $s1)->dikunci);
        $lewat = $this->jawaban($a, $s2);
        $this->assertTrue($lewat->dikunci);
        $this->assertNull($lewat->jawaban_teks);
        $this->assertSame(0, $lewat->selectedOptions()->count());
        $this->assertFalse($this->jawaban($a, $s3)->dikunci);
        $this->assertNull($this->jawaban($a, $s4));

        foreach ([$s1, $s2] as $s) {
            $this->kirim($a, ['aksi' => 'simpan', 'soal' => $s->id, 'nilai' => [$this->opsi($s, 'B')]])
                ->assertStatus(422)->assertExactJson(['ok' => false, 'pesan' => 'Jawaban soal ini sudah dikunci.']);
        }
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $s3->id, 'nilai' => [$this->opsi($s3, 'B')]])->assertOk();

        $this->actingAs($this->peserta)->get("/kerjakan/{$a->id}")->assertOk()->assertViewHas('config', function ($c) use ($s1, $s2, $s3) {
            $j = (array) $c['jawaban'];
            return $c['bebas'] === false
                && $j[$s1->id] === ['opsi' => [$this->opsi($s1, 'A')], 'teks' => null, 'dikunci' => true]
                && $j[$s2->id] === ['opsi' => [], 'teks' => null, 'dikunci' => true]
                && $j[$s3->id] === ['opsi' => [$this->opsi($s3, 'B')], 'teks' => null, 'dikunci' => false];
        });

        $a = $this->selesai($a);
        $this->assertSame(1, $a->jumlah_benar);
        $this->assertSame(25.0, (float) $a->skor);

        $bebas = $this->buatKuis([['tipe' => 'pg', 'opsi' => $opsi], ['tipe' => 'pg', 'opsi' => $opsi]]);
        [$b1, $b2] = $this->soal($bebas);
        $b = $this->mulai($bebas);
        $this->kirim($b, ['aksi' => 'simpan', 'soal' => $b2->id, 'nilai' => [$this->opsi($b2, 'A')]])->assertOk();
        $this->assertNull($this->jawaban($b, $b1));
    }

    public function test_cek_jawaban_mengunci_dan_mengembalikan_umpan_balik(): void
    {
        $quiz = $this->buatKuis([
            ['tipe' => 'pgk', 'pembahasan' => 'Bilangan prima: 2 dan 3.', 'opsi' => [['2', 1], ['3', 1], ['4', 0]]],
            ['tipe' => 'isian', 'opsi' => [['Jakarta', 1], ['DKI Jakarta', 1]]],
            ['tipe' => 'esai'],
            ['tipe' => 'bs', 'opsi' => [['Benar', 0], ['Salah', 1]]],
        ], ['tampilkan_feedback' => true]);
        [$pgk, $isian, $esai, $bs] = $this->soal($quiz);
        $a = $this->mulai($quiz);

        $res = $this->kirim($a, ['aksi' => 'cek', 'soal' => $pgk->id, 'nilai' => [$this->opsi($pgk, '2')]])->assertOk();
        $umpanPgk = ['ok' => true, 'benar' => false, 'kunci' => [$this->opsi($pgk, '2'), $this->opsi($pgk, '3')], 'jawaban_diterima' => [], 'pembahasan' => 'Bilangan prima: 2 dan 3.'];
        $this->assertSame($umpanPgk, $res->json());
        $this->assertTrue($this->jawaban($a, $pgk)->dikunci);
        $this->kirim($a, ['aksi' => 'cek', 'soal' => $pgk->id, 'nilai' => [$this->opsi($pgk, '2'), $this->opsi($pgk, '3')]])
            ->assertStatus(422)->assertExactJson(['ok' => false, 'pesan' => 'Jawaban soal ini sudah dikunci.']);

        $res = $this->kirim($a, ['aksi' => 'cek', 'soal' => $isian->id, 'nilai' => 'jakarta.'])->assertOk();
        $this->assertSame(['ok' => true, 'benar' => true, 'kunci' => [$this->opsi($isian, 'Jakarta'), $this->opsi($isian, 'DKI Jakarta')], 'jawaban_diterima' => ['Jakarta', 'DKI Jakarta'], 'pembahasan' => null], $res->json());

        $this->kirim($a, ['aksi' => 'cek', 'soal' => $esai->id, 'nilai' => 'teks'])
            ->assertStatus(422)->assertExactJson(['ok' => false, 'pesan' => 'Soal esai dinilai oleh pengajar.']);

        $this->actingAs($this->peserta)->get("/kerjakan/{$a->id}")->assertOk()->assertViewHas('config', function ($c) use ($pgk, $isian, $bs, $umpanPgk) {
            $umpan = (array) $c['umpan'];
            $soalBs = collect($c['soal'])->firstWhere('id', $bs->id);
            return $c['feedback'] === true
                && array_keys($umpan) === [$pgk->id, $isian->id]
                && ['ok' => true] + $umpan[$pgk->id] === $umpanPgk
                && $umpan[$isian->id]['benar'] === true
                && $soalBs === ['id' => $bs->id, 'tipe' => 'bs', 'teks' => $bs->teks, 'poin' => 10,
                    'opsi' => [['id' => $this->opsi($bs, 'Benar'), 'teks' => 'Benar'], ['id' => $this->opsi($bs, 'Salah'), 'teks' => 'Salah']]];
        });

        $tanpa = $this->buatKuis([['tipe' => 'pg', 'opsi' => [['A', 1]]]]);
        [$pg] = $this->soal($tanpa);
        $b = $this->mulai($tanpa);
        $this->kirim($b, ['aksi' => 'cek', 'soal' => $pg->id, 'nilai' => [$this->opsi($pg, 'A')]])
            ->assertStatus(422)->assertExactJson(['ok' => false, 'pesan' => 'Kuis ini tidak menampilkan umpan balik langsung.']);
        $this->assertNull($this->jawaban($b, $pg));
        $this->actingAs($this->peserta)->get("/kerjakan/{$b->id}")->assertViewHas('config', fn ($c) => (array) $c['umpan'] === [] && $c['sisaDetik'] === null);
    }

    public function test_esai_menunggu_lalu_dinilai_pengajar(): void
    {
        $quiz = $this->buatKuis([
            ['tipe' => 'pg', 'opsi' => [['A', 1], ['B', 0]]],
            ['tipe' => 'esai', 'poin' => 20],
            ['tipe' => 'esai'],
        ]);
        [$pg, $esai, $esaiKosong] = $this->soal($quiz);
        $a = $this->mulai($quiz);
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pg->id, 'nilai' => [$this->opsi($pg, 'A')]])->assertOk();
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $esai->id, 'nilai' => 'Fotosintesis adalah ...'])->assertOk();

        $a = $this->selesai($a);
        $this->assertSame('menunggu', $a->status);
        $this->assertNull($a->skor);
        $this->assertSame(1, $a->jumlah_benar);
        $this->assertSame(1, $a->jumlah_salah);
        $this->assertSame(10.0, (float) $a->poin_didapat);
        $this->assertSame(40.0, (float) $a->poin_maks);
        $this->assertNull($this->jawaban($a, $esai)->poin);
        $this->assertNull($this->jawaban($a, $esai)->benar);
        $this->assertSame(0.0, (float) $this->jawaban($a, $esaiKosong)->poin);
        $this->assertFalse($this->jawaban($a, $esaiKosong)->benar);
        $this->assertDatabaseCount('user_badges', 0);
        $this->actingAs($this->peserta)->get("/hasil/{$a->id}")->assertOk()->assertSee('Menunggu penilaian');

        $ans = $this->jawaban($a, $esai);
        $this->actingAs($this->guru)->get('/pengajar/penilaian')->assertOk()->assertSee(route('pengajar.nilai', $a->id));
        $this->get("/pengajar/kuis/{$quiz->id}/hasil")->assertOk()->assertSee($this->peserta->nama);
        $this->get("/pengajar/nilai/{$a->id}")->assertOk()->assertSee('Simpan penilaian');

        $this->post("/pengajar/nilai/{$a->id}", ['poin' => [$ans->id => 25]])
            ->assertSessionHasErrors((string) $ans->id);
        $this->assertSame('Poin harus antara 0 dan 20.', session('errors')->first((string) $ans->id));
        $this->post("/pengajar/nilai/{$a->id}", ['poin' => [$ans->id => 5], 'catatan' => [$ans->id => str_repeat('c', 501)]])
            ->assertSessionHasErrors((string) $ans->id);
        $this->assertSame('Catatan maksimal 500 karakter.', session('errors')->first((string) $ans->id));
        $this->assertSame('menunggu', $a->fresh()->status);

        $this->post("/pengajar/nilai/{$a->id}", ['poin' => [$ans->id => '9.5'], 'catatan' => [$ans->id => ' Kurang lengkap ']])
            ->assertRedirect(route('pengajar.nilai', $a->id));
        $this->assertContains(['tipe' => 'sukses', 'pesan' => 'Penilaian disimpan. Nilai akhir ' . $this->peserta->nama . ': 49.'], session('toasts'));
        $a->refresh();
        $ans->refresh();
        $this->assertSame('selesai', $a->status);
        $this->assertSame(48.75, (float) $a->skor);
        $this->assertSame(1, $a->jumlah_benar);
        $this->assertSame(2, $a->jumlah_salah);
        $this->assertFalse($ans->benar, 'below half of max points');
        $this->assertSame('Kurang lengkap', $ans->catatan);
        $this->assertSame($this->guru->id, (int) $ans->dinilai_oleh);
        $this->assertDatabaseHas('user_badges', ['user_id' => $this->peserta->id, 'attempt_id' => $a->id]);

        $this->post("/pengajar/nilai/{$a->id}", ['poin' => [$ans->id => 10]]);
        $a->refresh();
        $this->assertTrue($ans->fresh()->benar, 'half of max points counts as correct');
        $this->assertSame(2, $a->jumlah_benar);
        $this->assertSame(50.0, (float) $a->skor);
        $this->assertNull($ans->fresh()->catatan);
    }

    public function test_menilai_dua_esai_sekaligus_menghitung_skor_akhir_sekali(): void
    {
        $quiz = $this->buatKuis([
            ['tipe' => 'pg', 'opsi' => [['A', 1], ['B', 0]]],
            ['tipe' => 'esai', 'poin' => 20],
            ['tipe' => 'esai'],
            ['tipe' => 'pg', 'opsi' => [['A', 1], ['B', 0]]],
        ]);
        [$pg, $esai1, $esai2] = $this->soal($quiz);
        $a = $this->mulai($quiz);
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pg->id, 'nilai' => [$this->opsi($pg, 'A')]])->assertOk();
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $esai1->id, 'nilai' => 'Esai pertama'])->assertOk();
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $esai2->id, 'nilai' => 'Esai kedua'])->assertOk();
        $a = $this->selesai($a);
        $this->assertSame('menunggu', $a->status);
        $this->assertSame(4, UserAnswer::where('attempt_id', $a->id)->count(), 'finish records every question');

        $j1 = $this->jawaban($a, $esai1);
        $j2 = $this->jawaban($a, $esai2);
        $this->actingAs($this->guru)->post("/pengajar/nilai/{$a->id}", ['poin' => [$j1->id => 15, $j2->id => 4], 'catatan' => [$j2->id => 'Kurang']])
            ->assertRedirect(route('pengajar.nilai', $a->id));
        $a->refresh();
        $this->assertSame('selesai', $a->status);
        $this->assertSame(50.0, (float) $a->poin_maks);
        $this->assertSame(29.0, (float) $a->poin_didapat);
        $this->assertSame(58.0, (float) $a->skor);
        $this->assertSame(2, $a->jumlah_benar);
        $this->assertSame(2, $a->jumlah_salah);
        $this->assertTrue($j1->fresh()->benar);
        $this->assertFalse($j2->fresh()->benar);
        $this->assertSame('Kurang', $j2->fresh()->catatan);
        $this->assertSame(['first-step'], app(BadgeService::class)->earnedOn($a)->pluck('kode')->all());

        $this->actingAs($this->peserta)->get("/hasil/{$a->id}")->assertOk()
            ->assertViewHas('detail', fn ($d) => count($d) === 4)
            ->assertSee('Tidak dijawab');
    }

    public function test_draft_menyembunyikan_konten_dari_percobaan_lama(): void
    {
        $quiz = $this->buatKuis([['tipe' => 'pg', 'opsi' => [['A', 1], ['B', 0]]]], ['tampilkan_feedback' => true]);
        [$pg] = $this->soal($quiz);
        $a1 = $this->mulai($quiz);
        $this->kirim($a1, ['aksi' => 'simpan', 'soal' => $pg->id, 'nilai' => [$this->opsi($pg, 'A')]])->assertOk();
        $a1 = $this->selesai($a1);
        $a2 = $this->mulai($quiz);
        $this->actingAs($this->peserta)->get("/kerjakan/{$a2->id}")->assertOk();

        $this->actingAs($this->guru)->post('/pengajar/kuis', ['aksi' => 'draft', 'id' => $quiz->id])->assertRedirect(route('pengajar.kuis'));
        $this->assertSame('draft', $quiz->fresh()->status);
        $rahasia = Question::forceCreate([
            'quiz_id' => $quiz->id, 'tipe' => 'pg', 'teks' => 'SOAL-RAHASIA-X', 'poin' => 10,
            'pembahasan' => 'BAHAS-RAHASIA-X', 'pencocokan' => 'longgar', 'urutan' => 2,
        ]);
        $kunci = AnswerOption::forceCreate(['question_id' => $rahasia->id, 'teks' => 'KUNCI-RAHASIA-X', 'benar' => 1, 'urutan' => 1]);
        AnswerOption::forceCreate(['question_id' => $rahasia->id, 'teks' => 'SALAH-RAHASIA-X', 'benar' => 0, 'urutan' => 2]);

        $this->actingAs($this->peserta)->get("/kuis/{$quiz->id}")->assertNotFound();
        $this->get("/hasil/{$a1->id}")->assertOk()
            ->assertSee('Pertanyaan uji 1')
            ->assertDontSee('SOAL-RAHASIA-X')->assertDontSee('KUNCI-RAHASIA-X')->assertDontSee('BAHAS-RAHASIA-X')
            ->assertViewHas('detail', fn ($d) => count($d) === 1);

        $this->get("/kerjakan/{$a2->id}")->assertNotFound()->assertDontSee('SOAL-RAHASIA-X');
        $this->kirim($a2, ['aksi' => 'cek', 'soal' => $rahasia->id, 'nilai' => [$kunci->id]])
            ->assertNotFound()->assertDontSee('KUNCI-RAHASIA-X')->assertDontSee('BAHAS-RAHASIA-X');
        $this->kirim($a2, ['aksi' => 'selesai'])->assertNotFound();
        $this->assertNull($this->jawaban($a2, $rahasia));
        $this->assertSame('berlangsung', $a2->fresh()->status);
        $this->assertSame(1, $a1->fresh()->jumlah_benar, 'finished history is unchanged');
    }

    public function test_badge_diberikan_dan_tetap_setelah_reset(): void
    {
        $badges = app(BadgeService::class);
        $quiz = $this->buatKuis([['tipe' => 'pg', 'opsi' => [['A', 1], ['B', 0]]]]);
        [$pg] = $this->soal($quiz);
        $kerjakan = function (string $pilih) use ($quiz, $pg) {
            $a = $this->mulai($quiz);
            $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pg->id, 'nilai' => [$this->opsi($pg, $pilih)]])->assertOk();
            return $this->selesai($a);
        };

        $pertama = $kerjakan('A');
        $kode = fn () => \App\Models\Badge::whereRelation('users', 'users.id', $this->peserta->id)->orderBy('id')->pluck('kode')->all();
        $this->assertSame(['first-step', 'perfect-score'], $kode());
        $this->assertDatabaseHas('user_badges', ['user_id' => $this->peserta->id, 'attempt_id' => $pertama->id]);
        $this->actingAs($this->peserta)->get("/hasil/{$pertama->id}?baru=1")->assertOk()->assertSee('Badge baru: First Step')->assertSee('Badge baru: Perfect Score');
        $this->assertSame(2, $badges->countFor($this->peserta->id));

        $kerjakan('B');
        $kerjakan('B');
        $this->assertSame(['first-step', 'perfect-score', 'math-explorer'], $kode(), '3 Matematika attempts');
        $berjalan = $this->mulai($quiz);

        $this->actingAs($this->peserta)->post('/riwayat', ['aksi' => 'reset', 'konfirmasi_teks' => 'reset'])->assertRedirect(route('riwayat'));
        $this->assertSame(0, Attempt::where('diarsipkan', true)->count());

        Carbon::setTestNow('2026-05-05 09:30:00');
        $this->post('/riwayat', ['aksi' => 'reset', 'konfirmasi_teks' => 'RESET'])->assertRedirect(route('riwayat'));
        Carbon::setTestNow();
        $this->assertContains(['tipe' => 'sukses', 'pesan' => '3 riwayat diarsipkan. Statistik pribadimu dimulai dari nol; badge tetap tersimpan.'], session('toasts'));
        $this->assertSame(3, Attempt::where('diarsipkan', true)->where('diarsipkan_pada', '2026-05-05 09:30:00')->count());
        $this->assertFalse($berjalan->fresh()->diarsipkan, 'running attempt is not archived');
        $this->assertSame(0, $this->service()->historyCount($this->peserta->id, []));
        $this->assertSame(['first-step', 'perfect-score', 'math-explorer'], $kode(), 'badges are permanent');

        $koleksi = $badges->collection($this->peserta->id)->keyBy('kode');
        $this->assertSame(7, $koleksi->count());
        $this->assertNotNull($koleksi['first-step']['diperoleh_pada']);
        $this->assertNull($koleksi['consistent-learner']['diperoleh_pada']);
        $this->assertSame(3, $koleksi['consistent-learner']['kemajuan'], 'archived attempts still count');
        $this->assertSame(1, $koleksi['first-step']['kemajuan'], 'progress is capped at the requirement');
        $this->assertSame(1, $koleksi['knowledge-seeker']['kemajuan']);
        $this->assertSame('Matematika', $badges->all()->firstWhere('kode', 'math-explorer')['kategori']);
        $this->assertNull($badges->all()->firstWhere('kode', 'first-step')['kategori']);
        $this->get('/pencapaian')->assertOk()->assertSee('Kamu memiliki <b>3</b> dari 7 badge', false);

        $this->post('/riwayat', ['aksi' => 'reset', 'konfirmasi_teks' => 'RESET']);
        $this->assertContains(['tipe' => 'sukses', 'pesan' => 'Tidak ada riwayat untuk direset.'], session('toasts'));

        // berjalan is finished here (2 more needed for 5 completed in total incl. archived)
        $this->kirim($berjalan, ['aksi' => 'simpan', 'soal' => $pg->id, 'nilai' => [$this->opsi($pg, 'B')]]);
        $this->selesai($berjalan);
        $this->assertNotContains('consistent-learner', $kode());
        $kelima = $kerjakan('B');
        $this->assertContains('consistent-learner', $kode());
        $this->assertSame(['consistent-learner'], $badges->earnedOn($kelima)->pluck('kode')->all());
        $this->assertContains('math-explorer', $kode());
    }

    public function test_filter_riwayat(): void
    {
        $mtk = $this->buatKuis([], ['judul' => 'Kuis MTK']);
        $inf = $this->buatKuis([], ['judul' => 'Kuis INF', 'category_id' => $this->kategori('Informatika')->id]);
        $buat = fn (Quiz $q, string $status, ?float $skor, string $selesai, array $extra = []) => Attempt::forceCreate($extra + [
            'quiz_id' => $q->id, 'user_id' => $this->peserta->id, 'status' => $status, 'skor' => $skor,
            'mulai_pada' => $selesai, 'selesai_pada' => $status === 'berlangsung' ? null : $selesai,
        ])->id;

        $tinggi = $buat($mtk, 'selesai', 80, '2026-01-10 10:00:00');
        $sedang = $buat($inf, 'selesai', 79.99, '2026-01-11 10:00:00');
        $sedang60 = $buat($mtk, 'selesai', 60, '2026-01-12 23:59:59');
        $rendah = $buat($inf, 'selesai', 59.99, '2026-01-13 00:00:00');
        $menunggu = $buat($mtk, 'menunggu', null, '2026-01-14 10:00:00');
        $buat($mtk, 'berlangsung', null, '2026-01-15 10:00:00');
        $buat($mtk, 'selesai', 100, '2026-01-16 10:00:00', ['diarsipkan' => true]);
        Attempt::forceCreate(['quiz_id' => $mtk->id, 'user_id' => $this->guru->id, 'status' => 'selesai', 'skor' => 90, 'mulai_pada' => '2026-01-10 10:00:00', 'selesai_pada' => '2026-01-10 10:00:00']);

        $ids = fn (array $q) => $this->actingAs($this->peserta)->get('/riwayat?' . http_build_query($q))
            ->assertOk()->viewData('rows')->pluck('id')->all();

        $this->assertSame([$menunggu, $rendah, $sedang60, $sedang, $tinggi], $ids([]));
        $this->assertSame([$tinggi], $ids(['nilai' => 'tinggi']));
        $this->assertSame([$sedang60, $sedang], $ids(['nilai' => 'sedang']));
        $this->assertSame([$rendah], $ids(['nilai' => 'rendah']));
        $this->assertSame([$menunggu], $ids(['nilai' => 'menunggu']));
        $this->assertSame([$menunggu, $rendah, $sedang60, $sedang, $tinggi], $ids(['nilai' => 'asal']));
        $this->assertSame([$rendah, $sedang], $ids(['kategori' => 'informatika']));
        $this->assertSame([$sedang60, $sedang], $ids(['dari' => '2026-01-11', 'sampai' => '2026-01-12']));
        $this->assertSame([$menunggu, $rendah, $sedang60, $sedang, $tinggi], $ids(['dari' => '11-01-2026']));
        $this->assertSame([$rendah], $ids(['kategori' => 'informatika', 'nilai' => 'rendah', 'dari' => '2026-01-12']));

        $this->get('/riwayat')->assertSee('Kuis INF')->assertSee('Informatika')->assertSee('Kuis MTK');
        $this->get('/riwayat?nilai=tinggi&kategori=informatika')->assertOk()->assertSee('Tidak ada yang cocok');
        $this->assertSame(5, $this->service()->historyCount($this->peserta->id, []));
        $this->assertSame(2, $this->service()->history($this->peserta->id, [], 2, 3)->count());
    }

    public function test_halaman_hasil_pengajar_menampilkan_menunggu_di_atas(): void
    {
        $quiz = $this->buatKuis([
            ['tipe' => 'pg', 'opsi' => [['A', 1], ['B', 0]]],
            ['tipe' => 'esai'],
        ]);
        [$pg, $esai] = $this->soal($quiz);
        $lain = User::where('email', 'usergantengcantik@gmail.com')->firstOrFail();

        $a = $this->mulai($quiz);
        $this->kirim($a, ['aksi' => 'simpan', 'soal' => $pg->id, 'nilai' => [$this->opsi($pg, 'A')]]);
        $a = $this->selesai($a);
        $b = $this->service()->start($quiz, $lain->id);
        $this->actingAs($lain)->postJson("/kerjakan/{$b->id}", ['aksi' => 'simpan', 'soal' => $esai->id, 'nilai' => 'jawab']);
        $this->actingAs($lain)->postJson("/kerjakan/{$b->id}", ['aksi' => 'selesai']);
        $this->service()->start($quiz, $this->peserta->id);

        $rows = $this->service()->forQuizOwner($quiz->id);
        $this->assertSame([$b->id, $a->id], $rows->pluck('id')->all());
        $this->assertSame([$b->id], $this->service()->forQuizOwner($quiz->id, 'menunggu')->pluck('id')->all());
        $this->assertSame([$a->id], $this->service()->forQuizOwner($quiz->id, 'selesai')->pluck('id')->all());

        $stats = $this->service()->questionStats($quiz);
        $this->assertSame([[2, 1], [2, 0]], $stats->map(fn ($s) => [$s['dijawab'], $s['benar']])->all());

        $this->actingAs($this->guru)->get("/pengajar/kuis/{$quiz->id}/hasil")->assertOk()
            ->assertSeeInOrder([$lain->nama, $this->peserta->nama])->assertSee('Nilai esai');
        $this->get("/pengajar/kuis/{$quiz->id}/hasil?status=selesai")->assertOk()->assertDontSee($lain->email);
        $guruLain = User::where('email', 'dimas@ziqo.id')->firstOrFail();
        $this->actingAs($guruLain)->get("/pengajar/nilai/{$a->id}")->assertForbidden();
        $berjalan = $this->service()->ongoing($quiz->id, $this->peserta->id);
        $this->actingAs($this->guru)->get("/pengajar/nilai/{$berjalan->id}")->assertNotFound();
    }
}
