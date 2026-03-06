<?php

use App\Http\Controllers\AlarmController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FiberRouteController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MikrotikController;
use App\Http\Controllers\OdcController;
use App\Http\Controllers\OdpController;
use App\Http\Controllers\OltController;
use App\Http\Controllers\OntController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TroubleTicketController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Map
    Route::get('/map', [MapController::class, 'index'])->name('map');
    Route::get('/api/map/elements', [MapController::class, 'elements'])->name('map.elements');
    Route::get('/api/map/fiber-routes', [MapController::class, 'fiberRoutes'])->name('map.fiber-routes');

    // Mikrotik
    Route::resource('mikrotiks', MikrotikController::class);
    Route::get('/mikrotiks/{mikrotik}/resources', [MikrotikController::class, 'resources'])->name('mikrotiks.resources');
    Route::post('/mikrotiks/{mikrotik}/pppoe-disconnect', [MikrotikController::class, 'pppoeDisconnect'])->name('mikrotiks.pppoe-disconnect');

    // OLT
    Route::resource('olts', OltController::class);
    Route::get('/olts/{olt}/unregistered-onts', [OltController::class, 'unregisteredOnts'])->name('olts.unregistered-onts');

    // ODC, ODP, ONT
    Route::resource('odcs', OdcController::class);
    Route::resource('odps', OdpController::class);
    Route::resource('onts', OntController::class);

    // Customers
    Route::resource('customers', CustomerController::class);

    // Fiber Routes
    Route::resource('fiber-routes', FiberRouteController::class)->only(['index', 'store', 'update', 'destroy']);

    // Alarms
    Route::get('/alarms', [AlarmController::class, 'index'])->name('alarms.index');
    Route::post('/alarms/{alarm}/resolve', [AlarmController::class, 'resolve'])->name('alarms.resolve');

    // Trouble Tickets
    Route::resource('tickets', TroubleTicketController::class);

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
