<?php
namespace App\Http\Controllers;

use App\Models\FiberRoute;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FiberRouteController extends Controller
{
    public function index()
    {
        return Inertia::render('FiberRoute/Index', [
            'fiberRoutes' => FiberRoute::latest()->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'source_type' => 'required|string',
            'source_id' => 'required|integer',
            'destination_type' => 'required|string',
            'destination_id' => 'required|integer',
            'coordinates' => 'required|array',
            'color' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive,maintenance',
            'notes' => 'nullable|string',
        ]);

        FiberRoute::create($validated);
        return redirect()->back()->with('success', 'Fiber route berhasil ditambahkan.');
    }

    public function update(Request $request, FiberRoute $fiberRoute)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'coordinates' => 'required|array',
            'color' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive,maintenance',
            'notes' => 'nullable|string',
        ]);

        $fiberRoute->update($validated);
        return redirect()->back()->with('success', 'Fiber route berhasil diupdate.');
    }

    public function destroy(FiberRoute $fiberRoute)
    {
        $fiberRoute->delete();
        return redirect()->back()->with('success', 'Fiber route berhasil dihapus.');
    }
}
