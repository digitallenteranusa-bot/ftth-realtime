<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\FiberRoute;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Ont;
use App\Models\PonPort;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OntController extends Controller
{
    public function index(Request $request)
    {
        $query = Ont::with(['odp', 'customer', 'olt', 'ponPort']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('serial_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        return Inertia::render('Ont/Index', [
            'onts' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Ont/Create', [
            'odps' => Odp::where('is_active', true)->get(['id', 'name']),
            'customers' => Customer::with(['onts' => fn($q) => $q->select('id', 'customer_id', 'odp_id', 'olt_id', 'pon_port_id', 'name', 'serial_number', 'ont_id_number', 'lat', 'lng')])
                ->where('status', 'active')->get(['id', 'name', 'lat', 'lng']),
            'olts' => Olt::where('is_active', true)->get(['id', 'name']),
            'ponPorts' => PonPort::with('olt:id,name')->where('is_active', true)->get(['id', 'olt_id', 'slot', 'port']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'odp_id' => 'nullable|exists:odps,id',
            'customer_id' => 'nullable|exists:customers,id',
            'olt_id' => 'nullable|exists:olts,id',
            'pon_port_id' => 'nullable|exists:pon_ports,id',
            'name' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|unique:onts',
            'ont_id_number' => 'nullable|integer',
            'status' => 'nullable|in:online,offline,los,dyinggasp,unknown',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
        ]);

        $ont = Ont::create($validated);

        return redirect()->route('onts.index')->with('success', 'ONT berhasil ditambahkan.');
    }

    public function show(Ont $ont)
    {
        return Inertia::render('Ont/Show', [
            'ont' => $ont->load(['odp.odc', 'customer', 'olt', 'ponPort']),
        ]);
    }

    public function edit(Ont $ont)
    {
        return Inertia::render('Ont/Edit', [
            'ont' => $ont,
            'odps' => Odp::where('is_active', true)->get(['id', 'name']),
            'customers' => Customer::with(['onts' => fn($q) => $q->select('id', 'customer_id', 'odp_id', 'olt_id', 'pon_port_id', 'name', 'serial_number', 'ont_id_number', 'lat', 'lng')])
                ->where('status', 'active')->get(['id', 'name', 'lat', 'lng']),
            'olts' => Olt::where('is_active', true)->get(['id', 'name']),
            'ponPorts' => PonPort::with('olt:id,name')->where('is_active', true)->get(['id', 'olt_id', 'slot', 'port']),
        ]);
    }

    public function update(Request $request, Ont $ont)
    {
        $validated = $request->validate([
            'odp_id' => 'nullable|exists:odps,id',
            'customer_id' => 'nullable|exists:customers,id',
            'olt_id' => 'nullable|exists:olts,id',
            'pon_port_id' => 'nullable|exists:pon_ports,id',
            'name' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|unique:onts,serial_number,' . $ont->id,
            'ont_id_number' => 'nullable|integer',
            'status' => 'nullable|in:online,offline,los,dyinggasp,unknown',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string',
        ]);

        $ont->update($validated);
        return redirect()->route('onts.index')->with('success', 'ONT berhasil diupdate.');
    }

    public function destroy(Ont $ont)
    {
        FiberRoute::where('destination_type', 'ont')->where('destination_id', $ont->id)->delete();
        $ont->delete();
        return redirect()->route('onts.index')->with('success', 'ONT berhasil dihapus.');
    }
}
