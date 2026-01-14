<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Comune;
use App\Models\Central;
use App\Models\Regione;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\PermessoEnte;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PermessoEnteForm extends Component
{
    // Proprietà separate per migliore performance
    public $network;
    public $consegna;
    public $progetto;
    public $regione_id;
    public $comune_id;
    public $central_id;
    public $via;
    public $descrizione;
    public $ap_chiusini;
    public $num_chiusini;
    public $scavo_fino_100m;
    public $quote_aggiuntive;
    public $urgente;
    public $ordinaria;
    public $fine_lavori;
    public $data_fl;
    public $ra;
    public $data_ra;
    public $evaso_dal_dl;
    public $mese_saldo;
    public $al_dl;
    public $a_ne;
    public $delta;
    public $vdc1;
    public $vdc2;
    public $vdc3;
    public $vdc4;
    public $status;
    public $acception_date;
    public $delivery_date;
    public $completion_date;

    public $operator_id = null;

    public $isEdit = false;
    public $isShow = false;
    public $permessoEnteId = null;

    #[Computed]
    public function regioni()
    {
        return cache()->remember('regioni_list', 3600, function () {
            return Regione::pluck('nome', 'id')->toArray();
        });
    }

    #[Computed]
    public function comuni()
    {
        return cache()->remember('comuni_list', 3600, function () {
            return Comune::pluck('name', 'id')->toArray();
        });
    }

    #[Computed]
    public function centrali()
    {
        return cache()->remember('centrali_list', 3600, function () {
            return Central::pluck('central', 'id')->toArray();
        });
    }

    #[Computed]
    public function operators()
    {
        return User::permission('get permessi ente')->select('id', 'name')->get();
    }

    #[On('view-permesso')]
    public function viewPermesso($id)
    {
        $this->isShow = true;
        $this->isEdit = false;
        $this->loadPermesso($id);
    }

    #[On('edit-permesso')]
    public function editPermesso($id)
    {
        $this->isShow = false;
        $this->isEdit = true;
        $this->loadPermesso($id);
    }

    private function loadPermesso($id)
    {
        $permesso = PermessoEnte::findOrFail($id);
        $this->permessoEnteId = $permesso->id;

        // Popola le proprietà
        $this->network = $permesso->network;
        $this->progetto = $permesso->progetto;
        $this->regione_id = $permesso->regione_id;
        $this->comune_id = $permesso->comune_id;
        $this->central_id = $permesso->central_id;
        $this->via = $permesso->via;
        $this->descrizione = $permesso->descrizione;

        // FORMATTA LE DATE CORRETTAMENTE per input type="date"
        $this->consegna = $permesso->consegna ? Carbon::parse($permesso->consegna)->format('Y-m-d') : null;
        $this->data_fl = $permesso->data_fl ? Carbon::parse($permesso->data_fl)->format('Y-m-d') : null;
        $this->data_ra = $permesso->data_ra ? Carbon::parse($permesso->data_ra)->format('Y-m-d') : null;
        $this->evaso_dal_dl = $permesso->evaso_dal_dl ? Carbon::parse($permesso->evaso_dal_dl)->format('Y-m-d') : null;

        // FORMATTA IL MESE per input type="month" (yyyy-MM)
        $this->mese_saldo = $permesso->mese_saldo ? Carbon::parse($permesso->mese_saldo)->format('Y-m') : null;

        // Converti boolean in stringa per le select
        $this->ap_chiusini = $this->boolToString($permesso->ap_chiusini);
        $this->scavo_fino_100m = $this->boolToString($permesso->scavo_fino_100m);
        $this->urgente = $this->boolToString($permesso->urgente);
        $this->ordinaria = $this->boolToString($permesso->ordinaria);
        $this->fine_lavori = $this->boolToString($permesso->fine_lavori);
        $this->ra = $this->boolToString($permesso->ra);

        $this->num_chiusini = $permesso->num_chiusini;
        $this->quote_aggiuntive = $permesso->quote_aggiuntive;
        $this->al_dl = $permesso->al_dl;
        $this->a_ne = $permesso->a_ne;
        $this->delta = $permesso->delta;
        $this->vdc1 = $permesso->vdc1;
        $this->vdc2 = $permesso->vdc2;
        $this->vdc3 = $permesso->vdc3;
        $this->vdc4 = $permesso->vdc4;
        $this->status = $permesso->status;
        $this->acception_date = $permesso->acception_date;
        $this->delivery_date = $permesso->delivery_date;
        $this->completion_date = $permesso->completion_date;

        // Carica gli operatori assegnati
        $this->operator_id = $permesso->users->pluck('id')->toArray();
    }

    private function boolToString($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        return $value ? '1' : '0';
    }

    public function mount(?PermessoEnte $permessoEnte = null)
    {
        if ($permessoEnte && $permessoEnte->exists) {
            $this->loadPermesso($permessoEnte->id);
        }
    }

    protected function rules()
    {
        return [
            'network' => 'nullable|numeric',
            'consegna' => 'nullable|date',
            'progetto' => 'nullable|string|max:255',
            'regione_id' => ['nullable', Rule::exists('regioni', 'id')],
            'comune_id' => ['nullable', Rule::exists('comuni', 'id')],
            'central_id' => ['nullable', Rule::exists('centrals', 'id')],
            'via' => 'nullable|string|max:255',
            'descrizione' => 'nullable|string',
            'ap_chiusini' => 'nullable|in:0,1',
            'num_chiusini' => 'nullable|numeric',
            'scavo_fino_100m' => 'nullable|in:0,1',
            'quote_aggiuntive' => 'nullable|numeric',
            'urgente' => 'nullable|in:0,1',
            'ordinaria' => 'nullable|in:0,1',
            'fine_lavori' => 'nullable|in:0,1',
            'data_fl' => 'nullable|date',
            'ra' => 'nullable|in:0,1',
            'data_ra' => 'nullable|date',
            'evaso_dal_dl' => 'nullable|date',
            'mese_saldo' => 'nullable|date',
            'al_dl' => 'nullable|numeric',
            'a_ne' => 'nullable|numeric',
            'delta' => 'nullable|numeric',
            'vdc1' => 'nullable|numeric',
            'vdc2' => 'nullable|numeric',
            'vdc3' => 'nullable|numeric',
            'vdc4' => 'nullable|numeric',
            'status' => 'nullable|string',
            'acception_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'completion_date' => 'nullable|date',
        ];
    }

    public function save()
    {
        $this->validate();

        $data = [
            'network' => $this->network,
            'consegna' => $this->consegna,
            'progetto' => $this->progetto,
            'regione_id' => $this->regione_id ?: null,
            'comune_id' => $this->comune_id ?: null,
            'central_id' => $this->central_id ?: null,
            'via' => $this->via,
            'descrizione' => $this->descrizione,
            'ap_chiusini' => $this->ap_chiusini === '' ? null : (bool) $this->ap_chiusini,
            'num_chiusini' => $this->num_chiusini,
            'scavo_fino_100m' => $this->scavo_fino_100m === '' ? null : (bool) $this->scavo_fino_100m,
            'quote_aggiuntive' => $this->quote_aggiuntive,
            'urgente' => $this->urgente === '' ? null : (bool) $this->urgente,
            'ordinaria' => $this->ordinaria === '' ? null : (bool) $this->ordinaria,
            'fine_lavori' => $this->fine_lavori === '' ? null : (bool) $this->fine_lavori,
            'data_fl' => $this->data_fl,
            'ra' => $this->ra === '' ? null : (bool) $this->ra,
            'data_ra' => $this->data_ra,
            'evaso_dal_dl' => $this->evaso_dal_dl,
            'mese_saldo' => $this->mese_saldo,
            'al_dl' => $this->al_dl,
            'a_ne' => $this->a_ne,
            'delta' => $this->delta,
            'vdc1' => $this->vdc1,
            'vdc2' => $this->vdc2,
            'vdc3' => $this->vdc3,
            'vdc4' => $this->vdc4,
            'status' => $this->status ?: 'Da Lavorare',
            'acception_date' => $this->acception_date,
            'delivery_date' => $this->delivery_date,
            'completion_date' => $this->completion_date,
        ];

        if ($this->isEdit) {
            $permesso = PermessoEnte::findOrFail($this->permessoEnteId);
            $permesso->update($data);
        } else {
            $permesso = PermessoEnte::create($data);
        }

        $permesso->users()->sync($this->operator_id);

        session()->flash('success', $this->isEdit ? 'Record aggiornato con successo!' : 'Record creato con successo!');
        return redirect()->route('permessi-ente.table');
    }

    public function render()
    {
        return view('livewire.permesso-ente-form');
    }
}