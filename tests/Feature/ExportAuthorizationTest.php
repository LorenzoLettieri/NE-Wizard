<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExportAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_download_works_export(): void
    {
        $this->get(route('exports.works', [
            'date_field' => 'created_at',
            'start' => '2026-04-01',
            'end' => '2026-04-14',
        ]))->assertRedirect('/');
    }

    public function test_operator_cannot_download_admin_only_exports(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator);

        $this->get(route('exports.gbxes', [
            'date_field' => 'created_at',
            'start' => '2026-04-01',
            'end' => '2026-04-14',
        ]))->assertForbidden();

        $this->get(route('exports.permessi-ente', [
            'date_field' => 'created_at',
            'start' => '2026-04-01',
            'end' => '2026-04-14',
        ]))->assertForbidden();

        $this->get(route('exports.decommissionings', [
            'date_field' => 'created_at',
            'start' => '2026-04-01',
            'end' => '2026-04-14',
        ]))->assertForbidden();
    }

    public function test_admin_valid_works_export_request_reaches_validation_and_downloads(): void
    {
        $this->seed(RoleSeeder::class);
        Excel::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('exports.works', [
                'date_field' => 'created_at',
                'start' => '2026-04-01',
                'end' => '2026-04-14',
            ]))
            ->assertOk();

        Excel::assertDownloaded('works_created_at_2026-04-01_2026-04-14.xlsx');
    }

    #[DataProvider('exportRoutes')]
    public function test_invalid_date_field_is_rejected_for_export_routes(string $routeName): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route($routeName, [
                'date_field' => 'not_a_real_field',
                'start' => '2026-04-01',
                'end' => '2026-04-14',
            ]))
            ->assertInvalid(['date_field']);
    }

    #[DataProvider('exportRoutes')]
    public function test_end_before_start_is_rejected_for_export_routes(string $routeName): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route($routeName, [
                'date_field' => 'created_at',
                'start' => '2026-04-14',
                'end' => '2026-04-01',
            ]))
            ->assertInvalid(['end']);
    }

    public function test_admin_can_download_gbx_export(): void
    {
        $this->seed(RoleSeeder::class);
        Excel::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('exports.gbxes', [
                'date_field' => 'created_at',
                'start' => '2026-04-01',
                'end' => '2026-04-14',
            ]))
            ->assertOk();

        Excel::assertDownloaded('gbxes_created_at_2026-04-01_2026-04-14.xlsx');
    }

    public function test_admin_can_download_permessi_ente_export(): void
    {
        $this->seed(RoleSeeder::class);
        Excel::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('exports.permessi-ente', [
                'date_field' => 'created_at',
                'start' => '2026-04-01',
                'end' => '2026-04-14',
            ]))
            ->assertOk();

        Excel::assertDownloaded('permessi_ente_created_at_2026-04-01_2026-04-14.xlsx');
    }

    public function test_admin_can_download_decommissionings_export(): void
    {
        $this->seed(RoleSeeder::class);
        Excel::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('exports.decommissionings', [
                'date_field' => 'created_at',
                'start' => '2026-04-01',
                'end' => '2026-04-14',
            ]))
            ->assertOk();

        Excel::assertDownloaded('decommissionings_created_at_2026-04-01_2026-04-14.xlsx');
    }

    public static function exportRoutes(): array
    {
        return [
            'works' => ['exports.works'],
            'gbxes' => ['exports.gbxes'],
            'permessi ente' => ['exports.permessi-ente'],
            'decommissionings' => ['exports.decommissionings'],
        ];
    }
}
