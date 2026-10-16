@php($c = $slide?->content ?? [])
@php($t = $slide?->type ?? 'text')
<div data-sf>
  <div class="fgrid">
    <x-fg label="Slayt türü"><select name="type" class="field" data-type>@foreach(\App\Models\CourseSlide::TYPES as $k => $v)<option value="{{ $k }}" @selected($t === $k)>{{ $v }}</option>@endforeach</select></x-fg>
    <x-fg label="Başlık"><input name="title" value="{{ $slide?->title }}" class="field" placeholder="Slayt başlığı"></x-fg>
  </div>
  <div data-for="text code image video" style="margin-top:.9rem"><x-fg label="İçerik" help="Metin, kod ya da görselin altındaki açıklama. İçe aktarılan zengin içerik (HTML) metne dokunmazsanız korunur."><textarea name="body" rows="4" class="field" placeholder="İçerik">{{ $c['body'] ?? '' }}</textarea></x-fg></div>
  <div data-for="image video" style="margin-top:.9rem"><x-fg label="Görsel veya video adresi (URL)" help="YouTube ve Vimeo bağlantıları gömülü oynatılır."><input name="url" value="{{ $c['url'] ?? '' }}" class="field" placeholder="https://…"></x-fg></div>
  <div data-for="quiz" style="margin-top:.9rem;display:grid;gap:.9rem">
    <x-fg label="Soru"><input name="question" value="{{ $c['question'] ?? '' }}" class="field" placeholder="Soru metni"></x-fg>
    <x-fg label="Soru türü"><select name="mode" class="field" data-mode>@foreach(['choice' => 'Çoktan seçmeli / doğru-yanlış', 'short' => 'Kısa cevap', 'matching' => 'Eşleştirme'] as $mk => $mv)<option value="{{ $mk }}" @selected(($c['mode'] ?? 'choice') === $mk)>{{ $mv }}</option>@endforeach</select></x-fg>
    <div data-mode-for="choice" class="fgrid"><x-fg label="Seçenekler" help="Her satıra bir seçenek yazın." full><textarea name="options" rows="4" class="field">{{ implode("\n", $c['options'] ?? []) }}</textarea></x-fg>
      <x-fg label="Doğru seçenek no" help="0'dan başlar: ilk seçenek 0, ikincisi 1…"><input name="answer" type="number" min="0" value="{{ $c['answer'] ?? 0 }}" class="field"></x-fg></div>
    <div data-mode-for="short"><x-fg label="Kabul edilen cevaplar" help="Birden fazla ise | ile ayırın (büyük/küçük harf önemsiz)."><input name="short_answers" value="{{ implode(' | ', $c['answers'] ?? []) }}" class="field"></x-fg></div>
    <div data-mode-for="matching"><x-fg label="Eşleştirme çiftleri" help="Her satıra:  sol = sağ"><textarea name="pairs" rows="4" class="field">{{ collect($c['pairs'] ?? [])->map(fn($p) => $p[0].' = '.$p[1])->implode("\n") }}</textarea></x-fg></div>
  </div>
</div>
