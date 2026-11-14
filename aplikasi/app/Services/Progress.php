<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Completion;
use App\Models\Training;
use App\Models\User;

/**
 * Aturan buka-kunci: jadwal dan urutan di 4 tingkat
 * (Pelatihan › Agenda › Mata Pelatihan › Aktivitas).
 */
class Progress
{
    private array $done;

    public function __construct(private User $user, private Training $training)
    {
        $ids = $training->agendas()->with('subjects.activities:id,subject_id')->get()
            ->flatMap(fn ($a) => $a->subjects)->flatMap(fn ($s) => $s->activities)->pluck('id');
        $this->done = Completion::where('user_id', $user->id)->whereIn('activity_id', $ids)->pluck('activity_id')->flip()->all();
    }

    public function isDone(int $activityId): bool { return isset($this->done[$activityId]); }

    public static function inWindow($model): ?string
    {
        $now = now();
        if ($model->opens_at && $now->lt($model->opens_at)) return 'belum';
        if ($model->closes_at && $now->gt($model->closes_at)) return 'tutup';
        return null;
    }

    private function subjectDone($subject): bool
    {
        foreach ($subject->activities as $a) if ($a->required && ! $this->isDone($a->id)) return false;
        return true;
    }

    private function agendaDone($agenda): bool
    {
        foreach ($agenda->subjects as $s) if (! $this->subjectDone($s)) return false;
        return true;
    }

    /**
     * Status satu aktivitas: ['ok'=>bool, 'reason'=>string|null]
     */
    public function status(Activity $act): array
    {
        $t = $this->training->loadMissing('agendas.subjects.activities');
        if ($w = self::inWindow($t)) return ['ok' => false, 'reason' => $w];
        foreach ($t->agendas as $ai => $ag) {
            foreach ($ag->subjects as $si => $sub) {
                foreach ($sub->activities as $xi => $a) {
                    if ($a->id !== $act->id) continue;
                    foreach ([$ag, $sub, $a] as $lvl) if ($w = self::inWindow($lvl)) return ['ok' => false, 'reason' => $w];
                    if ($t->sequential && $ag->sequential && $ai > 0 && ! $this->agendaDone($t->agendas[$ai - 1])) return ['ok' => false, 'reason' => 'agenda'];
                    if ($sub->sequential && $si > 0 && ! $this->subjectDone($ag->subjects[$si - 1])) return ['ok' => false, 'reason' => 'mapel'];
                    if ($a->sequential && $xi > 0) {
                        $prev = $sub->activities[$xi - 1];
                        if (! $this->isDone($prev->id)) return ['ok' => false, 'reason' => 'aktivitas'];
                    }
                    return ['ok' => true, 'reason' => null];
                }
            }
        }
        return ['ok' => false, 'reason' => 'tidak_ada'];
    }

    public static function reasonText(?string $r): string
    {
        return match ($r) {
            'belum' => __('Belum dibuka'),
            'tutup' => __('Sudah ditutup'),
            'agenda' => __('Selesaikan agenda sebelumnya dulu'),
            'mapel' => __('Selesaikan mata pelatihan sebelumnya dulu'),
            'aktivitas' => __('Selesaikan aktivitas sebelumnya dulu'),
            default => '',
        };
    }

    public function percent(): int
    {
        $all = 0; $d = 0;
        foreach ($this->training->loadMissing('agendas.subjects.activities')->agendas as $ag)
            foreach ($ag->subjects as $s) foreach ($s->activities as $a) if ($a->required) { $all++; if ($this->isDone($a->id)) $d++; }
        return $all ? (int) round($d / $all * 100) : 0;
    }
}
