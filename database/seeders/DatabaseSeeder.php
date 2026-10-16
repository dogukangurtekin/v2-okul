<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect(['admin' => 'Yönetici', 'teacher' => 'Öğretmen', 'student' => 'Öğrenci'])
            ->map(fn ($name, $slug) => Role::firstOrCreate(['slug' => $slug], ['name' => $name]));

        $this->createFirstAdmin($roles['admin']);
        $this->call(GamificationSeeder::class);
    }

    /**
     * Yerelde/testte bilinen geliştirme şifresi (Admin1234!) kullanılır. ÜRETİMDE ASLA: ADMIN_PASSWORD verilmişse o,
     * verilmemişse rastgele bir şifre üretilip yalnızca bir kez konsola yazılır. Yönetici zaten varsa ikinci kez oluşturulmaz.
     */
    private function createFirstAdmin(Role $adminRole): void
    {
        $production = app()->isProduction();
        if ($production && User::where('role_id', $adminRole->id)->exists()) {
            return;
        }

        $email = config('school.admin_email');
        $password = config('school.admin_password') ?: ($production ? Str::password(16, symbols: false) : 'Admin1234!');

        $user = User::firstOrCreate(['email' => $email], [
            'role_id' => $adminRole->id, 'name' => 'Sistem Yöneticisi', 'password' => $password,
        ]);

        if ($production && $user->wasRecentlyCreated && ! config('school.admin_password')) {
            $this->command?->warn("İlk yönetici oluşturuldu: {$email} / {$password}  (bu şifre yalnızca şimdi gösterilir; girişten sonra değiştirin)");
        }
    }
}
