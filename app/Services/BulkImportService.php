<?php

namespace App\Services;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use App\Support\XlsxReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class BulkImportService
{
    public const MAX_ROWS = 1000;
    private const PW_ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    private const ALIASES = [
        'name' => ['ad_soyad', 'adi_soyadi', 'ad', 'isim', 'name'],
        'student_no' => ['ogrenci_no', 'okul_no', 'numara', 'no', 'student_no'],
        'class' => ['sinif', 'sube', 'class'],
        'username' => ['kullanici_adi', 'eposta', 'e_posta', 'email', 'mail', 'username'],
        'password' => ['sifre', 'parola', 'password'],
        'parent_name' => ['veli_adi', 'veli', 'parent_name'],
        'parent_phone' => ['veli_telefon', 'veli_tel', 'telefon_veli', 'parent_phone'],
        'branch' => ['brans', 'bolum', 'branch'],
        'phone' => ['telefon', 'tel', 'phone'],
    ];

    public const TEMPLATES = [
        'student' => [['ad_soyad', 'ogrenci_no', 'sinif', 'kullanici_adi', 'sifre', 'veli_adi', 'veli_telefon'], ['Ali Yılmaz', '1001', '5-A', '', '', 'Ayşe Yılmaz', '0532 111 22 33']],
        'teacher' => [['ad_soyad', 'eposta', 'brans', 'telefon', 'sifre'], ['Mehmet Demir', 'mehmet@okul.test', 'Bilişim', '0533 444 55 66', '']],
    ];

