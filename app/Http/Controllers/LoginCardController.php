<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Services\LoginTokenService;
use App\Support\StaffScope;
use Illuminate\Http\Request;

/** Personel: sinif icin QR'li, tek kullanimlik giris kartlari basar. */
class LoginCardController extends Controller
{
    public function index(Request $request)
    {
        return view('cards.index', ['classes' => StaffScope::classes($request->user())->withCount('students')->get()]);
    }

    public function issue(Request $request, SchoolClass $class, LoginTokenService $tokens)
    {
        abort_unless(StaffScope::classes($request->user())->whereKey($class->id)->exists(), 403);
        $students = $class->students()->with('user')->get()->sortBy(fn ($s) => $s->user->name)->values();

        $cards = $students->filter(fn ($s) => $s->user->is_active)->map(fn ($s) => [
            'name' => $s->user->name, 'no' => $s->student_no, 'login' => $s->user->email,
            'url' => route('card.show', $tokens->issueCard($s->user, $request->user())),
        ]);

        return response()->view('cards.print', ['class' => $class, 'cards' => $cards, 'days' => LoginTokenService::CARD_DAYS])->header('Cache-Control', 'no-store');
    }
}
