<?php
namespace App\Http\Controllers;

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

        Odp::create($validated);
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

        $odp->update($validated);
        return redirect()->route('odps.index')->with('success', 'ODP berhasil diupdate.');
    }

    public function destroy(Odp $odp)
    {
        $odp->delete();
        return redirect()->route('odps.index')->with('success', 'ODP berhasil dihapus.');
    }
}
