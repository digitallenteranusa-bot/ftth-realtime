<?php
namespace App\Http\Controllers;

use App\Models\FiberRoute;
use App\Models\Olt;
use App\Models\Ont;
use App\Models\PonPort;
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
        $oldLat = $olt->lat;
        $oldLng = $olt->lng;
        $olt->update($validated);

        // Update fiber routes jika lokasi berubah
        if ($olt->lat != $oldLat || $olt->lng != $oldLng) {
            FiberRoute::where('source_type', 'olt')->where('source_id', $olt->id)->each(function ($route) use ($olt) {
                $coords = $route->coordinates;
                if (!empty($coords)) {
                    $coords[0] = [$olt->lat, $olt->lng];
                    $route->update(['coordinates' => $coords]);
                }
            });
        }

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

    public function registerOnt(Request $request, Olt $olt)
    {
        $validated = $request->validate([
            'slot' => 'required|integer',
            'port' => 'required|integer',
            'ont_id' => 'required|integer',
            'serial_number' => 'required|string|max:255',
            'line_profile' => 'required|string|max:255',
            'service_profile' => 'required|string|max:255',
            'name' => 'nullable|string|max:255',
            'customer_id' => 'nullable|exists:customers,id',
            'odp_id' => 'nullable|exists:odps,id',
        ]);

        $driver = OltServiceFactory::make($olt);
        if (!$driver->connect()) {
            return back()->with('error', 'Tidak dapat terhubung ke OLT.');
        }

        $success = $driver->registerOnt(
            $validated['slot'],
            $validated['port'],
            $validated['ont_id'],
            $validated['serial_number'],
            $validated['line_profile'],
            $validated['service_profile']
        );
        $driver->disconnect();

        if (!$success) {
            return back()->with('error', 'Gagal mendaftarkan ONT di OLT.');
        }

        $ponPort = PonPort::firstOrCreate(
            ['olt_id' => $olt->id, 'slot' => $validated['slot'], 'port' => $validated['port']],
            ['name' => "gpon_{$validated['slot']}/{$validated['port']}"]
        );

        Ont::create([
            'olt_id' => $olt->id,
            'pon_port_id' => $ponPort->id,
            'odp_id' => $validated['odp_id'] ?? null,
            'customer_id' => $validated['customer_id'] ?? null,
            'name' => $validated['name'] ?? $validated['serial_number'],
            'serial_number' => $validated['serial_number'],
            'ont_id_number' => $validated['ont_id'],
            'status' => 'online',
        ]);

        return back()->with('success', 'ONT berhasil didaftarkan.');
    }

    public function deregisterOnt(Request $request, Olt $olt)
    {
        $validated = $request->validate([
            'slot' => 'required|integer',
            'port' => 'required|integer',
            'ont_id' => 'required|integer',
        ]);

        $driver = OltServiceFactory::make($olt);
        if (!$driver->connect()) {
            return back()->with('error', 'Tidak dapat terhubung ke OLT.');
        }

        $success = $driver->deregisterOnt(
            $validated['slot'],
            $validated['port'],
            $validated['ont_id']
        );
        $driver->disconnect();

        if (!$success) {
            return back()->with('error', 'Gagal menghapus ONT dari OLT.');
        }

        Ont::where('olt_id', $olt->id)
            ->where('ont_id_number', $validated['ont_id'])
            ->delete();

        return back()->with('success', 'ONT berhasil dihapus dari OLT.');
    }
}
