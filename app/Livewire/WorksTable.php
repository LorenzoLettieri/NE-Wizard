<?php

namespace App\Livewire;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Work;

class WorksTable extends DataTableComponent
{
    protected $model = Work::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setSearchLive();
    }

    public function columns(): array
    {
        return [
            Column::make("Created at", "created_at")
            ->sortable(),
            Column::make("Company", "company.name")
                ->sortable()->searchable(),
            Column::make("Central", "central.central")
                ->sortable()->searchable(),
            Column::make("Region", "central.region")
                ->sortable()->searchable(),
            Column::make("Status", "status")
                ->sortable()->searchable(),
            Column::make("Description", "description")
                ->sortable(),
            Column::make("Ntw scope", "ntw_scope")
                ->sortable()->searchable(),
            Column::make("Type", "type")
                ->sortable()->searchable(),
            Column::make("Phase", "phase")
                ->sortable()->searchable(),
                Column::make("Acception date", "acception_date")
                ->sortable(),
            Column::make("Completion date", "completion_date")
                ->sortable(),

            Column::make("Delivery date", "delivery_date")
                ->sortable(),
            Column::make("Nroe", "nroe")
                ->sortable(),
                Column::make("Network", "network")
                ->sortable()->searchable(),
            Column::make("Wo number", "wo_number")
                ->sortable()->searchable(),
            Column::make("Unica number", "unica_number")
                ->sortable()->searchable(),
            
            Column::make("Ao cno", "ao_cno")
                ->sortable()->searchable(),

        ];
    }
}
