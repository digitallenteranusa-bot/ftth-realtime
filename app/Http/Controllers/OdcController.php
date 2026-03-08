<?php
namespace App\Http\Controllers;

use App\Models\FiberRoute;
use App\Models\Odc;
use App\Models\Olt;
use App\Models\PonPort;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OdcController extends Controller
{
    public function index(Request $request)
    {
        $query = Odc::with('olt:id,name')->withCount('odps');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhereHas('olt', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        return Inertia::render('Odc/Index', [
            'odcs' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Odc/Create', [
            'olts' => Olt::where('is_active', true)->get(['id', 'name']),
            'ponPorts' => PonPort::with('olt:id,name')->where('is_active', true)->get(['id', 'olt_id', 'slot', 'port']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'olt_id' => 'nullable|exists:olts,id',
            'pon_port_id' => 'nullable|exists:pon_ports,id',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'address' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'used_ports' => 'nullable|integer|min:0',
            'splitter_ratio' => 'nullable|string|max:20',
            'geojson_area' => 'nullable|array',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $odc = Odc::create($validated);

        // Auto-create fiber route OLT → ODC
        if ($odc->olt_id) {
            $olt = Olt::find($odc->olt_id);
            if ($olt && $olt->lat && $olt->lng) {
                FiberRoute::create([
                    'name' => "Feeder {$olt->name} - {$odc->name}",
                    'source_type' => 'olt',
                    'source_id' => $olt->id,
                    'destination_type' => 'odc',
                    'destination_id' => $odc->id,
                    'coordinates' => [
                        [$olt->lat, $olt->lng],
                        [$odc->lat, $odc->lng],
                    ],
                    'color' => '#3388ff',
                    'status' => 'active',
                ]);
            }
        }

        return redirect()->route('odcs.index')->with('success', 'ODC berhasil ditambahkan.');
    }

    public function show(Odc $odc)
    {
        return Inertia::render('Odc/Show', [
            'odc' => $odc->load('odps.onts'),
        ]);
    }

    public function edit(Odc $odc)
    {
        return Inertia::render('Odc/Edit', [
            'odc' => $odc,
            'olts' => Olt::where('is_active', true)->get(['id', 'name']),
            'ponPorts' => PonPort::with('olt:id,name')->where('is_active', true)->get(['id', 'olt_id', 'slot', 'port']),
        ]);
    }

    public function update(Request $request, Odc $odc)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'olt_id' => 'nullable|exists:olts,id',
            'pon_port_id' => 'nullable|exists:pon_ports,id',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'address' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'used_ports' => 'nullable|integer|min:0',
            'splitter_ratio' => 'nullable|string|max:20',
            'geojson_area' => 'nullable|array',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $oldLat = $odc->lat;
        $oldLng = $odc->lng;
        $odc->update($validated);

        if ($odc->lat != $oldLat || $odc->lng != $oldLng) {
            $this->updateFiberRouteCoordinates('odc', $odc->id, $odc->lat, $odc->lng);
        }

        return redirect()->route('odcs.index')->with('success', 'ODC berhasil diupdate.');
    }

    public function destroy(Odc $odc)
    {
        // Hapus fiber routes terkait
        FiberRoute::where(function ($q) use ($odc) {
            $q->where(['source_type' => 'odc', 'source_id' => $odc->id])
              ->orWhere(['destination_type' => 'odc', 'destination_id' => $odc->id]);
        })->delete();

        $odc->delete();
        return redirect()->route('odcs.index')->with('success', 'ODC berhasil dihapus.');
    }

    private function updateFiberRouteCoordinates(string $type, int $id, float $newLat, float $newLng): void
    {
        FiberRoute::where('source_type', $type)->where('source_id', $id)->each(function ($route) use ($newLat, $newLng) {
            $coords = $route->coordinates;
            if (!empty($coords)) {
                $coords[0] = [$newLat, $newLng];
                $route->update(['coordinates' => $coords]);
            }
        });

        FiberRoute::where('destination_type', $type)->where('destination_id', $id)->each(function ($route) use ($newLat, $newLng) {
            $coords = $route->coordinates;
            if (!empty($coords)) {
                $coords[count($coords) - 1] = [$newLat, $newLng];
                $route->update(['coordinates' => $coords]);
            }
        });
    }
}
