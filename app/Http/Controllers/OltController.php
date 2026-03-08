<?php
namespace App\Http\Controllers;

use App\Models\FiberRoute;
use App\Models\Olt;
use App\Models\Ont;
use App\Models\PonPort;
use App\Services\Olt\HisoOltDriver;
use App\Services\Olt\OltServiceFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            'vendor' => 'required|string|max:50',
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
            'pon_count' => 'nullable|integer|in:1,2,4,8,16',
        ]);

        $ponCount = $validated['pon_count'] ?? 8;
        unset($validated['pon_count']);

        $olt = Olt::create($validated);

        // Auto-create PON ports
        for ($p = 1; $p <= $ponCount; $p++) {
            PonPort::create([
                'olt_id' => $olt->id,
                'slot' => 0,
                'port' => $p,
                'description' => "PON {$p}",
                'is_active' => true,
            ]);
        }

        return redirect()->route('olts.index')->with('success', "OLT berhasil ditambahkan dengan {$ponCount} PON port.");
    }

    public function show(Olt $olt)
    {
        return Inertia::render('Olt/Show', [
            'olt' => $olt->load('ponPorts.onts.customer'),
        ]);
    }

    public function edit(Olt $olt)
    {
        return Inertia::render('Olt/Edit', [
            'olt' => $olt->loadCount('ponPorts'),
        ]);
    }

    public function update(Request $request, Olt $olt)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'vendor' => 'required|string|max:50',
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
            'pon_count' => 'nullable|integer|in:1,2,4,8,16',
        ]);

        $ponCount = $validated['pon_count'] ?? null;
        unset($validated['pon_count']);

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

        // Sync PON port count
        if ($ponCount !== null) {
            $currentCount = $olt->ponPorts()->count();
            if ($ponCount > $currentCount) {
                for ($p = $currentCount + 1; $p <= $ponCount; $p++) {
                    PonPort::create([
                        'olt_id' => $olt->id,
                        'slot' => 0,
                        'port' => $p,
                        'description' => "PON {$p}",
                        'is_active' => true,
                    ]);
                }
            } elseif ($ponCount < $currentCount) {
                // Remove excess ports from the end (only if no ONTs attached)
                $olt->ponPorts()
                    ->whereDoesntHave('onts')
                    ->orderByDesc('port')
                    ->limit($currentCount - $ponCount)
                    ->delete();
            }
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

    /**
     * Sync signal (Rx/Tx power) untuk semua ONT di satu OLT
     */
    public function syncSignal(Olt $olt)
    {
        $driver = OltServiceFactory::make($olt);

        if (!$driver->connect()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke OLT. Periksa host, port, username, dan password.',
            ], 500);
        }

        $updated = 0;
        $errors = [];

        try {
            $ponPorts = $olt->ponPorts()->where('is_active', true)->with('onts')->get();

            foreach ($ponPorts as $ponPort) {
                // Try bulk query first (HIOSO supports port-level optical query)
                if ($driver instanceof HisoOltDriver) {
                    $allPower = $driver->getAllOpticalPower($ponPort->slot, $ponPort->port);

                    foreach ($ponPort->onts as $ont) {
                        if ($ont->ont_id_number && isset($allPower[$ont->ont_id_number])) {
                            $power = $allPower[$ont->ont_id_number];
                            $ont->update([
                                'rx_power' => $power['rx_power'] ?? null,
                                'tx_power' => $power['tx_power'] ?? null,
                                'status' => 'online',
                                'last_online_at' => now(),
                            ]);
                            $updated++;
                        }
                    }
                }

                // Fallback: query per-ONT
                foreach ($ponPort->onts as $ont) {
                    if (!$ont->ont_id_number) continue;

                    // Skip if already updated by bulk query
                    if ($driver instanceof HisoOltDriver && $ont->wasChanged()) continue;

                    try {
                        $power = $driver->getOpticalPower($ponPort->slot, $ponPort->port, $ont->ont_id_number);

                        if (!empty($power)) {
                            $updateData = [];
                            if (isset($power['rx_power'])) $updateData['rx_power'] = $power['rx_power'];
                            if (isset($power['tx_power'])) $updateData['tx_power'] = $power['tx_power'];

                            if (!empty($updateData)) {
                                $updateData['last_online_at'] = now();
                                $updateData['status'] = 'online';
                                $ont->update($updateData);
                                $updated++;
                            }
                        }
                    } catch (\Throwable $e) {
                        $errors[] = "ONT {$ont->name} (ID:{$ont->ont_id_number}): {$e->getMessage()}";
                        Log::warning("Sync signal failed for ONT {$ont->id}", ['error' => $e->getMessage()]);
                    }
                }
            }
        } finally {
            $driver->disconnect();
        }

        return response()->json([
            'success' => true,
            'message' => "{$updated} ONT berhasil disinkronkan.",
            'updated' => $updated,
            'errors' => $errors,
        ]);
    }

    /**
     * Sync signal untuk satu ONT spesifik
     */
    public function syncOntSignal(Olt $olt, Ont $ont)
    {
        $driver = OltServiceFactory::make($olt);

        if (!$driver->connect()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat terhubung ke OLT.',
            ], 500);
        }

        try {
            $ponPort = $ont->ponPort;
            if (!$ponPort) {
                return response()->json([
                    'success' => false,
                    'message' => 'ONT tidak memiliki PON Port.',
                ], 400);
            }

            if (!$ont->ont_id_number) {
                return response()->json([
                    'success' => false,
                    'message' => 'ONT ID belum diset.',
                ], 400);
            }

            $power = $driver->getOpticalPower($ponPort->slot, $ponPort->port, $ont->ont_id_number);

            if (empty($power)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat membaca signal dari ONU. Pastikan ONU online dan mendukung diagnostic.',
                ]);
            }

            $updateData = [];
            if (isset($power['rx_power'])) $updateData['rx_power'] = $power['rx_power'];
            if (isset($power['tx_power'])) $updateData['tx_power'] = $power['tx_power'];
            $updateData['last_online_at'] = now();
            $updateData['status'] = 'online';

            $ont->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Signal berhasil disinkronkan.',
                'data' => [
                    'rx_power' => $power['rx_power'] ?? null,
                    'tx_power' => $power['tx_power'] ?? null,
                    'temperature' => $power['temperature'] ?? null,
                    'voltage' => $power['voltage'] ?? null,
                ],
            ]);
        } finally {
            $driver->disconnect();
        }
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
