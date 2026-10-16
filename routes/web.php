<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseSlideController;
use App\Http\Controllers\Student;
use App\Http\Controllers\XpGrantController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\LiveSessionController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\StudentMessageController;
use App\Http\Controllers\SupportRequestController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\BulkImportController;
use App\Http\Controllers\LoginCardController;
use App\Http\Controllers\QrLoginController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\GradeController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

// Veli: imzali, sureli baglanti (giris gerekmez).
Route::get('/veli/rapor/{student}', [ReportController::class, 'parent'])->middleware(['signed', 'throttle:60,1'])->name('parent.report');

Route::get('/demo', [SupportRequestController::class, 'demoForm'])->name('demo.form');
Route::post('/demo', [SupportRequestController::class, 'demoStore'])->middleware('throttle:5,10')->name('demo.store');
Route::view('/gizlilik', 'legal.privacy')->name('legal.privacy');
Route::view('/cerez', 'legal.cookies')->name('legal.cookies');
Route::view('/kosullar', 'legal.terms')->name('legal.terms');
Route::redirect('/kvkk', '/gizlilik');

Route::get('/giris-karti/{token}', [QrLoginController::class, 'showCard'])->middleware('throttle:30,1')->name('card.show');
Route::post('/giris-karti/{token}', [QrLoginController::class, 'useCard'])->middleware('throttle:15,1')->name('card.use');

