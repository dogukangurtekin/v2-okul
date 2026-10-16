<?php

return [
    // Yoklama durumlari
    'attendance_statuses' => ['present' => 'Geldi', 'late' => 'Geç', 'excused' => 'İzinli', 'absent' => 'Gelmedi'],

    // Sinav turu => etiket ve agirlik (agirlikli ortalama icin)
    'exam_types' => [
        'quiz' => ['label' => 'Küçük sınav', 'weight' => 1],
        'project' => ['label' => 'Proje', 'weight' => 2],
        'midterm' => ['label' => 'Yazılı', 'weight' => 2],
        'final' => ['label' => 'Dönem sonu', 'weight' => 3],
    ],

    // v1'de not puani XP sayiliyordu (akademik not ile oyun puanini karistirir). v2'de varsayilan KAPALI.
    'grade_xp' => (bool) env('GRADE_XP', false),

    // Yoklama hatirlatma saati (hafta ici, Europe/Istanbul)
    'attendance_reminder_at' => env('ATTENDANCE_REMINDER_AT', '10:00'),

    // Seeder'ın oluşturduğu ilk yönetici. Üretimde ADMIN_PASSWORD verilmezse rastgele şifre üretilip bir kez yazdırılır.
    'admin_email' => env('ADMIN_EMAIL', 'admin@school.local'),
    'admin_password' => env('ADMIN_PASSWORD'),
];
