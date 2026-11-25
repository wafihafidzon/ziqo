<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Quiz;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $peserta;
    private User $guru;
    private Quiz $quiz;

    protected function setUp(): void
    {
        parent::setUp();
        $this->peserta = User::where('email', 'demo@ziqo.id')->firstOrFail();
        $this->guru = User::where('email', 'guru@ziqo.id')->firstOrFail();
        $this->quiz = Quiz::where('user_id', $this->guru->id)->where('status', 'terbit')->has('questions')->firstOrFail();
    }

    private function buatRoom(): Room
    {
        $this->actingAs($this->guru)->post(route('pengajar.room', $this->quiz->id), ['aksi' => 'buat'])->assertRedirect();
        return Room::where('quiz_id', $this->quiz->id)->latest('id')->firstOrFail();
    }

    public function test_pengajar_membuat_room_dengan_kode_unik(): void
    {
        $a = $this->buatRoom();
        $b = $this->buatRoom();

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $a->kode_akses);
        $this->assertNotSame($a->kode_akses, $b->kode_akses);
        $this->assertSame('buka', $a->status);
        $this->actingAs($this->guru)->get(route('pengajar.room', [$this->quiz->id, 'room' => $a->id]))
            ->assertOk()->assertSee($a->kode_akses);
    }

    public function test_kuis_draft_tidak_bisa_dibuatkan_room(): void
    {
        $this->quiz->update(['status' => 'draft']);
        $this->actingAs($this->guru)->post(route('pengajar.room', $this->quiz->id), ['aksi' => 'buat']);
        $this->assertSame(0, Room::count());
    }

    public function test_peserta_masuk_dengan_kode_dan_percobaan_tercatat_di_room(): void
    {
        $room = $this->buatRoom();

        $res = $this->actingAs($this->peserta)->post(route('room'), ['kode' => ' ' . strtolower($room->kode_akses) . ' ']);

        $attempt = Attempt::where('user_id', $this->peserta->id)->latest('id')->firstOrFail();
        $res->assertRedirect(route('kerjakan', $attempt->id));
        $this->assertSame($room->id, (int) $attempt->room_id);
        $this->actingAs($this->guru)->get(route('pengajar.room', [$this->quiz->id, 'room' => $room->id]))
            ->assertOk()->assertSee($this->peserta->nama);
    }

    public function test_percobaan_berjalan_dipindah_ke_room_tanpa_membuat_baru(): void
    {
        $this->actingAs($this->peserta)->post('/kuis/' . $this->quiz->id);
        $lama = Attempt::where('user_id', $this->peserta->id)->latest('id')->firstOrFail();
        $this->assertNull($lama->room_id);

        $room = $this->buatRoom();
        $this->actingAs($this->peserta)->post(route('room'), ['kode' => $room->kode_akses])
            ->assertRedirect(route('kerjakan', $lama->id));
        $this->assertSame($room->id, (int) $lama->fresh()->room_id);
    }

    public function test_kode_salah_atau_room_tutup_ditolak(): void
    {
        $room = $this->buatRoom();

        $this->actingAs($this->peserta)->from(route('room'))->post(route('room'), ['kode' => 'ZZZZZZ'])
            ->assertRedirect(route('room'))->assertSessionHasErrors('kode');

        $this->actingAs($this->guru)->post(route('pengajar.room', $this->quiz->id), ['aksi' => 'tutup', 'id' => $room->id]);
        $this->assertSame('tutup', $room->fresh()->status);

        $this->actingAs($this->peserta)->from(route('room'))->post(route('room'), ['kode' => $room->kode_akses])
            ->assertSessionHasErrors('kode');
        $this->assertSame(0, Attempt::where('user_id', $this->peserta->id)->count());
    }

    public function test_room_berpeserta_tidak_bisa_dihapus(): void
    {
        $kosong = $this->buatRoom();
        $this->actingAs($this->guru)->post(route('pengajar.room', $this->quiz->id), ['aksi' => 'hapus', 'id' => $kosong->id]);
        $this->assertNull(Room::find($kosong->id));

        $isi = $this->buatRoom();
        $this->actingAs($this->peserta)->post(route('room'), ['kode' => $isi->kode_akses]);
        $this->actingAs($this->guru)->post(route('pengajar.room', $this->quiz->id), ['aksi' => 'hapus', 'id' => $isi->id]);
        $this->assertNotNull(Room::find($isi->id));
    }

    public function test_pengajar_lain_tidak_bisa_mengelola_room(): void
    {
        $lain = User::where('role', 'pengajar')->where('id', '<>', $this->guru->id)->first();
        if (!$lain) $this->markTestSkipped('Seeder tidak punya pengajar kedua.');

        $this->actingAs($lain)->get(route('pengajar.room', $this->quiz->id))->assertForbidden();
        $this->actingAs($lain)->post(route('pengajar.room', $this->quiz->id), ['aksi' => 'buat'])->assertForbidden();
    }

    public function test_halaman_masuk_room_mengisi_kode_dari_tautan(): void
    {
        $this->actingAs($this->peserta)->get(route('room', ['kode' => 'k7qx-2m']))
            ->assertOk()->assertSee('value="K7QX2M"', false);
    }
}
