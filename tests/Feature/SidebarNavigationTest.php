<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function sidebar(array $permissions, string $route): string
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
            $user->givePermissionTo($permission);
        }
        $this->actingAs($user);
        request()->setRouteResolver(fn () => app('router')->getRoutes()->getByName($route));

        return Blade::render('<x-app.sidebar />');
    }

    public function test_report_only_users_see_reports_without_unrelated_groups(): void
    {
        $html = $this->sidebar(['reports.view'], 'personnel-recap.index');
        $this->assertStringContainsString('sidebar-trigger-reports', $html);
        $this->assertStringContainsString(route('spt-recap.index'), $html);
        $this->assertStringContainsString(route('personnel-recap.index'), $html);
        foreach (['spt', 'personnel', 'administration', 'access'] as $group) {
            $this->assertStringNotContainsString('id="sidebar-trigger-'.$group.'"', $html);
        }
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    public function test_edit_pages_mark_their_parent_navigation_and_group_active(): void
    {
        $html = $this->sidebar(['users.view', 'roles.view', 'reports.view'], 'users.edit');
        $this->assertMatchesRegularExpression('/id="sidebar-trigger-access"[^>]*is-parent-active/s', $html);
        $this->assertMatchesRegularExpression('/href="'.preg_quote(route('users.index'), '/').'"[^>]*aria-current="page"/s', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
        $this->assertStringContainsString('sidebar-navigation-users.edit', $html);
    }

    public function test_administration_and_security_menus_are_grouped_once(): void
    {
        $html = $this->sidebar(['settings.view', 'audit-logs.view', 'users.security.manage', 'roles.view'], 'letter-templates.edit');
        $this->assertSame(1, substr_count($html, 'id="sidebar-trigger-administration"'));
        $this->assertSame(1, substr_count($html, 'id="sidebar-trigger-access"'));
        foreach (['letter-templates.index', 'administration-profiles.index', 'audit-logs.index', 'security.account-recovery', 'roles.index'] as $route) {
            $this->assertStringContainsString(route($route), $html);
        }
        $this->assertStringNotContainsString(route('users.index'), $html);
    }
}
