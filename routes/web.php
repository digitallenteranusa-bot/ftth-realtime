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
use App\Http\Controllers\OdpController;
use App\Http\Controllers\OltController;
use App\Http\Controllers\OntController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TroubleTicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// All authenticated users (admin, operator, viewer)
Route::middleware(['auth', 'verified'])->group(function () {
    // Read-only: everyone can view
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/map', [MapController::class, 'index'])->name('map');
    Route::get('/api/map/elements', [MapController::class, 'elements'])->name('map.elements');
    Route::get('/api/map/fiber-routes', [MapController::class, 'fiberRoutes'])->name('map.fiber-routes');
    Route::get('/alarms', [AlarmController::class, 'index'])->name('alarms.index');

    // View-only routes for all roles
    Route::get('/mikrotiks', [MikrotikController::class, 'index'])->name('mikrotiks.index');
    Route::get('/mikrotiks/{mikrotik}', [MikrotikController::class, 'show'])->name('mikrotiks.show');
    Route::get('/mikrotiks/{mikrotik}/resources', [MikrotikController::class, 'resources'])->name('mikrotiks.resources');
    Route::get('/olts', [OltController::class, 'index'])->name('olts.index');
    Route::get('/olts/{olt}', [OltController::class, 'show'])->name('olts.show');
    Route::get('/olts/{olt}/unregistered-onts', [OltController::class, 'unregisteredOnts'])->name('olts.unregistered-onts');
    Route::get('/odcs', [OdcController::class, 'index'])->name('odcs.index');
    Route::get('/odcs/{odc}', [OdcController::class, 'show'])->name('odcs.show');
    Route::get('/odps', [OdpController::class, 'index'])->name('odps.index');
    Route::get('/odps/{odp}', [OdpController::class, 'show'])->name('odps.show');
    Route::get('/onts', [OntController::class, 'index'])->name('onts.index');
    Route::get('/onts/{ont}', [OntController::class, 'show'])->name('onts.show');
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/fiber-routes', [FiberRouteController::class, 'index'])->name('fiber-routes.index');
    Route::get('/tickets', [TroubleTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [TroubleTicketController::class, 'show'])->name('tickets.show');
    Route::get('/bandwidth-plans', [BandwidthPlanController::class, 'index'])->name('bandwidth-plans.index');

    // Export routes
    Route::get('/export/customers/csv', [ExportController::class, 'customersCsv'])->name('export.customers.csv');
    Route::get('/export/customers/pdf', [ExportController::class, 'customersPdf'])->name('export.customers.pdf');
    Route::get('/export/onts/csv', [ExportController::class, 'ontsCsv'])->name('export.onts.csv');
    Route::get('/export/onts/pdf', [ExportController::class, 'ontsPdf'])->name('export.onts.pdf');
    Route::get('/export/alarms/csv', [ExportController::class, 'alarmsCsv'])->name('export.alarms.csv');

    // Profile (all roles)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin & Operator: can create, edit, delete
Route::middleware(['auth', 'verified', 'role:admin,operator'])->group(function () {
    // Mikrotik CUD
    Route::get('/mikrotiks/create', [MikrotikController::class, 'create'])->name('mikrotiks.create');
    Route::post('/mikrotiks', [MikrotikController::class, 'store'])->name('mikrotiks.store');
    Route::get('/mikrotiks/{mikrotik}/edit', [MikrotikController::class, 'edit'])->name('mikrotiks.edit');
    Route::put('/mikrotiks/{mikrotik}', [MikrotikController::class, 'update'])->name('mikrotiks.update');
    Route::post('/mikrotiks/{mikrotik}/pppoe-disconnect', [MikrotikController::class, 'pppoeDisconnect'])->name('mikrotiks.pppoe-disconnect');

    // OLT CUD
    Route::get('/olts/create', [OltController::class, 'create'])->name('olts.create');
    Route::post('/olts', [OltController::class, 'store'])->name('olts.store');
    Route::get('/olts/{olt}/edit', [OltController::class, 'edit'])->name('olts.edit');
    Route::put('/olts/{olt}', [OltController::class, 'update'])->name('olts.update');
    Route::post('/olts/{olt}/register-ont', [OltController::class, 'registerOnt'])->name('olts.register-ont');
    Route::post('/olts/{olt}/deregister-ont', [OltController::class, 'deregisterOnt'])->name('olts.deregister-ont');

    // ODC CUD
    Route::get('/odcs/create', [OdcController::class, 'create'])->name('odcs.create');
    Route::post('/odcs', [OdcController::class, 'store'])->name('odcs.store');
    Route::get('/odcs/{odc}/edit', [OdcController::class, 'edit'])->name('odcs.edit');
    Route::put('/odcs/{odc}', [OdcController::class, 'update'])->name('odcs.update');

    // ODP CUD
    Route::get('/odps/create', [OdpController::class, 'create'])->name('odps.create');
    Route::post('/odps', [OdpController::class, 'store'])->name('odps.store');
    Route::get('/odps/{odp}/edit', [OdpController::class, 'edit'])->name('odps.edit');
    Route::put('/odps/{odp}', [OdpController::class, 'update'])->name('odps.update');

    // ONT CUD
    Route::get('/onts/create', [OntController::class, 'create'])->name('onts.create');
    Route::post('/onts', [OntController::class, 'store'])->name('onts.store');
    Route::get('/onts/{ont}/edit', [OntController::class, 'edit'])->name('onts.edit');
    Route::put('/onts/{ont}', [OntController::class, 'update'])->name('onts.update');

    // Customer CUD
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');

    // Fiber Routes CUD
    Route::post('/fiber-routes', [FiberRouteController::class, 'store'])->name('fiber-routes.store');
    Route::put('/fiber-routes/{fiber_route}', [FiberRouteController::class, 'update'])->name('fiber-routes.update');

    // Alarms resolve
    Route::post('/alarms/{alarm}/resolve', [AlarmController::class, 'resolve'])->name('alarms.resolve');

    // Tickets CUD
    Route::get('/tickets/create', [TroubleTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TroubleTicketController::class, 'store'])->name('tickets.store');
    Route::put('/tickets/{ticket}', [TroubleTicketController::class, 'update'])->name('tickets.update');

    // Bandwidth Plans CUD
    Route::get('/bandwidth-plans/create', [BandwidthPlanController::class, 'create'])->name('bandwidth-plans.create');
    Route::post('/bandwidth-plans', [BandwidthPlanController::class, 'store'])->name('bandwidth-plans.store');
    Route::get('/bandwidth-plans/{bandwidth_plan}/edit', [BandwidthPlanController::class, 'edit'])->name('bandwidth-plans.edit');
    Route::put('/bandwidth-plans/{bandwidth_plan}', [BandwidthPlanController::class, 'update'])->name('bandwidth-plans.update');
});

// Admin only: destructive operations
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::delete('/mikrotiks/{mikrotik}', [MikrotikController::class, 'destroy'])->name('mikrotiks.destroy');
    Route::delete('/olts/{olt}', [OltController::class, 'destroy'])->name('olts.destroy');
    Route::delete('/odcs/{odc}', [OdcController::class, 'destroy'])->name('odcs.destroy');
    Route::delete('/odps/{odp}', [OdpController::class, 'destroy'])->name('odps.destroy');
    Route::delete('/onts/{ont}', [OntController::class, 'destroy'])->name('onts.destroy');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::delete('/fiber-routes/{fiber_route}', [FiberRouteController::class, 'destroy'])->name('fiber-routes.destroy');
    Route::delete('/tickets/{ticket}', [TroubleTicketController::class, 'destroy'])->name('tickets.destroy');
    Route::delete('/bandwidth-plans/{bandwidth_plan}', [BandwidthPlanController::class, 'destroy'])->name('bandwidth-plans.destroy');
});

require __DIR__.'/auth.php';
