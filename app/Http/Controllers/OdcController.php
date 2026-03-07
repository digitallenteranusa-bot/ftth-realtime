<?php
namespace App\Http\Controllers;

use App\Models\FiberRoute;
use App\Models\Odc;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OdcController extends Controller
{
    public function index()
    {
        return Inertia::render('Odc/Index', [
            'odcs' => Odc::withCount('odps')->latest()->paginate(15),
        ]);
    }

    public function create()
    {
        return Inertia::render('Odc/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
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

        Odc::create($validated);
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
        return Inertia::render('Odc/Edit', ['odc' => $odc]);
    }

    public function update(Request $request, Odc $odc)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
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

        // Update fiber routes jika lokasi berubah
        if ($odc->lat != $oldLat || $odc->lng != $oldLng) {
            $this->updateFiberRouteCoordinates('odc', $odc->id, $odc->lat, $odc->lng);
        }

        return redirect()->route('odcs.index')->with('success', 'ODC berhasil diupdate.');
    }

    public function destroy(Odc $odc)
    {
        $odc->delete();
        return redirect()->route('odcs.index')->with('success', 'ODC berhasil dihapus.');
    }

    private function updateFiberRouteCoordinates(string $type, int $id, float $newLat, float $newLng): void
    {
        // Update routes where this device is the source (update first coordinate)
        FiberRoute::where('source_type', $type)->where('source_id', $id)->each(function ($route) use ($newLat, $newLng) {
            $coords = $route->coordinates;
            if (!empty($coords)) {
                $coords[0] = [$newLat, $newLng];
                $route->update(['coordinates' => $coords]);
            }
        });

        // Update routes where this device is the destination (update last coordinate)
        FiberRoute::where('destination_type', $type)->where('destination_id', $id)->each(function ($route) use ($newLat, $newLng) {
            $coords = $route->coordinates;
            if (!empty($coords)) {
                $coords[count($coords) - 1] = [$newLat, $newLng];
                $route->update(['coordinates' => $coords]);
            }
        });
    }
}