    /** @return list<array<string,string>> baslik eslemesi yapilmis satirlar (her biri 'line' icerir) */
    public function parse(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $raw = match ($ext) {
            'xlsx' => XlsxReader::read($file->getRealPath()),
            'csv', 'txt' => $this->readCsv($file->getRealPath()),
            default => throw new RuntimeException('Yalnızca .xlsx veya .csv yükleyin.'),
        };
        $raw = array_values(array_filter($raw, fn ($r) => array_filter($r, fn ($c) => trim((string) $c) !== '')));
        if (count($raw) < 2) {
            throw new RuntimeException('Dosyada başlık satırı ve en az bir veri satırı olmalı.');
        }
        if (count($raw) - 1 > self::MAX_ROWS) {
            throw new RuntimeException('En fazla '.self::MAX_ROWS.' satır yüklenebilir.');
        }

        $map = [];
        foreach ($raw[0] as $i => $h) {
            $key = $this->slug((string) $h);
            foreach (self::ALIASES as $field => $names) {
                if (in_array($key, $names, true)) {
                    $map[$i] = $field;
                }
            }
        }
        $rows = [];
        foreach (array_slice($raw, 1) as $n => $r) {
            $row = ['line' => (string) ($n + 2)];
            foreach ($map as $i => $field) {
                $row[$field] = trim((string) ($r[$i] ?? ''));
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function readCsv(string $path): array
    {
        $content = file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1254'); // Turkce Excel CSV
        }
        $first = strtok($content, "\n");
        $delim = collect([';', ',', "\t"])->sortByDesc(fn ($d) => substr_count($first, $d))->first();

        $rows = [];
        $h = fopen('php://memory', 'r+');
        fwrite($h, $content);
        rewind($h);
        while (($r = fgetcsv($h, 0, $delim)) !== false) {
            $rows[] = $r;
        }
        fclose($h);

        return $rows;
    }

    private function slug(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = strtr($s, ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'i̇' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u']);

        return trim(preg_replace('/[^a-z0-9]+/', '_', $s), '_');
    }

    public function password(?string $given): string
    {
        if ($given !== null && mb_strlen($given) >= 6) {
            return $given;
        }
        $out = '';
        for ($i = 0; $i < 8; $i++) {
            $out .= self::PW_ALPHABET[random_int(0, strlen(self::PW_ALPHABET) - 1)];
        }

        return $out;
    }

    private function academicYear(): string
    {
        $y = (int) date('Y');

        return (int) date('n') >= 9 ? "$y-".($y + 1) : ($y - 1)."-$y";
    }

    /** "5-A", "5/a", "5 A", "5A" -> [5, 'A'] */
    public function parseClass(string $label): ?array
    {
        if (preg_match('/^(\d{1,2})\s*[-\/ ]?\s*([A-Za-zÇĞİÖŞÜçğıöşü]{1,5})$/u', trim($label), $m)) {
            return [$m[1], mb_strtoupper($m[2], 'UTF-8')];
        }

        return null;
    }

    private function resolveClass(string $label, bool $create, array &$cache): ?SchoolClass
    {
        $p = $this->parseClass($label);
        if (! $p) {
            return null;
        }
        $key = implode('-', $p);
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        $class = SchoolClass::where(['name' => $p[0], 'section' => $p[1]])->orderByDesc('academic_year')->first();
        if (! $class && $create) {
            $class = SchoolClass::create(['name' => $p[0], 'section' => $p[1], 'grade_level' => min(12, max(1, (int) $p[0])), 'academic_year' => $this->academicYear()]);
        }

        return $cache[$key] = $class;
    }

    /** @return array{created: list<array>, errors: list<array{line:string,message:string}>} */
    public function importStudents(array $rows, bool $createClasses): array
    {
        $created = [];
        $errors = [];
        $seenNo = [];
        $seenUser = [];
        $classCache = [];
        $studentRole = Role::where('slug', Role::STUDENT)->value('id');

        foreach ($rows as $row) {
            $line = $row['line'];
            $no = $row['student_no'] ?? '';
            $username = strtolower($row['username'] ?? '') ?: strtolower($no).'@school.local';
            if ($username !== '' && ! str_contains($username, '@')) {
                $username .= '@school.local';
            }

            $v = Validator::make(['name' => $row['name'] ?? '', 'no' => $no, 'username' => $username, 'phone' => $row['parent_phone'] ?? ''], [
                'name' => ['required', 'string', 'max:255'], 'no' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'],
                'username' => ['required', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:20'],
            ], ['no.regex' => 'öğrenci numarası yalnızca harf, rakam . _ - içerebilir']);
            if ($v->fails()) {
                $errors[] = ['line' => $line, 'message' => $v->errors()->first()];
                continue;
            }
            if (isset($seenNo[$no]) || \App\Models\Student::where('student_no', $no)->exists()) {
                $errors[] = ['line' => $line, 'message' => "öğrenci no $no zaten kayıtlı"];
                continue;
            }
            if (isset($seenUser[$username]) || User::where('email', $username)->exists()) {
                $errors[] = ['line' => $line, 'message' => "kullanıcı adı $username zaten kayıtlı"];
                continue;
            }
            $class = null;
            if (($row['class'] ?? '') !== '') {
                $class = $this->resolveClass($row['class'], $createClasses, $classCache);
                if (! $class) {
                    $errors[] = ['line' => $line, 'message' => 'sınıf bulunamadı: '.$row['class']];
                    continue;
                }
            }

            $plain = $this->password($row['password'] ?? null);
            DB::transaction(function () use ($row, $no, $username, $plain, $class, $studentRole) {
                $u = User::create(['role_id' => $studentRole, 'name' => $row['name'], 'email' => $username, 'password' => $plain]);
                $u->student()->create(['student_no' => $no, 'school_class_id' => $class?->id, 'parent_name' => $row['parent_name'] ?? null, 'parent_phone' => $row['parent_phone'] ?? null]);
            });
            $seenNo[$no] = $seenUser[$username] = true;
            $created[] = ['name' => $row['name'], 'no' => $no, 'username' => $username, 'password' => $plain, 'class' => $class?->label ?? '—'];
        }

        return compact('created', 'errors');
    }

    public function importTeachers(array $rows): array
    {
        $created = [];
        $errors = [];
        $seen = [];
        $role = Role::where('slug', Role::TEACHER)->value('id');

        foreach ($rows as $row) {
            $email = strtolower($row['username'] ?? '');
            $v = Validator::make(['name' => $row['name'] ?? '', 'email' => $email], ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255']]);
            if ($v->fails()) {
                $errors[] = ['line' => $row['line'], 'message' => $v->errors()->first()];
                continue;
            }
            if (isset($seen[$email]) || User::where('email', $email)->exists()) {
                $errors[] = ['line' => $row['line'], 'message' => "$email zaten kayıtlı"];
                continue;
            }
            $plain = $this->password($row['password'] ?? null);
            DB::transaction(function () use ($row, $email, $plain, $role) {
                $u = User::create(['role_id' => $role, 'name' => $row['name'], 'email' => $email, 'password' => $plain]);
                $u->teacher()->create(['branch' => $row['branch'] ?? null, 'phone' => $row['phone'] ?? null]);
            });
            $seen[$email] = true;
            $created[] = ['name' => $row['name'], 'no' => $row['branch'] ?? '', 'username' => $email, 'password' => $plain, 'class' => '—'];
        }

        return compact('created', 'errors');
    }
}
