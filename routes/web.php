<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name', 'Mini-CRM API'),
        'status' => 'online',
    ]);
});
