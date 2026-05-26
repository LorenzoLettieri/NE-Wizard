<?php

namespace Tests\Feature;

use App\Livewire\DecommissioningStats;
use App\Models\Company;
use App\Models\Decommissioning;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('pdo_sqlite')]
class DecommissioningStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_decommissioning_stats_group_counts_and_payment_totals_by_deco_designer(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $firstDesigner = User::factory()->create(['name' => 'Anna Deco']);
        $secondDesigner = User::factory()->create(['name' => 'Luca Zero']);
        $firstDesigner->assignRole('Deco');
        $secondDesigner->assignRole('Deco');

        $this->createDecommissioning([
            'progettista_id' => $firstDesigner->id,
            'status' => 'In Lavorazione',
            'created_at' => Carbon::parse('2026-04-05 10:00:00'),
        ]);
        $this->createDecommissioning([
            'progettista_id' => $firstDesigner->id,
            'status' => 'Sospeso',
            'created_at' => Carbon::parse('2026-04-06 10:00:00'),
        ]);
        $this->createDecommissioning([
            'progettista_id' => $firstDesigner->id,
            'status' => 'Fine Lavori',
            'tot_prog' => 125.50,
            'pagata_prog' => true,
            'created_at' => Carbon::parse('2026-04-07 10:00:00'),
        ]);
        $this->createDecommissioning([
            'progettista_id' => $firstDesigner->id,
            'status' => 'Fine Lavori',
            'tot_prog' => 300,
            'pagata_prog' => false,
            'created_at' => Carbon::parse('2026-04-08 10:00:00'),
        ]);
        $this->createDecommissioning([
            'progettista_id' => $firstDesigner->id,
            'status' => 'Fine Lavori',
            'tot_prog' => 999,
            'pagata_prog' => true,
            'created_at' => Carbon::parse('2026-03-31 10:00:00'),
        ]);
        $this->createDecommissioning([
            'status' => 'Fine Lavori',
            'tot_prog' => 500,
            'pagata_prog' => true,
            'created_at' => Carbon::parse('2026-04-09 10:00:00'),
        ]);

        $this->actingAs($admin);

        Livewire::test(DecommissioningStats::class)
            ->set('startDate', '2026-04-01')
            ->set('endDate', '2026-04-30')
            ->assertViewHas('rows', function (array $rows) {
                $rows = collect($rows)->keyBy('designer_name');

                return $rows->has('Anna Deco')
                    && $rows->has('Luca Zero')
                    && $rows['Anna Deco']['in_progress_count'] === 1
                    && $rows['Anna Deco']['suspended_count'] === 1
                    && $rows['Anna Deco']['completed_count'] === 2
                    && $rows['Anna Deco']['paid_prog_total'] === 125.50
                    && $rows['Anna Deco']['unpaid_prog_total'] === 300.0
                    && $rows['Luca Zero']['in_progress_count'] === 0
                    && $rows['Luca Zero']['suspended_count'] === 0
                    && $rows['Luca Zero']['completed_count'] === 0
                    && $rows['Luca Zero']['paid_prog_total'] === 0.0
                    && $rows['Luca Zero']['unpaid_prog_total'] === 0.0;
            });
    }

    public function test_decommissioning_stats_filter_by_designer_and_company(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $matchingDesigner = User::factory()->create(['name' => 'Designer Visibile']);
        $otherDesigner = User::factory()->create(['name' => 'Designer Nascosto']);
        $matchingDesigner->assignRole('Deco');
        $otherDesigner->assignRole('Deco');

        $matchingCompany = Company::create(['name' => 'Impresa Match']);
        $otherCompany = Company::create(['name' => 'Impresa Other']);

        $this->createDecommissioning([
            'company_id' => $matchingCompany->id,
            'progettista_id' => $matchingDesigner->id,
            'status' => 'Fine Lavori',
            'tot_prog' => 200,
            'pagata_prog' => true,
            'created_at' => Carbon::parse('2026-04-10 10:00:00'),
        ]);
        $this->createDecommissioning([
            'company_id' => $otherCompany->id,
            'progettista_id' => $matchingDesigner->id,
            'status' => 'Fine Lavori',
            'tot_prog' => 400,
            'pagata_prog' => true,
            'created_at' => Carbon::parse('2026-04-10 10:00:00'),
        ]);
        $this->createDecommissioning([
            'company_id' => $matchingCompany->id,
            'progettista_id' => $otherDesigner->id,
            'status' => 'Fine Lavori',
            'tot_prog' => 800,
            'pagata_prog' => true,
            'created_at' => Carbon::parse('2026-04-10 10:00:00'),
        ]);

        $this->actingAs($admin);

        Livewire::test(DecommissioningStats::class)
            ->set('startDate', '2026-04-01')
            ->set('endDate', '2026-04-30')
            ->set('designerId', (string) $matchingDesigner->id)
            ->set('companyId', (string) $matchingCompany->id)
            ->assertViewHas('rows', function (array $rows) {
                return count($rows) === 1
                    && $rows[0]['designer_name'] === 'Designer Visibile'
                    && $rows[0]['completed_count'] === 1
                    && $rows[0]['paid_prog_total'] === 200.0;
            });
    }

    public function test_report_routes_are_authorized_by_role(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $deco = User::factory()->create();
        $deco->assignRole('Deco');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($admin)
            ->get(route('reports.decommissioning'))
            ->assertOk();

        $this->actingAs($supervisor)
            ->get(route('reports.operators'))
            ->assertOk();

        $this->actingAs($deco)
            ->get(route('reports.decommissioning'))
            ->assertForbidden();

        $this->actingAs($operator)
            ->get(route('reports.decommissioning'))
            ->assertForbidden();
    }

    public function test_navbar_exposes_report_links_by_role(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create(['name' => 'Admin User']);
        $admin->assignRole('admin');

        $supervisor = User::factory()->create(['name' => 'Supervisor User']);
        $supervisor->assignRole('supervisor');

        $this->actingAs($admin)
            ->get(route('welcome'))
            ->assertSee('Report Operatori')
            ->assertSee('Report Deco');

        $this->actingAs($supervisor)
            ->get(route('welcome'))
            ->assertSee('Report Operatori')
            ->assertDontSee('Report Deco');
    }

    private function createDecommissioning(array $attributes): Decommissioning
    {
        $decommissioning = new Decommissioning();
        $decommissioning->forceFill($attributes);
        $decommissioning->save();

        return $decommissioning;
    }
}
