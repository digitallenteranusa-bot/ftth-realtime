<?php
namespace App\Http\Controllers;

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

        $odc->update($validated);
        return redirect()->route('odcs.index')->with('success', 'ODC berhasil diupdate.');
    }

    public function destroy(Odc $odc)
    {
        $odc->delete();
        return redirect()->route('odcs.index')->with('success', 'ODC berhasil dihapus.');
    }
}
