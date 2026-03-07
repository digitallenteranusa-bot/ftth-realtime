<?php
namespace App\Http\Controllers;

use App\Models\Alarm;
use App\Models\Customer;
use App\Models\Mikrotik;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Ont;
use App\Models\PppoeSession;
use App\Models\TroubleTicket;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'customers' => Customer::count(),
                'mikrotiks' => Mikrotik::where('is_active', true)->count(),
                'olts' => Olt::where('is_active', true)->count(),
                'odcs' => Odc::count(),
                'odps' => Odp::count(),
                'onts_total' => Ont::count(),
                'onts_online' => Ont::where('status', 'online')->count(),
                'onts_offline' => Ont::where('status', 'offline')->count(),
                'onts_los' => Ont::where('status', 'los')->count(),
                'active_pppoe' => PppoeSession::where('is_active', true)->count(),
                'open_tickets' => TroubleTicket::whereIn('status', ['open', 'in_progress'])->count(),
                'unresolved_alarms' => Alarm::where('is_resolved', false)->count(),
            ],
            'recentAlarms' => Alarm::with([])->where('is_resolved', false)->latest()->take(10)->get(),
            'recentTickets' => TroubleTicket::with('customer')->latest()->take(5)->get(),
            'mikrotiks' => Mikrotik::where('is_active', true)->get(['id', 'name']),
        ]);
    }
}
