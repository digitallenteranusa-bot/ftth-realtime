<?php
namespace App\Http\Controllers;

use App\Models\FiberRoute;
use App\Models\Odc;
use App\Models\Odp;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OdpController extends Controller
{
    public function index()
    {
        return Inertia::render('Odp/Index', [
            'odps' => Odp::with('odc')->withCount('onts')->latest()->paginate(15),
        ]);
    }

    public function create()
    {
        return Inertia::render('Odp/Create', [
            'odcs' => Odc::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'odc_id' => 'required|exists:odcs,id',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'address' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'used_ports' => 'nullable|integer|min:0',
            'splitter_ratio' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $odp = Odp::create($validated);

        // Auto-create fiber route ODC → ODP
        $odc = Odc::find($odp->odc_id);
        if ($odc) {
            FiberRoute::create([
                'name' => "Distribusi {$odc->name} - {$odp->name}",
                'source_type' => 'odc',
                'source_id' => $odc->id,
                'destination_type' => 'odp',
                'destination_id' => $odp->id,
                'coordinates' => [
                    [$odc->lat, $odc->lng],
                    [$odp->lat, $odp->lng],
                ],
                'color' => '#33cc33',
                'status' => 'active',
            ]);
        }

        return redirect()->route('odps.index')->with('success', 'ODP berhasil ditambahkan.');
    }

    public function show(Odp $odp)
    {
        return Inertia::render('Odp/Show', [
            'odp' => $odp->load(['odc', 'onts.customer']),
        ]);
    }

    public function edit(Odp $odp)
    {
        return Inertia::render('Odp/Edit', [
            'odp' => $odp,
            'odcs' => Odc::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Odp $odp)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'odc_id' => 'required|exists:odcs,id',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'address' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'used_ports' => 'nullable|integer|min:0',
            'splitter_ratio' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $oldLat = $odp->lat;
        $oldLng = $odp->lng;
        $odp->update($validated);

        if ($odp->lat != $oldLat || $odp->lng != $oldLng) {
            $this->updateFiberRouteCoordinates('odp', $odp->id, $odp->lat, $odp->lng);
        }

        return redirect()->route('odps.index')->with('success', 'ODP berhasil diupdate.');
    }

    public function destroy(Odp $odp)
    {
        FiberRoute::where(function ($q) use ($odp) {
            $q->where(['source_type' => 'odp', 'source_id' => $odp->id])
              ->orWhere(['destination_type' => 'odp', 'destination_id' => $odp->id]);
        })->delete();

        $odp->delete();
        return redirect()->route('odps.index')->with('success', 'ODP berhasil dihapus.');
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
