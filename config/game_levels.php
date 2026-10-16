<?php

return [
    // S = baslangic (saga bakar), G = hedef, # = duvar, . = bos. Komutlar: ileri / sol / sag.
    'block-grid-runner' => [
        ['max' => 4, 'grid' => ['.....', '.....', 'S...G', '.....', '.....']],
        ['max' => 5, 'grid' => ['.....', '.....', 'S.#..', '...G.', '.....']],
        ['max' => 6, 'grid' => ['G....', '.###.', '.....', '.#...', 'S....']],
        ['max' => 7, 'grid' => ['.....', 'S###.', '.....', '.###.', '....G']],
        ['max' => 8, 'grid' => ['..G..', '.###.', '.....', '.#.#.', 'S....']],
        ['max' => 9, 'grid' => ['S.#.G', '.##..', '.....', '.####', '.....']],
    ],
    'keyboard-race' => [
        ['text' => 'merhaba dunya'],
        ['text' => 'kod yazmak cok eglenceli'],
        ['text' => 'bilgisayar bilimi gelecegin dilidir'],
        ['text' => 'once dusun sonra kodla sonra test et'],
        ['text' => 'her hata yeni bir ogrenme firsatidir'],
    ],

    // Python ciktisini tahmin et. 'output' ve 'explain' yalnizca sunucuda kalir.
    'silent-teacher-runner' => [
        ['title' => 'print', 'code' => "print(\"Merhaba\")\nprint(\"Dünya\")", 'output' => "Merhaba\nDünya", 'explain' => 'print her çağrıda yeni satıra yazar.'],
        ['title' => 'Değişkenler', 'code' => "x = 5\ny = 3\nprint(x + y)", 'output' => '8', 'explain' => 'x + y = 5 + 3 = 8.'],
        ['title' => 'Aritmetik', 'code' => "a = 10\nb = 4\nprint(a - b)\nprint(a * b)", 'output' => "6\n40", 'explain' => '10 - 4 = 6 ve 10 * 4 = 40.'],
        ['title' => 'Metin birleştirme', 'code' => "ad = \"Ada\"\nprint(\"Merhaba \" + ad)", 'output' => 'Merhaba Ada', 'explain' => '+ metinleri birleştirir; "Merhaba " sonundaki boşluk korunur.', 'hint' => 'Boşluğa dikkat et.'],
        ['title' => 'Bölme ve kalan', 'code' => "n = 7\nprint(n // 2)\nprint(n % 2)", 'output' => "3\n1", 'explain' => '// tam bölmedir (7//2 = 3), % kalandır (7%2 = 1).'],
        ['title' => 'if / else', 'code' => "x = 15\nif x > 10:\n    print(\"büyük\")\nelse:\n    print(\"küçük\")", 'output' => 'büyük', 'explain' => '15 > 10 doğru olduğu için if bloğu çalışır.'],
        ['title' => 'for döngüsü', 'code' => "for i in range(3):\n    print(i * 2)", 'output' => "0\n2\n4", 'explain' => 'range(3) 0,1,2 verir; her biri 2 ile çarpılır.'],
        ['title' => 'Toplama döngüsü', 'code' => "t = 0\nfor i in range(1, 5):\n    t = t + i\nprint(t)", 'output' => '10', 'explain' => 'range(1, 5) → 1,2,3,4; toplamları 10.', 'hint' => 'range(1, 5) 5\'i içermez.'],
        ['title' => 'Liste', 'code' => "liste = [4, 8, 15]\nprint(len(liste))\nprint(liste[1])", 'output' => "3\n8", 'explain' => 'len listenin uzunluğudur (3); indeks 0\'dan başlar, liste[1] = 8.'],
        ['title' => 'while döngüsü', 'code' => "s = 1\nwhile s < 20:\n    s = s * 3\nprint(s)", 'output' => '27', 'explain' => 's: 1 → 3 → 9 → 27; 27 < 20 yanlış olunca döngü biter.'],
        ['title' => 'Fonksiyon', 'code' => "def kare(n):\n    return n * n\nprint(kare(4) + kare(2))", 'output' => '20', 'explain' => 'kare(4) = 16, kare(2) = 4; toplam 20.'],
        ['title' => 'Metin ve döngü', 'code' => "s = \"\"\nfor ch in \"abc\":\n    s = ch + s\nprint(s)", 'output' => 'cba', 'explain' => 'Her harf başa eklenir: a → ba → cba.', 'hint' => 'Harf sona değil başa ekleniyor.'],
    ],

    // Lightbot (2B). S baslangic (doguya bakar), L yakilacak isik, # duvar. main_max/proc_max slot siniri.
    'lightbot-runner' => [
        ['title' => 'İlk ışık', 'main_max' => 6, 'proc_max' => 0, 'grid' => ['S..L.', '.....', '.....', '.....', '.....']],
        ['title' => 'Dönüş', 'main_max' => 8, 'proc_max' => 0, 'grid' => ['S....', '.....', '...L.', '.....', '.....']],
        ['title' => 'Duvarı aş', 'main_max' => 12, 'proc_max' => 0, 'grid' => ['S.#..', '..#..', '..#L.', '.....', '.....']],
        ['title' => 'Prosedür', 'main_max' => 3, 'proc_max' => 3, 'grid' => ['S.L.L', '.....', '.....', '.....', '.....']],
        ['title' => 'Kare turu', 'main_max' => 3, 'proc_max' => 6, 'grid' => ['S...L', '.....', '.....', '.....', 'L...L']],
        ['title' => 'Kenar yolu', 'main_max' => 7, 'proc_max' => 4, 'grid' => ['S...L', '.###.', '.....', '.###.', 'L....']],
    ],

    // Web Atolyesi: 'checks' yalnizca sunucuda. Onizleme JavaScript calistirmaz.
    'web-lab-runner' => [
        ['title' => 'Başlık', 'task' => '<h1> etiketiyle içinde "Merhaba" yazan bir başlık ekle.', 'html' => "<!-- kodunu buraya yaz -->\n", 'explain' => 'h1 en büyük başlık etiketidir.',
            'checks' => [['type' => 'tag', 'tag' => 'h1', 'text' => 'merhaba', 'msg' => '"Merhaba" yazan bir <h1> bulunamadı.']]],
        ['title' => 'Paragraf', 'task' => 'Bir <h1> başlık ve altında en az 10 karakterlik bir <p> paragraf yaz.', 'html' => "<h1>Sayfam</h1>\n", 'explain' => 'p paragraf etiketidir.',
            'checks' => [['type' => 'tag', 'tag' => 'h1', 'msg' => '<h1> başlık eksik.'], ['type' => 'tag', 'tag' => 'p', 'min_text' => 10, 'msg' => 'En az 10 karakterlik bir <p> yok.']]],
        ['title' => 'Liste', 'task' => 'En az 3 maddeli bir <ul> listesi oluştur (<li> kullan).', 'html' => '', 'explain' => 'ul madde işaretli liste, li her madde.',
            'checks' => [['type' => 'tag', 'tag' => 'ul', 'msg' => '<ul> yok.'], ['type' => 'tag', 'tag' => 'li', 'min' => 3, 'msg' => 'En az 3 <li> gerekli.']]],
        ['title' => 'Bağlantı', 'task' => 'href özelliği "http" ile başlayan ve içinde yazı olan bir <a> bağlantısı ekle.', 'html' => '', 'explain' => 'a etiketi href ile bağlantı verir.',
            'checks' => [['type' => 'attr', 'tag' => 'a', 'attr' => 'href', 'prefix' => 'http', 'msg' => 'href="http..." olan bir <a> yok.'], ['type' => 'tag', 'tag' => 'a', 'min_text' => 1, 'msg' => 'Bağlantının içinde yazı olmalı.']]],
        ['title' => 'Resim', 'task' => 'src ve alt özellikleri dolu bir <img> ekle (alt, erişilebilirlik için önemlidir).', 'html' => '', 'explain' => 'alt metni görme engelli kullanıcılar için resmi anlatır.',
            'checks' => [['type' => 'attr', 'tag' => 'img', 'attr' => 'src', 'msg' => '<img> için src yok.'], ['type' => 'attr', 'tag' => 'img', 'attr' => 'alt', 'msg' => '<img> için alt metni yok.']]],
        ['title' => 'Renk', 'task' => 'CSS ile h1 başlığın rengini kırmızı yap (color).', 'html' => "<h1>Renkli başlık</h1>\n", 'css' => "h1 {\n  \n}\n", 'explain' => 'color metin rengini belirler.',
            'checks' => [['type' => 'css', 'selector' => 'h1', 'prop' => 'color', 'values' => ['red', '#f00', '#ff0000', 'rgb(255,0,0)'], 'msg' => 'h1 için color kırmızı olmalı.']]],
        ['title' => 'Boyut ve arka plan', 'task' => 'p için font-size: 20px ve herhangi bir background-color ver.', 'html' => "<p>Merhaba dünya!</p>\n", 'css' => "p {\n  \n}\n", 'explain' => 'font-size yazı boyutu, background-color arka plan rengidir.',
            'checks' => [['type' => 'css', 'selector' => 'p', 'prop' => 'font-size', 'values' => ['20px'], 'msg' => 'p için font-size: 20px gerekli.'], ['type' => 'css', 'selector' => 'p', 'prop' => 'background-color', 'msg' => 'p için background-color eksik.']]],
        ['title' => 'Sınıf (class)', 'task' => 'Bir <div class="kutu"> oluştur ve CSS\'te .kutu için border ile padding ver.', 'html' => '', 'css' => '', 'explain' => 'Sınıflar birden çok elemanı aynı kuralla biçimlendirir.',
            'checks' => [['type' => 'class', 'class' => 'kutu', 'msg' => 'class="kutu" olan eleman yok.'], ['type' => 'css', 'selector' => '.kutu', 'prop' => 'border', 'msg' => '.kutu için border eksik.'], ['type' => 'css', 'selector' => '.kutu', 'prop' => 'padding', 'msg' => '.kutu için padding eksik.']]],
    ],

    // Arı Bahçesi (Flexbox). 'solution' ve 'hint'/'explain' yalnızca sunucuda; istemci yalnızca çiçek konumlarını görür.
    'bee-garden-runner' => [
        ['title' => 'Sağa yasla', 'task' => 'Arıyı çiçeğe ulaştır: ana eksende sona yasla.', 'bees' => 1, 'solution' => ['justify-content' => 'flex-end'], 'hint' => 'justify-content yatay (ana) ekseni belirler.', 'explain' => 'flex-end ögeleri ana eksenin sonuna iter.'],
        ['title' => 'Ortala', 'task' => 'Arıyı yatayda tam ortaya getir.', 'bees' => 1, 'solution' => ['justify-content' => 'center'], 'hint' => 'justify-content: center', 'explain' => 'center ögeleri ana eksende ortalar.'],
        ['title' => 'Aşağı in', 'task' => 'Arıyı bahçenin altına, sol köşeye indir.', 'bees' => 1, 'solution' => ['align-items' => 'flex-end'], 'hint' => 'align-items çapraz (dikey) ekseni belirler.', 'explain' => 'align-items çapraz eksendeki yerleşimi belirler.'],
        ['title' => 'Tam orta', 'task' => 'Arıyı hem yatayda hem dikeyde ortala.', 'bees' => 1, 'solution' => ['justify-content' => 'center', 'align-items' => 'center'], 'hint' => 'İki özelliği birlikte kullan.', 'explain' => 'Ortalamak için justify-content ve align-items birlikte kullanılır.'],
        ['title' => 'Eşit aralık', 'task' => 'Üç arıyı çiçeklere ulaştır: ilki sola, sonuncusu sağa, arası eşit.', 'bees' => 3, 'solution' => ['justify-content' => 'space-between'], 'hint' => 'space-between ilk ve son ögeyi uçlara koyar.', 'explain' => 'space-between boşluğu ögelerin arasına eşit dağıtır.'],
        ['title' => 'Ters sıra', 'task' => 'İki arı sağdan başlayıp sola doğru sıralanacak ve dikeyde ortada olacak.', 'bees' => 2, 'solution' => ['flex-direction' => 'row-reverse', 'justify-content' => 'flex-start', 'align-items' => 'center'], 'hint' => 'flex-direction ana ekseni ve sırayı değiştirir.', 'explain' => 'row-reverse ana ekseni tersine çevirir; flex-start artık sağ kenardır.'],
        ['title' => 'Sütun', 'task' => 'Üç arı alt alta, sağ kenarda ve aralarında/etrafında eşit boşlukla dizilecek.', 'bees' => 3, 'solution' => ['flex-direction' => 'column', 'justify-content' => 'space-around', 'align-items' => 'flex-end'], 'hint' => 'column ile eksenler yer değiştirir.', 'explain' => 'column ana ekseni dikey yapar: justify-content artık dikeyi, align-items yatayı belirler.'],
        ['title' => 'Ters sütun', 'task' => 'İki arı sol kenarda, dikeyde yukarıda ve ters sırada olacak.', 'bees' => 2, 'solution' => ['flex-direction' => 'column-reverse', 'justify-content' => 'flex-end'], 'hint' => 'column-reverse\'de flex-end yukarıdır.', 'explain' => 'column-reverse ile ana eksen aşağıdan yukarı gider; flex-end üst kenardır.'],
    ],

    // Python Editörü görevleri. 'expect', 'must', 'must_not', 'explain', 'hint' yalnızca sunucuda.
    // must: kodda (yorum ve metin içeriği hariç) bulunmalı; must_not: kodda (metin dahil, yorum hariç) bulunmamalı.
    'python-editor' => [
        ['title' => 'print', 'task' => 'Ekrana tam olarak  Merhaba Python  yazdır.', 'starter' => "# kodunu buraya yaz\n", 'expect' => 'Merhaba Python', 'hint' => 'print("...") kullan.',
            'must' => ['/\bprint\s*\(/' => 'print(...) kullanmalısın.'], 'must_not' => [], 'explain' => 'print, parantez içindeki değeri ekrana yazar.'],
        ['title' => 'Değişken', 'task' => 'yas adlı değişkene 12 ata, sonra değişkeni kullanarak  Yaşım: 12  yazdır.', 'starter' => "yas = \n", 'expect' => 'Yaşım: 12', 'hint' => 'print("Yaşım:", yas) araya boşluk koyar.',
            'must' => ['/\byas\s*=\s*12\b/' => 'yas = 12 yazmalısın.', '/\bprint\s*\(.*\byas\b/s' => 'print içinde yas değişkenini kullanmalısın.'],
            'must_not' => ['/["\']Ya[şs]ım:\s*12/u' => 'Cevabı doğrudan metin olarak yazma; değişkeni kullan.'], 'explain' => 'Değişkenler değer saklar; print birden çok değeri araya boşluk koyarak yazar.'],
        ['title' => 'Hesap', 'task' => '7 çarpı 8 artı 2 işleminin sonucunu hesaplatıp yazdır (sonucu kendin yazma).', 'starter' => "print()\n", 'expect' => '58', 'hint' => '* çarpma, + toplama; çarpma önce yapılır.',
            'must' => ['/\*/' => 'Çarpma işleci (*) kullanmalısın.', '/\+/' => 'Toplama işleci (+) kullanmalısın.'], 'must_not' => ['/\b58\b/' => 'Sonucu kendin yazma, Python hesaplasın.'], 'explain' => '7 * 8 + 2 = 58 (çarpma önceliklidir).'],
        ['title' => 'if / else', 'task' => 'sayi = 17 olsun. Sayı tekse  Tek , çiftse  Çift  yazdır (if, else ve % kullan).', 'starter' => "sayi = 17\n", 'expect' => 'Tek', 'hint' => 'sayi % 2 kalanı verir.',
            'must' => ['/\bsayi\s*=\s*17\b/' => 'sayi = 17 yazmalısın.', '/\bif\b/' => 'if kullanmalısın.', '/\belse\b/' => 'else kullanmalısın.', '/%/' => 'Kalan işleci (%) kullanmalısın.'], 'must_not' => [], 'explain' => '17 % 2 = 1 olduğundan sayı tektir.'],
        ['title' => 'for döngüsü', 'task' => '1\'den 5\'e kadar (5 dahil) sayıları alt alta yazdır. for ve range kullan.', 'starter' => "for i in range():\n    \n", 'expect' => "1\n2\n3\n4\n5", 'hint' => 'range(1, 6) 6\'yı içermez.',
            'must' => ['/\bfor\b/' => 'for döngüsü kullanmalısın.', '/\brange\s*\(/' => 'range(...) kullanmalısın.'], 'must_not' => ['/print\s*\(\s*["\']?\s*1\b/' => 'Sayıları tek tek yazma; döngüyü kullan.'], 'explain' => 'range(1, 6) 1,2,3,4,5 üretir.'],
        ['title' => 'Toplam', 'task' => '1\'den 10\'a kadar sayıların toplamını bir döngüyle hesaplayıp yazdır.', 'starter' => "toplam = 0\n", 'expect' => '55', 'hint' => 'Döngüde toplam değişkenine ekle.',
            'must' => ['/\bfor\b|\bwhile\b/' => 'Bir döngü (for/while) kullanmalısın.'], 'must_not' => ['/\b55\b/' => 'Sonucu kendin yazma, döngü hesaplasın.'], 'explain' => '1+2+…+10 = 55.'],
        ['title' => 'Fonksiyon', 'task' => 'def ile toplam(a, b) fonksiyonu yaz (return ile sonucu döndür). toplam(3, 4) sonucunu yazdır.', 'starter' => "def toplam(a, b):\n    \n", 'expect' => '7', 'hint' => 'return a + b',
            'must' => ['/\bdef\s+toplam\s*\(/' => 'def toplam(...) tanımlamalısın.', '/\breturn\b/' => 'return kullanmalısın.'], 'must_not' => ['/print\s*\(\s*7\b/' => 'Sonucu kendin yazma, fonksiyon hesaplasın.'], 'explain' => 'Fonksiyon bir değer üretip return ile geri verir.'],
        ['title' => 'while döngüsü', 'task' => '3\'ten 1\'e geri sayan bir while döngüsü yaz (her sayı ayrı satırda), sonra  Başla  yazdır.', 'starter' => "n = 3\n", 'expect' => "3\n2\n1\nBaşla", 'hint' => 'Döngü içinde n\'i azaltmayı unutma.',
            'must' => ['/\bwhile\b/' => 'while döngüsü kullanmalısın.'], 'must_not' => ['/print\s*\(\s*["\']?\s*3\b/' => 'Sayıları tek tek yazma; döngüyü kullan.'], 'explain' => 'while koşul doğru olduğu sürece tekrar eder; n -= 1 olmazsa sonsuz döngü olur.'],
    ],
];
