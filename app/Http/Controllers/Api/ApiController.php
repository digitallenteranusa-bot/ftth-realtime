<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alarm;
use App\Models\Customer;
use App\Models\Mikrotik;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Ont;
use App\Models\PppoeSession;
use App\Models\TroubleTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    protected function perPage(Request $request, int $default = 20, int $max = 100): int
    {
        return min(max((int) ($request->per_page ?? $default), 1), $max);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            Log::warning('API login failed', ['email' => $request->email, 'ip' => $request->ip()]);
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        $token = $user->createToken($request->device_name ?? 'mobile-app')->plainTextToken;

        Log::info('API login success', ['user_id' => $user->id, 'ip' => $request->ip()]);

        return response()->json([
            'token' => $token,
            'user' => $user->only('id', 'name', 'email', 'role'),
        ]);
    }

    public function dashboard()
    {
        return response()->json([
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
        ]);
    }

    public function customers(Request $request)
    {
        $query = Customer::withCount('onts');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        return response()->json($query->latest()->paginate($this->perPage($request)));
    }

    public function customerShow($id)
    {
        $customer = Customer::with('onts.odp')->findOrFail($id);
        return response()->json($customer);
    }

    public function onts(Request $request)
    {
        $query = Ont::with(['customer:id,name', 'odp:id,name', 'olt:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('serial_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        return response()->json($query->latest()->paginate($this->perPage($request)));
    }

    public function ontShow($id)
    {
        $ont = Ont::with(['customer', 'odp.odc', 'olt', 'ponPort'])->findOrFail($id);
        return response()->json($ont);
    }

    public function alarms(Request $request)
    {
        $query = Alarm::query();

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->boolean('unresolved', false)) {
            $query->where('is_resolved', false);
        }

        return response()->json($query->latest()->paginate($this->perPage($request)));
    }

    public function tickets(Request $request)
    {
        $query = TroubleTicket::with('customer:id,name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest()->paginate($this->perPage($request)));
    }
}
