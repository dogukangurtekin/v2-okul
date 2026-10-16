<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseSlideController extends Controller
{
    private function data(Request $request): array
    {
        $v = $request->validate([
            'type' => ['required', 'in:'.implode(',', array_keys(CourseSlide::TYPES))],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'url' => ['nullable', 'string', 'max:500'],
            'question' => ['nullable', 'string'],
            'options' => ['nullable', 'string'],   // satir basina bir secenek
            'answer' => ['nullable', 'integer', 'min:0'],
            'mode' => ['nullable', 'in:choice,short,matching'],
            'short_answers' => ['nullable', 'string', 'max:500'],   // "|" ile ayrilmis kabul edilen cevaplar
            'pairs' => ['nullable', 'string', 'max:3000'],          // satir basina "sol = sag"
        ]);

        $content = match ($v['type']) {
            'quiz' => $this->quizContent($v),
            'image', 'video' => ['url' => $v['url'] ?? '', 'body' => $v['body'] ?? ''],
            default => ['body' => $v['body'] ?? ''],
        };

        return ['type' => $v['type'], 'title' => $v['title'] ?? null, 'content' => $content];
    }

    private function quizContent(array $v): array
    {
        $mode = $v['mode'] ?? 'choice';
        $question = $v['question'] ?? '';

        if ($mode === 'short') {
            $answers = array_values(array_filter(array_map('trim', explode('|', $v['short_answers'] ?? ''))));
            abort_if(! $answers, 422, 'Kısa cevap için en az bir kabul edilen cevap girin.');

            return ['mode' => 'short', 'question' => $question, 'answers' => $answers];
        }
        if ($mode === 'matching') {
            $pairs = [];
            foreach (preg_split('/\R/', $v['pairs'] ?? '') as $line) {
                if (str_contains($line, '=') && count($parts = array_map('trim', explode('=', $line, 2))) === 2 && $parts[0] !== '' && $parts[1] !== '') {
                    $pairs[] = $parts;
                }
            }
            abort_if(count($pairs) < 2, 422, 'Eşleştirme için en az 2 "sol = sağ" satırı girin.');

            return ['mode' => 'matching', 'question' => $question, 'pairs' => $pairs];
        }
        $options = array_values(array_filter(array_map('trim', preg_split('/\R/', $v['options'] ?? ''))));
        abort_if(count($options) < 2 || (int) ($v['answer'] ?? 0) >= count($options), 422, 'En az 2 seçenek ve geçerli bir doğru cevap gerekli.');

        return ['mode' => 'choice', 'question' => $question, 'options' => $options, 'answer' => (int) ($v['answer'] ?? 0)];
    }

    /** Formun yönetmediği alanlar (içe aktarılan html, yönerge, v1 künyesi) korunur; metin değiştiyse html atılır. */
    private function keepExtras(array $new, array $old): array
    {
        foreach (['note', 'v1'] as $k) {
            if (isset($old[$k]) && ! isset($new[$k])) {
                $new[$k] = $old[$k];
            }
        }
        if (isset($old['html']) && ($new['body'] ?? null) === ($old['body'] ?? null)) {
            $new['html'] = $old['html'];
        }

        return $new;
    }

    public function store(Request $request, Course $course)
    {
        $this->authorize('manage', $course);
        $course->slides()->create($this->data($request) + ['position' => ($course->slides()->max('position') ?? -1) + 1]);

        return back()->with('ok', 'Slayt eklendi.');
    }

    public function update(Request $request, Course $course, CourseSlide $slide)
    {
        $this->authorize('manage', $course);
        abort_unless($slide->course_id === $course->id, 404);
        $d = $this->data($request);
        $d['content'] = $this->keepExtras($d['content'], (array) $slide->content);
        $slide->update($d);

        return back()->with('ok', 'Slayt güncellendi.');
    }

    public function destroy(Course $course, CourseSlide $slide)
    {
        $this->authorize('manage', $course);
        abort_unless($slide->course_id === $course->id, 404);
        $slide->delete();
        $course->slides()->get()->each(fn ($s, $i) => $s->update(['position' => $i]));

        return back()->with('ok', 'Slayt silindi.');
    }

    public function reorder(Request $request, Course $course)
    {
        $this->authorize('manage', $course);
        $ids = $request->validate(['order' => ['required', 'array'], 'order.*' => ['integer']])['order'];
        DB::transaction(function () use ($ids, $course) {
            foreach ($ids as $pos => $id) {
                $course->slides()->whereKey($id)->update(['position' => $pos]);
            }
        });

        return response()->json(['ok' => true]);
    }
}
