<?php

use App\Http\Controllers\SimulacaoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('analise');
});

Route::get('/clientes', function () {
    return view('clientes');
});

Route::get('/simulacao/{id}', [SimulacaoController::class, 'show']);
