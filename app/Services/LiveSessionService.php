<?php

namespace App\Services;

use App\Models\GameProgress;
use App\Models\LiveAnswer;
use App\Models\LiveParticipant;
use App\Models\LiveSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Quiz, oyun yarismasi ve klavye yarisi icin tek oturum motoru. Sunucu zamani esastir. */
class LiveSessionService
{
    private const RANK_XP = [1 => 30, 2 => 20, 3 => 10];
    private const FINISH_XP = 5;
    private const PARTICIPATION_XP = 2;
    private const GRACE_MS = 800;

    public function __construct(private XpService $xp) {}

    public function now(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    public function create(User $host, array $data): LiveSession
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
        } while (LiveSession::where('join_code', $code)->exists());

        return LiveSession::create([
            'type' => $data['type'],
            'host_user_id' => $host->id,
            'school_class_id' => $data['school_class_id'] ?? null,
            'quiz_id' => $data['quiz_id'] ?? null,
            'join_code' => $code,
            'config' => $data['config'] ?? [],
        ]);
    }

    public function canJoin(Student $student, LiveSession $s): bool
    {
        return $s->status !== 'finished' && (! $s->school_class_id || $s->school_class_id === $student->school_class_id);
    }

    public function join(Student $student, LiveSession $s): ?LiveParticipant
    {
        if (! $this->canJoin($student, $s)) {
            return null;
        }
        $existing = $s->participants()->where('student_id', $student->id)->first();
        if ($existing) {
            return $existing;
        }
        if ($s->is_locked) {
            return null;
        }

        return $s->participants()->create(['student_id' => $student->id, 'name' => $student->user->name, 'joined_at_ms' => $this->now()]);
    }

    public function start(LiveSession $s): void
    {
        if ($s->status !== 'lobby') {
            return;
        }
        $now = $this->now();
        $s->status = 'live';
        $s->started_at_ms = $now;
        $s->is_locked = true;
        if ($s->type === 'quiz') {
            $s->current_index = 0;
            $s->ends_at_ms = $now + $this->question($s, 0)->duration_sec * 1000;
        } else {
            $s->ends_at_ms = $now + ((int) ($s->config['duration'] ?? 300)) * 1000;
        }
        $s->save();
    }

    public function next(LiveSession $s): void
    {
        if ($s->status !== 'live' || $s->type !== 'quiz') {
            return;
        }
        $idx = $s->current_index + 1;
        if ($idx >= $s->quiz->questions()->count()) {
            $this->finish($s);

            return;
        }
        $s->current_index = $idx;
        $s->ends_at_ms = $this->now() + $this->question($s, $idx)->duration_sec * 1000;
        $s->save();
    }

    public function question(LiveSession $s, int $i)
    {
        return $s->quiz->questions()->skip($i)->first();
    }

    /** Yaris sureleri dolmus mu kontrol eder; durum sorgularinda tembel olarak cagrilir. */
    public function tick(LiveSession $s): LiveSession
    {
        if ($s->status === 'live' && $s->type === 'competition') {
            $this->syncCompetition($s);
        }
        if ($s->status === 'live' && $s->type !== 'quiz' && $s->ends_at_ms && $this->now() > $s->ends_at_ms) {
            $this->finish($s);
        }

        return $s->refresh();
    }

    public function answer(LiveParticipant $p, int $questionIndex, int $answer): array
    {
        $s = $p->session;
        if ($s->type !== 'quiz' || $s->status !== 'live' || $questionIndex !== $s->current_index) {
            return ['ok' => false, 'error' => 'Soru artık açık değil.'];
        }
        $now = $this->now();
        if ($now > $s->ends_at_ms + self::GRACE_MS) {
            return ['ok' => false, 'error' => 'Süre doldu.'];
        }
        $q = $this->question($s, $questionIndex);
        $correct = $answer === $q->correct_index;
        $duration = max(1, $q->duration_sec * 1000);
        $remaining = max(0, $s->ends_at_ms - $now);
        $points = $correct ? (int) round($q->xp * (0.5 + 0.5 * $remaining / $duration) * ($q->double_xp ? 2 : 1)) : 0;

        return DB::transaction(function () use ($p, $s, $questionIndex, $answer, $correct, $points, $now) {
            $created = LiveAnswer::firstOrCreate(
                ['live_session_id' => $s->id, 'live_participant_id' => $p->id, 'question_index' => $questionIndex],
                ['answer_index' => $answer, 'is_correct' => $correct, 'points' => $points, 'answered_at_ms' => $now],
            );
            if (! $created->wasRecentlyCreated) {
                return ['ok' => false, 'error' => 'Bu soruyu zaten yanıtladınız.'];
            }
            $p->increment('score', $points);

            return ['ok' => true, 'correct' => $correct, 'points' => $points];
        });
    }

    /** Klavye yarisi: istemci yalniz yazdigi metni yollar; ilerleme/dogruluk/KDK sunucuda hesaplanir. */
    public function typing(LiveParticipant $p, string $typed): void
    {
        $s = $p->session;
        if ($s->type !== 'typing' || $s->status !== 'live' || $p->finished_at_ms) {
            return;
        }
        $now = $this->now();
        if ($now > $s->ends_at_ms + self::GRACE_MS) {
            return;
        }
        $text = (string) ($s->config['text'] ?? '');
        $len = mb_strlen($text);
        $typed = mb_substr($typed, 0, $len);
        $n = mb_strlen($typed);
        $ok = 0;
        for ($i = 0; $i < $n; $i++) {
            $ok += mb_substr($typed, $i, 1) === mb_substr($text, $i, 1) ? 1 : 0;
        }
        $minutes = max(1 / 60, ($now - $s->started_at_ms) / 60000);
        $progress = $len ? min(100, round($ok / $len * 100, 2)) : 0;

        $p->progress = max($p->progress, $progress);
        $p->accuracy = $n ? (int) round($ok / $n * 100) : 100;
        $p->wpm = min(300, (int) round(($ok / 5) / $minutes));
        if ($n >= $len && $ok === $len && ! $p->finished_at_ms) {
            $p->finished_at_ms = $now;
            $p->progress = 100;
        }
        $p->save();
    }

    /** Oyun yarismasi ilerlemesi: oturum basladiktan sonra tamamlanan seviyelerden turetilir. */
    public function syncCompetition(LiveSession $s): void
    {
        $slug = $s->config['game_slug'] ?? null;
        $from = (int) ($s->config['level_from'] ?? 1);
        $to = (int) ($s->config['level_to'] ?? 1);
        $total = max(1, $to - $from + 1);
        $since = \Carbon\Carbon::createFromTimestampMs($s->started_at_ms);

        foreach ($s->participants as $p) {
            if ($p->finished_at_ms) {
                continue;
            }
            $done = GameProgress::where(['student_id' => $p->student_id, 'game_slug' => $slug])
                ->whereBetween('level', [$from, $to])->where('updated_at', '>=', $since)->count();
            $p->progress = round($done / $total * 100, 2);
            if ($done >= $total) {
                $p->finished_at_ms = $this->now();
            }
            $p->save();
        }
    }

    public function ranked(LiveSession $s)
    {
        $list = $s->participants()->get();

        return $s->type === 'quiz'
            ? $list->sortByDesc('score')->values()
            : $list->sort(fn ($a, $b) => [$b->progress, $a->finished_at_ms ?? PHP_INT_MAX, $b->wpm] <=> [$a->progress, $b->finished_at_ms ?? PHP_INT_MAX, $a->wpm])->values();
    }

    public function finish(LiveSession $s): void
    {
        DB::transaction(function () use ($s) {
            $s = LiveSession::lockForUpdate()->find($s->id);
            if ($s->status === 'finished' && $s->xp_granted_at) {
                return;
            }
            if ($s->type === 'competition' && $s->status === 'live') {
                $this->syncCompetition($s);
            }
            $s->status = 'finished';
            $s->finished_at_ms ??= $this->now();
            $s->xp_granted_at = now();
            $s->save();

            foreach ($this->ranked($s) as $i => $p) {
                $rank = $i + 1;
                $xp = $s->type === 'quiz'
                    ? $p->score
                    : ($p->finished_at_ms ? (self::RANK_XP[$rank] ?? self::FINISH_XP) : self::PARTICIPATION_XP);
                $p->update(['rank_no' => $rank, 'xp_earned' => $xp]);
                if ($xp > 0) {
                    $this->xp->award($p->student, $xp, LiveSession::TYPES[$s->type].' #'.$rank, $s);
                }
            }
        });
    }

    /** Hem host hem ogrenci icin JSON anlik goruntusu. $forStudent=true iken dogru cevap sizdirilmaz. */
    public function snapshot(LiveSession $s, bool $forStudent, ?LiveParticipant $me = null): array
    {
        $s = $this->tick($s);
        $out = [
            'now' => $this->now(), 'type' => $s->type, 'status' => $s->status, 'locked' => $s->is_locked,
            'ends_at' => $s->ends_at_ms, 'count' => $s->participants()->count(),
            'board' => $this->ranked($s)->take(15)->map(fn ($p) => [
                'name' => $p->name, 'score' => $p->score, 'progress' => (float) $p->progress, 'wpm' => $p->wpm, 'rank' => $p->rank_no,
            ])->all(),
        ];

        if ($s->type === 'quiz') {
            $total = $s->quiz->questions()->count();
            $out['total'] = $total;
            if ($s->status === 'live') {
                $q = $this->question($s, $s->current_index);
                $closed = $this->now() > $s->ends_at_ms;
                $out['index'] = $s->current_index;
                $out['question'] = ['text' => $q->text, 'options' => $q->options, 'duration' => $q->duration_sec, 'double' => $q->double_xp];
                if (! $forStudent || $closed) {
                    $out['correct'] = $q->correct_index;
                }
                if (! $forStudent) {
                    $out['answered'] = $s->answers()->where('question_index', $s->current_index)->count();
                }
                if ($me) {
                    $out['my_answer'] = $me->id ? LiveAnswer::where(['live_session_id' => $s->id, 'live_participant_id' => $me->id, 'question_index' => $s->current_index])->first(['answer_index', 'is_correct', 'points']) : null;
                }
            }
        } else {
            $out['config'] = $forStudent && $s->type === 'typing' && $s->status !== 'live' ? [] : $s->config;
        }
        if ($me) {
            $me->refresh();
            $out['me'] = ['score' => $me->score, 'progress' => (float) $me->progress, 'wpm' => $me->wpm, 'accuracy' => $me->accuracy, 'rank' => $me->rank_no, 'xp' => $me->xp_earned, 'done' => (bool) $me->finished_at_ms];
        }

        return $out;
    }
}
