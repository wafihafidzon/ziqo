<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZiqoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_halaman_publik_bisa_dibuka(): void
    {
        foreach (['/', '/jelajah', '/kuis/1', '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/kuis/9999')->assertNotFound();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_dengan_password_lama_dan_salah(): void
    {
        $this->post('/login', ['email' => 'demo@ziqo.id', 'password' => 'salah'])
            ->assertSessionHas('login_error', 'Email atau password belum sesuai.');
        $this->post('/login', ['email' => 'DEMO@ziqo.id', 'password' => 'demo1234'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($this->user('demo@ziqo.id'));
    }

    public function test_akun_nonaktif_tidak_bisa_masuk(): void
    {
        $this->user('demo@ziqo.id')->update(['status' => 'nonaktif']);
        $this->post('/login', ['email' => 'demo@ziqo.id', 'password' => 'demo1234'])
            ->assertSessionHas('login_error', 'Akun kamu sedang dinonaktifkan. Hubungi administrator.');
        $this->assertGuest();
    }

    public function test_register_selalu_menjadi_peserta(): void
    {
        $this->post('/register', [
            'nama' => 'Peserta Baru', 'email' => 'baru@ziqo.id', 'password' => 'rahasia123',
            'konfirmasi' => 'rahasia123', 'role' => 'admin',
        ])->assertRedirect('/dashboard');
        $this->assertSame('peserta', $this->user('baru@ziqo.id')->role);
    }

    public function test_akses_per_role(): void
    {
        $peserta = $this->user('demo@ziqo.id');
        $this->actingAs($peserta)->get('/pengajar/kuis')->assertForbidden();
        $this->actingAs($peserta)->get('/admin/pengguna')->assertForbidden();

        $guru = $this->user('dimas@ziqo.id');
        $milikGuruLain = \App\Models\Quiz::where('user_id', '!=', $guru->id)->value('id');
        $this->actingAs($guru)->get("/pengajar/kuis/$milikGuruLain/soal")->assertForbidden();
        $this->actingAs($this->user('admin@ziqo.id'))->get("/pengajar/kuis/$milikGuruLain/soal")->assertOk();
    }

    public function test_404_tidak_membocorkan_pesan_internal(): void
    {
        $this->actingAs($this->user('admin@ziqo.id'))->get('/admin/pengguna/9999')
            ->assertNotFound()->assertSee('Halaman atau data yang kamu cari tidak ada.')->assertDontSee('No query results');
    }

    public function test_admin_tidak_bisa_mengubah_akun_admin(): void
    {
        $admin = $this->user('admin@ziqo.id');
        $this->actingAs($admin)->post('/admin/pengguna', ['aksi' => 'status', 'id' => $admin->id, 'status' => 'nonaktif']);
        $this->assertSame('aktif', $admin->fresh()->status);
    }

    public function test_mengerjakan_kuis_dinilai_di_server(): void
    {
        $peserta = $this->user('demo@ziqo.id');
        $this->actingAs($peserta)->post('/kuis/1')->assertRedirect();
        $attempt = Attempt::where('user_id', $peserta->id)->firstOrFail();

        foreach (app(\App\Services\QuestionService::class)->forQuiz(1) as $s) {
            $nilai = match ($s['tipe']) {
                'isian' => $s['opsi'][0]['teks'],
                'esai' => 'jawaban',
                default => Attempt::kunci($s['opsi']),
            };
            $this->postJson("/kerjakan/{$attempt->id}", ['aksi' => 'simpan', 'soal' => $s['id'], 'nilai' => $nilai])
                ->assertOk()->assertJson(['ok' => true]);
        }
        $this->postJson("/kerjakan/{$attempt->id}", ['aksi' => 'selesai'])->assertOk();

        $attempt->refresh();
        $this->assertSame('selesai', $attempt->status);
        $this->assertEquals(100, $attempt->skor);
        $this->assertDatabaseHas('user_badges', ['user_id' => $peserta->id, 'attempt_id' => $attempt->id]);
        $this->get("/hasil/{$attempt->id}")->assertOk();
        $this->postJson("/kerjakan/{$attempt->id}", ['aksi' => 'selesai'])->assertStatus(409);
    }

    public function test_pengajar_membuat_kuis_dan_menilai_esai(): void
    {
        $guru = $this->user('guru@ziqo.id');
        $this->actingAs($guru)->post('/pengajar/kuis/buat', [
            'judul' => 'Kuis Esai Uji', 'category_id' => 1, 'kesulitan' => 'mudah', 'jenjang' => 'umum', 'durasi_menit' => 0,
        ])->assertRedirect();
        $quiz = \App\Models\Quiz::where('judul', 'Kuis Esai Uji')->firstOrFail();
        $this->post("/pengajar/kuis/{$quiz->id}/soal/buat?tipe=esai", ['teks' => 'Jelaskan fotosintesis.', 'poin' => 20])->assertRedirect();
        $this->post('/pengajar/kuis', ['aksi' => 'terbit', 'id' => $quiz->id]);
        $this->assertSame('terbit', $quiz->fresh()->status);

        $peserta = $this->user('demo@ziqo.id');
        $this->actingAs($peserta)->post("/kuis/{$quiz->id}");
        $attempt = Attempt::where('quiz_id', $quiz->id)->firstOrFail();
        $soal = app(\App\Services\QuestionService::class)->forQuiz($quiz->id)[0];
        $this->postJson("/kerjakan/{$attempt->id}", ['aksi' => 'simpan', 'soal' => $soal['id'], 'nilai' => 'Proses tumbuhan membuat makanan.']);
        $this->postJson("/kerjakan/{$attempt->id}", ['aksi' => 'selesai']);
        $this->assertSame('menunggu', $attempt->fresh()->status);

        $jawaban = $attempt->answers()->firstOrFail();
        $this->actingAs($guru)->post("/pengajar/nilai/{$attempt->id}", ['poin' => [$jawaban->id => 25]])
            ->assertSessionHasErrors((string) $jawaban->id);
        $this->post("/pengajar/nilai/{$attempt->id}", ['poin' => [$jawaban->id => 15]])->assertRedirect();
        $this->assertSame('selesai', $attempt->fresh()->status);
        $this->assertEquals(75, $attempt->fresh()->skor);
    }

    public function test_admin_kelola_kategori_dan_topik(): void
    {
        $this->actingAs($this->user('admin@ziqo.id'));
        $this->get('/admin/kategori')->assertOk()->assertSee('Matematika');

        $this->post('/admin/kategori', ['aksi' => 'simpan-kategori', 'nama' => 'matematika', 'warna' => 'ungu'])
            ->assertSessionHasErrors('nama');
        $this->post('/admin/kategori', ['aksi' => 'simpan-kategori', 'nama' => 'Astronomi', 'warna' => 'biru'])
            ->assertSessionHasNoErrors();
        $astro = \App\Models\Category::where('slug', 'astronomi')->firstOrFail();
        $this->assertNotNull($astro->created_at);

        $this->post('/admin/kategori', ['aksi' => 'simpan-topik', 'nama' => 'Tata Surya', 'category_id' => $astro->id]);
        $this->post('/admin/kategori', ['aksi' => 'simpan-topik', 'nama' => 'tata surya', 'category_id' => $astro->id])
            ->assertSessionHasErrors('nama');
        $this->assertSame(1, $astro->topics()->count());

        $quiz = \App\Models\Quiz::whereNotNull('subcategory_id')->firstOrFail();
        $topik = $quiz->subcategory;
        $this->post('/admin/kategori', ['aksi' => 'simpan-topik', 'id' => $topik->id, 'nama' => $topik->nama, 'category_id' => $astro->id]);
        $this->assertNull($quiz->fresh()->subcategory_id);
        $this->assertSame($astro->id, $topik->fresh()->category_id);

        $this->post('/admin/kategori', ['aksi' => 'hapus-kategori', 'id' => $quiz->category_id]);
        $this->assertDatabaseHas('categories', ['id' => $quiz->category_id]);
        $this->post('/admin/kategori', ['aksi' => 'hapus-kategori', 'id' => $astro->id]);
        $this->assertDatabaseMissing('categories', ['id' => $astro->id]);
        $this->assertDatabaseMissing('subcategories', ['category_id' => $astro->id]);
    }

    public function test_reset_riwayat_hanya_mengarsipkan(): void
    {
        $peserta = $this->user('demo@ziqo.id');
        $this->actingAs($peserta)->post('/kuis/1');
        $attempt = Attempt::firstOrFail();
        $this->postJson("/kerjakan/{$attempt->id}", ['aksi' => 'selesai']);

        $this->post('/riwayat', ['aksi' => 'reset', 'konfirmasi_teks' => 'salah']);
        $this->assertSame(0, (int) $attempt->fresh()->diarsipkan);
        $this->post('/riwayat', ['aksi' => 'reset', 'konfirmasi_teks' => 'RESET']);
        $this->assertSame(1, (int) $attempt->fresh()->diarsipkan);
    }
}
