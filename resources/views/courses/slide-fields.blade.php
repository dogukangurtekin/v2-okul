@php($c = $slide?->content ?? [])
<select name="type" class="border rounded px-2 py-1">@foreach(\App\Models\CourseSlide::TYPES as $k => $v)<option value="{{ $k }}" @selected(($slide?->type ?? 'text') === $k)>{{ $v }}</option>@endforeach</select>
<input name="title" value="{{ $slide?->title }}" placeholder="Başlık" class="w-full border rounded px-3 py-1">
<textarea name="body" rows="3" placeholder="İçerik (metin / kod / açıklama)" class="w-full border rounded px-3 py-1">{{ $c['body'] ?? '' }}</textarea>
<input name="url" value="{{ $c['url'] ?? '' }}" placeholder="Görsel / video URL (görsel ve video türü)" class="w-full border rounded px-3 py-1">
<input name="question" value="{{ $c['question'] ?? '' }}" placeholder="Soru (soru türü)" class="w-full border rounded px-3 py-1">
<textarea name="options" rows="3" placeholder="Seçenekler (her satıra bir tane)" class="w-full border rounded px-3 py-1">{{ implode("\n", $c['options'] ?? []) }}</textarea>
<input name="answer" type="number" min="0" value="{{ $c['answer'] ?? 0 }}" class="border rounded px-3 py-1" title="Doğru seçenek (0'dan başlar)">
<div class="text-xs text-slate-500 pt-1">Soru türü (yalnızca “Soru” slaytı):</div>
<select name="mode" class="border rounded px-2 py-1">@foreach(['choice' => 'Çoktan seçmeli / doğru-yanlış', 'short' => 'Kısa cevap', 'matching' => 'Eşleştirme'] as $mk => $mv)<option value="{{ $mk }}" @selected(($c['mode'] ?? 'choice') === $mk)>{{ $mv }}</option>@endforeach</select>
<input name="short_answers" value="{{ implode(' | ', $c['answers'] ?? []) }}" placeholder="Kısa cevap: kabul edilen cevaplar (| ile ayır)" class="w-full border rounded px-3 py-1">
<textarea name="pairs" rows="3" placeholder="Eşleştirme: her satıra  sol = sağ" class="w-full border rounded px-3 py-1">{{ collect($c['pairs'] ?? [])->map(fn($p) => $p[0].' = '.$p[1])->implode("\n") }}</textarea>
