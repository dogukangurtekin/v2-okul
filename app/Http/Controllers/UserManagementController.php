<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Services\BulkImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Yonetici: personel (yonetici/ogretmen) hesaplari ve ogretmene sinif atama. */
class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('role', 'teacher')->whereHas('role', fn ($q) => $q->whereIn('slug', [Role::ADMIN, Role::TEACHER]))
            ->when($request->filled('role'), fn ($q) => $q->whereHas('role', fn ($r) => $r->where('slug', $request->role)))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->q.'%')->orWhere('email', 'like', '%'.$request->q.'%')))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.form', ['user' => new User(['is_active' => true]), 'roleSlug' => old('role', 'teacher')]);
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', 'in:admin,teacher'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'branch' => ['nullable', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function store(Request $request)
    {
        $d = $request->validate($this->rules());
        DB::transaction(function () use ($d, $request) {
            $user = User::create(['role_id' => Role::where('slug', $d['role'])->value('id'), 'name' => $d['name'], 'email' => $d['email'], 'password' => $d['password'], 'is_active' => $request->boolean('is_active')]);
            if ($d['role'] === 'teacher') {
                $user->teacher()->create(['branch' => $d['branch'] ?? null, 'phone' => $d['phone'] ?? null]);
            }
        });

        return redirect()->route('users.index')->with('ok', 'Kullanıcı oluşturuldu.');
    }

    public function edit(User $user)
    {
        $this->staffOnly($user);

        return view('users.form', ['user' => $user->load('teacher', 'role'), 'roleSlug' => $user->role->slug]);
    }

    private function staffOnly(User $user): void
    {
        abort_unless($user->hasRole(Role::ADMIN, Role::TEACHER), 404); // ogrenciler ogrenci ekranindan yonetilir
    }

    /** Sistemde en az bir etkin yonetici kalmalidir. */
    private function lastAdminGuard(User $user, bool $stillAdminAndActive): void
    {
        if ($user->hasRole(Role::ADMIN) && ! $stillAdminAndActive) {
            $others = User::whereHas('role', fn ($q) => $q->where('slug', Role::ADMIN))->where('is_active', true)->where('id', '!=', $user->id)->count();
            abort_if($others === 0, 422, 'Sistemde en az bir etkin yönetici kalmalı.');
        }
    }

    public function update(Request $request, User $user)
    {
        $this->staffOnly($user);
        $d = $request->validate($this->rules($user));
        $active = $request->boolean('is_active');
        abort_if($user->id === $request->user()->id && (! $active || $d['role'] !== 'admin'), 422, 'Kendi hesabınızı pasifleştiremez veya yetkinizi düşüremezsiniz.');
        $this->lastAdminGuard($user, $active && $d['role'] === 'admin');

        DB::transaction(function () use ($d, $user, $active) {
            $user->fill(['role_id' => Role::where('slug', $d['role'])->value('id'), 'name' => $d['name'], 'email' => $d['email'], 'is_active' => $active]);
            if (! empty($d['password'])) {
                $user->password = $d['password'];
                $user->force_logout_at = now(); // sifre degisince acik oturumlar kapanir
            }
            if (! $active) {
                $user->force_logout_at = now();
            }
            $user->save();

            if ($d['role'] === 'teacher') {
                $user->teacher()->updateOrCreate([], ['branch' => $d['branch'] ?? null, 'phone' => $d['phone'] ?? null]);
            } elseif ($user->teacher) {
                $user->teacher->classes()->update(['teacher_id' => null]);
                $user->teacher->delete();
            }
        });

        return redirect()->route('users.index')->with('ok', 'Kullanıcı güncellendi.');
    }

    public function destroy(Request $request, User $user)
    {
        $this->staffOnly($user);
        abort_if($user->id === $request->user()->id, 422, 'Kendi hesabınızı silemezsiniz.');
        $this->lastAdminGuard($user, false);
        $user->delete(); // teachers satiri cascade; siniflarda teacher_id null olur

        return redirect()->route('users.index')->with('ok', 'Kullanıcı silindi.');
    }

    public function resetPassword(Request $request, User $user, BulkImportService $svc)
    {
        $this->staffOnly($user);
        $plain = $svc->password(null);
        $user->forceFill(['password' => $plain, 'force_logout_at' => now()])->save();

        return back()->with('ok', "Yeni geçici şifre ({$user->email}): $plain — bir kez gösterilir, kullanıcıya iletin.");
    }

    // ---- Ogretmene sinif atama ----

    public function classes(User $user)
    {
        $this->staffOnly($user);
        abort_unless($user->teacher, 404, 'Bu kullanıcı öğretmen değil.');

        return view('users.classes', [
            'user' => $user, 'teacher' => $user->teacher,
            'classes' => SchoolClass::with('teacher.user')->withCount('students')->orderBy('grade_level')->orderBy('section')->get(),
        ]);
    }

    public function assignClasses(Request $request, User $user)
    {
        $this->staffOnly($user);
        $teacher = $user->teacher ?? abort(404);
        $d = $request->validate(['classes' => ['nullable', 'array'], 'classes.*' => ['integer', 'exists:school_classes,id']]);
        $ids = $d['classes'] ?? [];

        DB::transaction(function () use ($teacher, $ids) {
            SchoolClass::where('teacher_id', $teacher->id)->whereNotIn('id', $ids)->update(['teacher_id' => null]);
            SchoolClass::whereIn('id', $ids)->update(['teacher_id' => $teacher->id]);
        });

        return back()->with('ok', count($ids).' sınıf atandı.');
    }

    public function assignByGrade(Request $request, User $user)
    {
        $this->staffOnly($user);
        $teacher = $user->teacher ?? abort(404);
        $grade = $request->validate(['grade_level' => ['required', 'integer', 'between:1,12']])['grade_level'];
        $n = SchoolClass::where('grade_level', $grade)->update(['teacher_id' => $teacher->id]);

        return back()->with('ok', "$grade. kademedeki $n sınıf atandı.");
    }

    /** Secili ogrencileri sil (yalniz yonetici, yalniz POST). */
    public function destroySelectedStudents(Request $request)
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:500'], 'ids.*' => ['integer']])['ids'];
        $userIds = Student::whereIn('id', $ids)->pluck('user_id');
        User::whereIn('id', $userIds)->delete();

        return back()->with('ok', $userIds->count().' öğrenci silindi.');
    }
}