Route::middleware('guest')->group(function () {
    Route::post('/qr/uret', [QrLoginController::class, 'start'])->middleware('throttle:20,1')->name('qr.start');
    Route::get('/qr/durum/{token}', [QrLoginController::class, 'status'])->middleware('throttle:120,1')->name('qr.status');
    Route::post('/qr/tamamla/{token}', [QrLoginController::class, 'complete'])->middleware('throttle:20,1')->name('qr.complete');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('login.attempt');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/etkinlikler', [GameController::class, 'index'])->name('games.index');
    Route::get('/oyun/{slug}', [GameController::class, 'play'])->name('games.play');
    Route::get('/runner-grant/{slug}', [GameController::class, 'grant'])->name('games.grant');
    Route::get('/connect-the-dots-runner/levels', fn () => response()->file(public_path('runner-assets/connect-the-dots-runner/levels.json'), ['Content-Type' => 'application/json']))->name('games.dots-levels');
    Route::post('/oyun/{slug}/sonuc', [GameController::class, 'result'])->middleware(['role:student', 'throttle:60,1'])->name('games.result');
    Route::post('/oyun/{slug}/dene', [GameController::class, 'check'])->middleware(['role:admin,teacher', 'throttle:120,1'])->name('games.check');
    Route::post('/etkinlikler/ogretmene-ata', [GameController::class, 'assignTeacher'])->middleware('role:admin')->name('games.assign');
    Route::get('/bildirimlerim', [NotificationController::class, 'inbox'])->name('notifications.inbox');
    Route::post('/bildirimlerim/hepsi', [NotificationController::class, 'readAll'])->name('notifications.readall');
    Route::post('/bildirimlerim/{notification}/oku', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/push/abone', [NotificationController::class, 'subscribe'])->middleware('throttle:20,1')->name('push.subscribe');
    Route::delete('/push/abone', [NotificationController::class, 'unsubscribe'])->name('push.unsubscribe');
    Route::get('/duyurular', [AnnouncementController::class, 'feed'])->name('announcements.feed');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:admin,teacher')->group(function () {
        Route::resource('classes', SchoolClassController::class)->except('show')->parameters(['classes' => 'class']);
        Route::resource('students', StudentController::class)->except('show');

        Route::post('courses/import', [CourseController::class, 'import'])->name('courses.import');
        Route::resource('courses', CourseController::class)->except('show');
        Route::get('courses/{course}/onizle', [CourseController::class, 'preview'])->name('courses.preview');
        Route::get('courses/{course}/indir', [CourseController::class, 'export'])->name('courses.export');
        Route::post('courses/{course}/siniflar', [CourseController::class, 'assignClasses'])->name('courses.classes');
        Route::post('courses/{course}/slides', [CourseSlideController::class, 'store'])->name('slides.store');
        Route::post('courses/{course}/slides/sirala', [CourseSlideController::class, 'reorder'])->name('slides.reorder');
        Route::put('courses/{course}/slides/{slide}', [CourseSlideController::class, 'update'])->name('slides.update');
        Route::delete('courses/{course}/slides/{slide}', [CourseSlideController::class, 'destroy'])->name('slides.destroy');

        Route::post('xp-hediyesi', [XpGrantController::class, 'store'])->middleware('throttle:10,1')->name('xp.grant');
        Route::resource('quizzes', QuizController::class)->except('show');
        Route::get('canli', [LiveSessionController::class, 'index'])->name('live.index');
        Route::post('canli', [LiveSessionController::class, 'store'])->name('live.store');
        Route::get('canli/{session}', [LiveSessionController::class, 'show'])->name('live.show');
        Route::get('canli/{session}/durum', [LiveSessionController::class, 'status'])->middleware('throttle:240,1')->name('live.status');
        Route::post('canli/{session}/baslat', [LiveSessionController::class, 'start'])->name('live.start');
        Route::post('canli/{session}/sonraki', [LiveSessionController::class, 'next'])->name('live.next');
        Route::post('canli/{session}/kilit', [LiveSessionController::class, 'lock'])->name('live.lock');
        Route::post('canli/{session}/bitir', [LiveSessionController::class, 'finish'])->name('live.finish');
        Route::get('canli/{session}/rapor', [LiveSessionController::class, 'report'])->name('live.report');
        Route::delete('canli/{session}', [LiveSessionController::class, 'destroy'])->name('live.destroy');
        Route::get('takip', [TrackingController::class, 'index'])->name('tracking.index');
        Route::get('takip/veri', [TrackingController::class, 'data'])->middleware('throttle:120,1')->name('tracking.data');
        Route::get('takip/ogrenci/{student}', [TrackingController::class, 'student'])->name('tracking.student');
        Route::post('takip/ogrenci/{student}/cikis', [TrackingController::class, 'forceLogoutStudent'])->name('tracking.logout.student');
        Route::post('takip/sinif/{class}/cikis', [TrackingController::class, 'forceLogoutClass'])->name('tracking.logout.class');
        Route::get('analitik', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('analitik/indir', [AnalyticsController::class, 'export'])->middleware('throttle:20,1')->name('analytics.export');
        Route::get('rapor/veli-listesi', [ReportController::class, 'parentList'])->name('reports.parentlist');
        Route::get('rapor/{student}', [ReportController::class, 'show'])->name('reports.show');
        Route::post('rapor/{student}/veli', [ReportController::class, 'sendToParent'])->middleware('throttle:30,1')->name('reports.parent');
        Route::get('bildirim-gonder', [NotificationController::class, 'compose'])->name('notifications.compose');
        Route::post('bildirim-gonder', [NotificationController::class, 'send'])->middleware('throttle:20,1')->name('notifications.send');
        Route::get('talepler', [SupportRequestController::class, 'index'])->name('support.index');
        Route::post('talepler', [SupportRequestController::class, 'store'])->name('support.store');
        Route::get('talepler/{supportRequest}', [SupportRequestController::class, 'show'])->name('support.show');
        Route::post('talepler/{supportRequest}/yanit', [SupportRequestController::class, 'reply'])->middleware('throttle:30,1')->name('support.reply');
        Route::post('talepler/{supportRequest}/kapat', [SupportRequestController::class, 'close'])->name('support.close');
        Route::post('talepler/{supportRequest}/arsiv', [SupportRequestController::class, 'archive'])->name('support.archive');
        Route::delete('talepler/{supportRequest}', [SupportRequestController::class, 'destroy'])->name('support.destroy');

        Route::get('ogrenci-mesajlari', [StudentMessageController::class, 'staffIndex'])->name('messages.staff');
        Route::post('ogrenci-mesajlari/{message}/cevapla', [StudentMessageController::class, 'staffReply'])->middleware('throttle:30,1')->name('messages.reply');
        Route::delete('ogrenci-mesajlari/{message}', [StudentMessageController::class, 'staffDestroy'])->name('messages.destroy');
        Route::delete('ogrenci-mesajlari-cevaplananlar', [StudentMessageController::class, 'staffClearAnswered'])->name('messages.clear');

        Route::get('yoklama', [AttendanceController::class, 'sheet'])->name('attendance.sheet');
        Route::post('yoklama', [AttendanceController::class, 'save'])->middleware('throttle:60,1')->name('attendance.save');
        Route::get('yoklama/rapor', [AttendanceController::class, 'report'])->name('attendance.report');
        Route::get('yoklama/rapor/indir', [AttendanceController::class, 'export'])->middleware('throttle:20,1')->name('attendance.export');
        Route::get('notlar/giris', [GradeController::class, 'sheet'])->name('grades.sheet');
        Route::post('notlar', [GradeController::class, 'store'])->middleware('throttle:60,1')->name('grades.store');
        Route::get('notlar', [GradeController::class, 'index'])->name('grades.index');
        Route::get('notlar/{grade}/duzenle', [GradeController::class, 'edit'])->name('grades.edit');
        Route::put('notlar/{grade}', [GradeController::class, 'update'])->name('grades.update');
        Route::delete('notlar/{grade}', [GradeController::class, 'destroy'])->name('grades.destroy');
        Route::get('giris-kartlari', [LoginCardController::class, 'index'])->name('cards.index');
        Route::post('giris-kartlari/{class}', [LoginCardController::class, 'issue'])->middleware('throttle:10,1')->name('cards.issue');
        Route::get('qr/onay/{token}', [QrLoginController::class, 'approveForm'])->name('qr.approve');
        Route::post('qr/onay/{token}', [QrLoginController::class, 'approve'])->middleware('throttle:30,1')->name('qr.approve.do');

        Route::middleware('role:admin')->group(function () {
            Route::resource('kullanicilar', UserManagementController::class)->except('show')->parameters(['kullanicilar' => 'user'])->names('users');
            Route::post('kullanicilar/{user}/sifre-sifirla', [UserManagementController::class, 'resetPassword'])->name('users.password');
            Route::get('kullanicilar/{user}/siniflar', [UserManagementController::class, 'classes'])->name('users.classes');
            Route::post('kullanicilar/{user}/siniflar', [UserManagementController::class, 'assignClasses'])->name('users.classes.save');
            Route::post('kullanicilar/{user}/siniflar/kademe', [UserManagementController::class, 'assignByGrade'])->name('users.classes.grade');
            Route::post('ogrenciler/secili-sil', [UserManagementController::class, 'destroySelectedStudents'])->name('students.destroy.selected');
            Route::get('toplu-yukleme', [BulkImportController::class, 'index'])->name('import.index');
            Route::get('toplu-yukleme/{type}/sablon', [BulkImportController::class, 'template'])->name('import.template');
            Route::post('toplu-yukleme/{type}', [BulkImportController::class, 'upload'])->middleware('throttle:10,1')->name('import.upload');
            Route::resource('announcements', AnnouncementController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
            Route::get('duyuru-yonetimi', [AnnouncementController::class, 'index'])->name('announcements.index');
            Route::get('sistem', [SystemController::class, 'index'])->name('system.index');
            Route::post('sistem/bakim', [SystemController::class, 'toggleMaintenance'])->name('system.maintenance');
            Route::post('sistem/ogrenci-sifirla', [SystemController::class, 'resetStudentData'])->middleware('throttle:3,1')->name('system.reset');
        });
        Route::resource('assignments', AssignmentController::class);
    });
});

Route::middleware(['auth', 'role:student'])->prefix('ogrenci')->name('student.')->group(function () {
    Route::get('derslerim', [Student\CourseController::class, 'index'])->name('courses');
    Route::get('derslerim/{course}', [Student\CourseController::class, 'show'])->name('courses.show');
    Route::post('derslerim/{course}/ilerleme', [Student\CourseController::class, 'saveProgress'])->middleware('throttle:120,1')->name('courses.progress');
    Route::post('derslerim/{course}/tamamla', [Student\CourseController::class, 'complete'])->name('courses.complete');

    Route::get('odevlerim', [Student\AssignmentController::class, 'index'])->name('assignments');
    Route::get('odevlerim/{assignment}/ac', [Student\AssignmentController::class, 'open'])->name('assignments.open');
    Route::post('odevlerim/{assignment}/tamamla', [Student\AssignmentController::class, 'complete'])->name('assignments.complete');

    Route::get('canli', [Student\LiveController::class, 'joinForm'])->name('live');
    Route::post('canli', [Student\LiveController::class, 'join'])->middleware('throttle:30,1')->name('live.join');
    Route::get('canli/{session}/katil', [Student\LiveController::class, 'joinById'])->name('live.joinid');
    Route::get('canli/{session}', [Student\LiveController::class, 'play'])->name('live.play');
    Route::get('canli/{session}/durum', [Student\LiveController::class, 'status'])->middleware('throttle:240,1')->name('live.status');
    Route::post('canli/{session}/cevap', [Student\LiveController::class, 'answer'])->middleware('throttle:60,1')->name('live.answer');
    Route::post('canli/{session}/yazi', [Student\LiveController::class, 'typing'])->middleware('throttle:240,1')->name('live.typing');
    Route::get('mesajlar', [StudentMessageController::class, 'studentIndex'])->name('messages');
    Route::post('mesajlar', [StudentMessageController::class, 'studentSend'])->middleware('throttle:10,1')->name('messages.send');
    Route::get('devamsizlik', [AttendanceController::class, 'mine'])->name('attendance');
    Route::get('notlarim', [GradeController::class, 'mine'])->name('grades');
    Route::get('karnem', [ReportController::class, 'mine'])->name('report');
    Route::post('sure/ping', [Student\PortalController::class, 'pingTime'])->middleware('throttle:12,1')->name('ping');
    Route::get('avatarlarim', [Student\PortalController::class, 'avatars'])->name('avatars');
    Route::post('avatarlarim/{avatar}/satinal', [Student\PortalController::class, 'buyAvatar'])->name('avatars.buy');
    Route::post('avatarlarim/{avatar}/sec', [Student\PortalController::class, 'equipAvatar'])->name('avatars.equip');
    Route::get('rozetlerim', [Student\PortalController::class, 'badges'])->name('badges');
    Route::get('siralama', [Student\PortalController::class, 'ranking'])->name('ranking');
    Route::get('arkadaslarim', [Student\PortalController::class, 'friends'])->name('friends');
    Route::get('sinif-panosu', [Student\PortalController::class, 'classBoard'])->name('board');
    Route::post('sinif-panosu/paylas', [Student\PortalController::class, 'storeBoardPost'])->name('board.post');
});
