<?php

use App\Http\Controllers\AlarmController;
use App\Http\Controllers\BandwidthPlanController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FiberRouteController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MikrotikController;
use App\Http\Controllers\OdcController;
use App\Http\Controllers\PonPortController;
use App\Http\Controllers\OdpController;
use App\Http\Controllers\OltController;
use App\Http\Controllers\OntController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TroubleTicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// All authenticated users
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard & Map
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/map', [MapController::class, 'index'])->name('map');
    Route::get('/api/map/elements', [MapController::class, 'elements'])->name('map.elements');
    Route::get('/api/map/fiber-routes', [MapController::class, 'fiberRoutes'])->name('map.fiber-routes');

    // Mikrotik - create/edit BEFORE {mikrotik} to avoid route conflict
    Route::get('/mikrotiks', [MikrotikController::class, 'index'])->name('mikrotiks.index');
    Route::get('/mikrotiks/create', [MikrotikController::class, 'create'])->name('mikrotiks.create')->middleware('role:admin,operator');
    Route::post('/mikrotiks', [MikrotikController::class, 'store'])->name('mikrotiks.store')->middleware('role:admin,operator');
    Route::get('/mikrotiks/{mikrotik}', [MikrotikController::class, 'show'])->name('mikrotiks.show');
    Route::get('/mikrotiks/{mikrotik}/edit', [MikrotikController::class, 'edit'])->name('mikrotiks.edit')->middleware('role:admin,operator');
    Route::put('/mikrotiks/{mikrotik}', [MikrotikController::class, 'update'])->name('mikrotiks.update')->middleware('role:admin,operator');
    Route::delete('/mikrotiks/{mikrotik}', [MikrotikController::class, 'destroy'])->name('mikrotiks.destroy')->middleware('role:admin');
    Route::get('/mikrotiks/{mikrotik}/resources', [MikrotikController::class, 'resources'])->name('mikrotiks.resources');
    Route::post('/mikrotiks/{mikrotik}/pppoe-disconnect', [MikrotikController::class, 'pppoeDisconnect'])->name('mikrotiks.pppoe-disconnect')->middleware('role:admin,operator');

    // OLT
    Route::get('/olts', [OltController::class, 'index'])->name('olts.index');
    Route::get('/olts/create', [OltController::class, 'create'])->name('olts.create')->middleware('role:admin,operator');
    Route::post('/olts', [OltController::class, 'store'])->name('olts.store')->middleware('role:admin,operator');
    Route::get('/olts/{olt}', [OltController::class, 'show'])->name('olts.show');
    Route::get('/olts/{olt}/edit', [OltController::class, 'edit'])->name('olts.edit')->middleware('role:admin,operator');
    Route::put('/olts/{olt}', [OltController::class, 'update'])->name('olts.update')->middleware('role:admin,operator');
    Route::delete('/olts/{olt}', [OltController::class, 'destroy'])->name('olts.destroy')->middleware('role:admin');
    Route::get('/olts/{olt}/unregistered-onts', [OltController::class, 'unregisteredOnts'])->name('olts.unregistered-onts');
    Route::post('/olts/{olt}/register-ont', [OltController::class, 'registerOnt'])->name('olts.register-ont')->middleware('role:admin,operator');
    Route::post('/olts/{olt}/deregister-ont', [OltController::class, 'deregisterOnt'])->name('olts.deregister-ont')->middleware('role:admin,operator');

    // PON Ports
    Route::post('/pon-ports', [PonPortController::class, 'store'])->name('pon-ports.store')->middleware('role:admin,operator');
    Route::delete('/pon-ports/{id}', [PonPortController::class, 'destroy'])->name('pon-ports.destroy')->middleware('role:admin,operator');

    // ODC
    Route::get('/odcs', [OdcController::class, 'index'])->name('odcs.index');
    Route::get('/odcs/create', [OdcController::class, 'create'])->name('odcs.create')->middleware('role:admin,operator');
    Route::post('/odcs', [OdcController::class, 'store'])->name('odcs.store')->middleware('role:admin,operator');
    Route::get('/odcs/{odc}', [OdcController::class, 'show'])->name('odcs.show');
    Route::get('/odcs/{odc}/edit', [OdcController::class, 'edit'])->name('odcs.edit')->middleware('role:admin,operator');
    Route::put('/odcs/{odc}', [OdcController::class, 'update'])->name('odcs.update')->middleware('role:admin,operator');
    Route::delete('/odcs/{odc}', [OdcController::class, 'destroy'])->name('odcs.destroy')->middleware('role:admin');

    // ODP
    Route::get('/odps', [OdpController::class, 'index'])->name('odps.index');
    Route::get('/odps/create', [OdpController::class, 'create'])->name('odps.create')->middleware('role:admin,operator');
    Route::post('/odps', [OdpController::class, 'store'])->name('odps.store')->middleware('role:admin,operator');
    Route::get('/odps/{odp}', [OdpController::class, 'show'])->name('odps.show');
    Route::get('/odps/{odp}/edit', [OdpController::class, 'edit'])->name('odps.edit')->middleware('role:admin,operator');
    Route::put('/odps/{odp}', [OdpController::class, 'update'])->name('odps.update')->middleware('role:admin,operator');
    Route::delete('/odps/{odp}', [OdpController::class, 'destroy'])->name('odps.destroy')->middleware('role:admin');

    // ONT
    Route::get('/onts', [OntController::class, 'index'])->name('onts.index');
    Route::get('/onts/create', [OntController::class, 'create'])->name('onts.create')->middleware('role:admin,operator');
    Route::post('/onts', [OntController::class, 'store'])->name('onts.store')->middleware('role:admin,operator');
    Route::get('/onts/{ont}', [OntController::class, 'show'])->name('onts.show');
    Route::get('/onts/{ont}/edit', [OntController::class, 'edit'])->name('onts.edit')->middleware('role:admin,operator');
    Route::put('/onts/{ont}', [OntController::class, 'update'])->name('onts.update')->middleware('role:admin,operator');
    Route::delete('/onts/{ont}', [OntController::class, 'destroy'])->name('onts.destroy')->middleware('role:admin');

    // Customer
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create')->middleware('role:admin,operator');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store')->middleware('role:admin,operator');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit')->middleware('role:admin,operator');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update')->middleware('role:admin,operator');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy')->middleware('role:admin');

    // Alarms
    Route::get('/alarms', [AlarmController::class, 'index'])->name('alarms.index');
    Route::post('/alarms/{alarm}/resolve', [AlarmController::class, 'resolve'])->name('alarms.resolve')->middleware('role:admin,operator');

    // Tickets
    Route::get('/tickets', [TroubleTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TroubleTicketController::class, 'create'])->name('tickets.create')->middleware('role:admin,operator');
    Route::post('/tickets', [TroubleTicketController::class, 'store'])->name('tickets.store')->middleware('role:admin,operator');
    Route::get('/tickets/{ticket}', [TroubleTicketController::class, 'show'])->name('tickets.show');
    Route::put('/tickets/{ticket}', [TroubleTicketController::class, 'update'])->name('tickets.update')->middleware('role:admin,operator');
    Route::delete('/tickets/{ticket}', [TroubleTicketController::class, 'destroy'])->name('tickets.destroy')->middleware('role:admin');

    // Bandwidth Plans
    Route::get('/bandwidth-plans', [BandwidthPlanController::class, 'index'])->name('bandwidth-plans.index');
    Route::get('/bandwidth-plans/create', [BandwidthPlanController::class, 'create'])->name('bandwidth-plans.create')->middleware('role:admin,operator');
    Route::post('/bandwidth-plans', [BandwidthPlanController::class, 'store'])->name('bandwidth-plans.store')->middleware('role:admin,operator');
    Route::get('/bandwidth-plans/{bandwidth_plan}/edit', [BandwidthPlanController::class, 'edit'])->name('bandwidth-plans.edit')->middleware('role:admin,operator');
    Route::put('/bandwidth-plans/{bandwidth_plan}', [BandwidthPlanController::class, 'update'])->name('bandwidth-plans.update')->middleware('role:admin,operator');
    Route::delete('/bandwidth-plans/{bandwidth_plan}', [BandwidthPlanController::class, 'destroy'])->name('bandwidth-plans.destroy')->middleware('role:admin');

    // Fiber Routes
    Route::get('/fiber-routes', [FiberRouteController::class, 'index'])->name('fiber-routes.index');
    Route::post('/fiber-routes', [FiberRouteController::class, 'store'])->name('fiber-routes.store')->middleware('role:admin,operator');
    Route::put('/fiber-routes/{fiber_route}', [FiberRouteController::class, 'update'])->name('fiber-routes.update')->middleware('role:admin,operator');
    Route::delete('/fiber-routes/{fiber_route}', [FiberRouteController::class, 'destroy'])->name('fiber-routes.destroy')->middleware('role:admin');

    // Export
    Route::get('/export/customers/csv', [ExportController::class, 'customersCsv'])->name('export.customers.csv');
    Route::get('/export/customers/pdf', [ExportController::class, 'customersPdf'])->name('export.customers.pdf');
    Route::get('/export/onts/csv', [ExportController::class, 'ontsCsv'])->name('export.onts.csv');
    Route::get('/export/onts/pdf', [ExportController::class, 'ontsPdf'])->name('export.onts.pdf');
    Route::get('/export/alarms/csv', [ExportController::class, 'alarmsCsv'])->name('export.alarms.csv');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
