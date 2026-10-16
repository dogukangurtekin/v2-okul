<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function asProduction(): void
    {
        $this->app['env'] = 'production';
    }

    public function test_dev_environment_keeps_known_admin_password(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertTrue(Hash::check('Admin1234!', User::where('email', 'admin@school.local')->value('password')));
    }

    public function test_production_never_uses_the_default_password(): void
    {
        $this->asProduction();
        config(['school.admin_password' => null, 'school.admin_email' => 'yonetici@okul.test']);
        app(DatabaseSeeder::class)->run();          // db:seed üretimde onay sorar; seeder'ı doğrudan çalıştırıyoruz

        $u = User::where('email', 'yonetici@okul.test')->first();
        $this->assertNotNull($u);
        $this->assertFalse(Hash::check('Admin1234!', $u->password), 'Üretimde varsayılan şifre kullanıldı');
        $this->assertNull(User::where('email', 'admin@school.local')->first());
    }

    public function test_production_uses_admin_password_from_config_and_seeding_twice_does_not_add_another_admin(): void
    {
        $this->asProduction();
        config(['school.admin_password' => 'Cok-Gizli-Sifre-91', 'school.admin_email' => 'yonetici@okul.test']);
        app(DatabaseSeeder::class)->run();
        $this->assertTrue(Hash::check('Cok-Gizli-Sifre-91', User::where('email', 'yonetici@okul.test')->value('password')));

        // Yönetici e-postasını değiştirip tekrar seed çalıştırılırsa ikinci bir yönetici üretilmez
        config(['school.admin_email' => 'baska@okul.test']);
        app(DatabaseSeeder::class)->run();
        $adminRole = Role::where('slug', 'admin')->value('id');
        $this->assertSame(1, User::where('role_id', $adminRole)->count());
    }

    public function test_security_headers_are_present_and_hsts_only_on_https_production(): void
    {
        $r = $this->get('/login');
        $r->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString('camera=()', $r->headers->get('Permissions-Policy'));
        $r->assertHeaderMissing('Strict-Transport-Security');                       // yerelde http: HSTS yok

        $this->asProduction();
        $this->get('https://okul.test/login')->assertHeader('Strict-Transport-Security');
        $this->get('http://okul.test/login')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_deploy_script_refuses_debug_mode_and_missing_env(): void
    {
        $script = file_get_contents(base_path('scripts/deploy.sh'));
        $this->assertStringContainsString('APP_DEBUG=(false|0)', $script);
        $this->assertStringContainsString('--no-dev', $script);
        $this->assertStringContainsString('trap', $script);                         // hata olsa bile site bakımda kalmaz
        $this->assertFileExists(base_path('.env.production.example'));
        $env = file_get_contents(base_path('.env.production.example'));
        $this->assertStringContainsString('APP_DEBUG=false', $env);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE=true', $env);
        $this->assertStringNotContainsString('Admin1234', $env);
    }

    public function test_gitignore_protects_secrets(): void
    {
        $g = file_get_contents(base_path('.gitignore'));
        $lines = array_map('trim', explode("\n", $g));
        foreach (['.env', '/vendor', '/node_modules', '/tests/dom/.cache/*'] as $p) {
            $this->assertContains($p, $lines, "$p .gitignore'da yok");
        }
    }

    public function test_login_page_is_not_cacheable_and_419_redirects_gracefully(): void
    {
        $this->get('/login')->assertOk()->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');

        \Illuminate\Support\Facades\Route::middleware('web')->post('/yaz-419', fn () => throw new \Illuminate\Session\TokenMismatchException('x'));
        $this->from('/login')->post('/yaz-419')->assertRedirect('/login')->assertSessionHasErrors('csrf');
    }
}
