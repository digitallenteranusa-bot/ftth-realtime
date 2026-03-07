<?php

namespace App\Http\Controllers;

use App\Models\BandwidthPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BandwidthPlanController extends Controller
{
    public function index()
    {
        return Inertia::render('BandwidthPlan/Index', [
            'bandwidthPlans' => BandwidthPlan::latest()->paginate(15),
        ]);
    }

    public function create()
    {
        return Inertia::render('BandwidthPlan/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'upload_speed' => 'required|string|max:50',
            'download_speed' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        BandwidthPlan::create($validated);
        return redirect()->route('bandwidth-plans.index')->with('success', 'Bandwidth plan berhasil ditambahkan.');
    }

    public function edit(BandwidthPlan $bandwidthPlan)
    {
        return Inertia::render('BandwidthPlan/Edit', [
            'bandwidthPlan' => $bandwidthPlan,
        ]);
    }

    public function update(Request $request, BandwidthPlan $bandwidthPlan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'upload_speed' => 'required|string|max:50',
            'download_speed' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $bandwidthPlan->update($validated);
        return redirect()->route('bandwidth-plans.index')->with('success', 'Bandwidth plan berhasil diupdate.');
    }

    public function destroy(BandwidthPlan $bandwidthPlan)
    {
        $bandwidthPlan->delete();
        return redirect()->route('bandwidth-plans.index')->with('success', 'Bandwidth plan berhasil dihapus.');
    }
}
