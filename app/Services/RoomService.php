<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Quiz;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RoomService
{
    /** Tanpa 0/O dan 1/I/L agar kode mudah dibacakan di kelas. */
    private const HURUF = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const PANJANG = 6;

    /** Room milik kuis, terbaru dulu, beserta jumlah peserta. */
    public function forQuiz(int $quizId): Collection
    {
        return Room::where('quiz_id', $quizId)->withCount('attempts')->latest('id')->get();
    }

    /**
     * Ringkasan room lintas kuis. $ownerId null = semua room (admin),
     * selain itu hanya room di kuis milik pengajar tersebut.
     */
    public function overview(?int $ownerId, array $f, int $limit, int $offset = 0): Collection
    {
        return $this->overviewQuery($ownerId, $f)
            ->with(['quiz:id,judul,user_id', 'quiz.owner:id,nama', 'attempts' => fn ($a) => $a->with('user:id,nama')->latest('id')->limit(3)])
            ->withCount(['attempts', 'attempts as sedang_count' => fn ($a) => $a->where('status', 'berlangsung')])
            ->orderByRaw("CASE status WHEN 'buka' THEN 0 ELSE 1 END")
            ->latest('id')
            ->limit($limit)->offset($offset)
            ->get();
    }

    public function overviewCount(?int $ownerId, array $f): int
    {
        return $this->overviewQuery($ownerId, $f)->count();
    }

    private function overviewQuery(?int $ownerId, array $f): Builder
    {
        $kata = $f['q'] ?? '';
        return Room::query()
            ->when($ownerId, fn (Builder $q) => $q->whereHas('quiz', fn (Builder $k) => $k->where('user_id', $ownerId)))
            ->when(($f['status'] ?? '') !== '', fn (Builder $q) => $q->where('status', $f['status']))
            ->when($kata !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('kode_akses', 'like', '%' . $this->normalize($kata) . '%')
                ->orWhereHas('quiz', fn (Builder $k) => $k->where('judul', 'like', "%$kata%"))));
    }

    public function find(int $quizId, int $roomId): ?Room
    {
        return Room::where('quiz_id', $quizId)->whereKey($roomId)->first();
    }

    public function create(Quiz $quiz, User $owner): Room
    {
        return Room::create([
            'quiz_id' => $quiz->id,
            'user_id' => $owner->id,
            'kode_akses' => $this->newCode(),
            'status' => 'buka',
        ]);
    }

    public function setStatus(Room $room, string $status): void
    {
        $room->update(['status' => $status]);
    }

    /** Room yang sudah punya peserta tidak dihapus agar riwayat room tetap utuh; tutup saja. */
    public function delete(Room $room): bool
    {
        if ($room->attempts()->exists()) return false;
        $room->delete();
        return true;
    }

    public function normalize(?string $kode): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $kode));
    }

    public function findByCode(string $kode): ?Room
    {
        return $kode === '' ? null : Room::where('kode_akses', $kode)->first();
    }

    /** Percobaan di room ini, yang berjalan tampil paling atas. */
    public function participants(Room $room): Collection
    {
        return Attempt::with('user')->where('room_id', $room->id)
            ->orderByRaw("CASE status WHEN 'berlangsung' THEN 0 WHEN 'menunggu' THEN 1 ELSE 2 END")
            ->orderByDesc('id')
            ->get();
    }

    private function newCode(): string
    {
        do {
            $kode = '';
            for ($i = 0; $i < self::PANJANG; $i++) {
                $kode .= self::HURUF[random_int(0, strlen(self::HURUF) - 1)];
            }
        } while (Room::where('kode_akses', $kode)->exists());
        return $kode;
    }
}
