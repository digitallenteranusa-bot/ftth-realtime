<?php
namespace App\Http\Controllers;

use App\Models\Mikrotik;
use App\Services\Mikrotik\MikrotikApiService;
use App\Services\Mikrotik\MikrotikConnectionManager;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MikrotikController extends Controller
{
    public function index()
    {
        return Inertia::render('Mikrotik/Index', [
            'mikrotiks' => Mikrotik::latest()->paginate(15),
        ]);
    }

    public function create()
    {
        return Inertia::render('Mikrotik/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'api_port' => 'integer|min:1|max:65535',
            'api_username' => 'required|string|max:255',
            'api_password' => 'required|string',
            'snmp_community' => 'nullable|string|max:255',
            'snmp_port' => 'nullable|integer',
            'is_active' => 'boolean',
            'location' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        Mikrotik::create($validated);
        return redirect()->route('mikrotiks.index')->with('success', 'Mikrotik berhasil ditambahkan.');
    }

    public function show(Mikrotik $mikrotik)
    {
        return Inertia::render('Mikrotik/Show', [
            'mikrotik' => $mikrotik,
            'pppoeSessions' => $mikrotik->pppoeSessions()->where('is_active', true)->paginate(20),
            'interfaces' => $mikrotik->interfaceTraffics()->get(),
        ]);
    }

    public function edit(Mikrotik $mikrotik)
    {
        return Inertia::render('Mikrotik/Edit', [
            'mikrotik' => $mikrotik,
        ]);
    }

    public function update(Request $request, Mikrotik $mikrotik)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'host' => 'required|string|max:255',
            'api_port' => 'integer|min:1|max:65535',
            'api_username' => 'required|string|max:255',
            'api_password' => 'nullable|string',
            'snmp_community' => 'nullable|string|max:255',
            'snmp_port' => 'nullable|integer',
            'is_active' => 'boolean',
            'location' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        if (empty($validated['api_password'])) {
            unset($validated['api_password']);
        }

        $mikrotik->update($validated);
        return redirect()->route('mikrotiks.index')->with('success', 'Mikrotik berhasil diupdate.');
    }

    public function destroy(Mikrotik $mikrotik)
    {
        $mikrotik->delete();
        return redirect()->route('mikrotiks.index')->with('success', 'Mikrotik berhasil dihapus.');
    }

    public function resources(Mikrotik $mikrotik, MikrotikApiService $service)
    {
        return response()->json($service->getSystemResources($mikrotik));
    }

    public function pppoeDisconnect(Mikrotik $mikrotik, Request $request, MikrotikApiService $service)
    {
        $request->validate(['id' => 'required|string']);
        $result = $service->disconnectPppoe($mikrotik, $request->id);
        return response()->json(['success' => $result]);
    }
}
