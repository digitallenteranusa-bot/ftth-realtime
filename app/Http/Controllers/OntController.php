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
            'customers' => Customer::where('status', 'active')->get(['id', 'name']),
            'olts' => Olt::where('is_active', true)->get(['id', 'name']),
            'ponPorts' => PonPort::with('olt:id,name')->where('is_active', true)->get(['id', 'olt_id', 'slot', 'port']),
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'odp_id' => 'nullable|exists:odps,id',
            'customer_id' => 'nullable|exists:customers,id',
            'olt_id' => 'nullable|exists:olts,id',
            'pon_port_id' => 'nullable|exists:pon_ports,id',
            'name' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|unique:onts',
            'ont_id_number' => 'nullable|integer',
            'status' => 'nullable|in:online,offline,los,dyinggasp,unknown',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'customer_mode' => 'nullable|string',
        ];

        // Validate new customer fields if creating new customer
        if ($request->customer_mode === 'new') {
            $rules['customer_name'] = 'required|string|max:255';
            $rules['customer_phone'] = 'required|string|max:20';
            $rules['customer_address'] = 'required|string';
            $rules['customer_bandwidth'] = 'nullable|string|max:20';
        }

        $validated = $request->validate($rules);

        // Create new customer if mode is 'new'
        if ($request->customer_mode === 'new') {
            $customer = Customer::create([
                'name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'],
                'address' => $validated['customer_address'],
                'bandwidth' => $validated['customer_bandwidth'] ?? null,
                'lat' => $validated['lat'] ?? null,
                'lng' => $validated['lng'] ?? null,
                'status' => 'active',
            ]);
            $validated['customer_id'] = $customer->id;
        }

        // Remove customer form fields before creating ONT
        unset($validated['customer_mode'], $validated['customer_name'], $validated['customer_phone'], $validated['customer_address'], $validated['customer_bandwidth']);

        $ont = Ont::create($validated);

        // Auto-create fiber route ODP → ONT
        if ($ont->odp_id && $ont->lat && $ont->lng) {
            $odp = Odp::find($ont->odp_id);
            if ($odp) {
                FiberRoute::create([
                    'name' => "Drop {$odp->name} - " . ($ont->name ?: $ont->serial_number),
                    'source_type' => 'odp',
                    'source_id' => $odp->id,
                    'destination_type' => 'ont',
                    'destination_id' => $ont->id,
                    'coordinates' => [
                        [$odp->lat, $odp->lng],
                        [$ont->lat, $ont->lng],
                    ],
                    'color' => '#ff9933',
                    'status' => 'active',
                ]);
            }
        }

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
            'customers' => Customer::where('status', 'active')->get(['id', 'name']),
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
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
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
