<?php
namespace App\Http\Controllers;

use App\Models\Olt;
use App\Services\Olt\OltServiceFactory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OltController extends Controller
{
    public function index()
    {
        return Inertia::render('Olt/Index', [
            'olts' => Olt::withCount('ponPorts', 'onts')->latest()->paginate(15),
        ]);
    }

    public function create()
    {
        return Inertia::render('Olt/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'vendor' => 'required|in:zte,huawei,fiberhome',
            'host' => 'required|string|max:255',
            'telnet_port' => 'nullable|integer',
            'ssh_port' => 'nullable|integer',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'snmp_community' => 'nullable|string|max:255',
            'snmp_port' => 'nullable|integer',
            'is_active' => 'boolean',
            'location' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        Olt::create($validated);
        return redirect()->route('olts.index')->with('success', 'OLT berhasil ditambahkan.');
    }

    public function show(Olt $olt)
    {
        return Inertia::render('Olt/Show', [
            'olt' => $olt->load('ponPorts.onts'),
        ]);
    }

    public function edit(Olt $olt)
    {
        return Inertia::render('Olt/Edit', [
            'olt' => $olt,
        ]);
    }

    public function update(Request $request, Olt $olt)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'vendor' => 'required|in:zte,huawei,fiberhome',
            'host' => 'required|string|max:255',
            'telnet_port' => 'nullable|integer',
            'ssh_port' => 'nullable|integer',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string',
            'snmp_community' => 'nullable|string|max:255',
            'snmp_port' => 'nullable|integer',
            'is_active' => 'boolean',
            'location' => 'nullable|string|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        if (empty($validated['password'])) unset($validated['password']);
        $olt->update($validated);
        return redirect()->route('olts.index')->with('success', 'OLT berhasil diupdate.');
    }

    public function destroy(Olt $olt)
    {
        $olt->delete();
        return redirect()->route('olts.index')->with('success', 'OLT berhasil dihapus.');
    }

    public function unregisteredOnts(Olt $olt)
    {
        $driver = OltServiceFactory::make($olt);
        if (!$driver->connect()) {
            return response()->json(['error' => 'Cannot connect to OLT'], 500);
        }
        $onts = $driver->getUnregisteredOnts();
        $driver->disconnect();
        return response()->json($onts);
    }
}
