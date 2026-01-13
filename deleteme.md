                @role('admin|supervisor|permessi ente')
                    <div class="col-12 col-md-4">
                        <a href="{{route('permessi-ente.table')}}" class="text-decoration-none">
                        <div class="border shadow-lg border-black rounded-4 p-4 d-flex justify-content-between hover-lighten">
                            <h3 class="display-5">Permessi Ente</h3>
                        </div>
                        </a>
                    </div>
                @endrole


    public function permessi_ente()
    {
        return $this->belongsToMany(PermessoEnte::class);
    }

    Route::group(['middleware' => ['role:admin|supervisor|permessi ente']], function (){
    Route::get('/permessi-ente/table', [PermessoEnteController::class, 'index'])->name('permessi-ente.table');
    Route::get('/permessi-ente/create', [PermessoEnteController::class, 'create'])->name('addPermessoEnte');
    });