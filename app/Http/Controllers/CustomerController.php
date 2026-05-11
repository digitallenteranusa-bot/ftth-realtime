<?php
namespace App\Http\Controllers;

use App\Models\BandwidthPlan;
use App\Models\Customer;
use App\Models\FiberRoute;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Ont;
use App\Models\PonPort;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount('onts');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Customer/Index', [
            'customers' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Customer/Create', [
            'odps' => Odp::where('is_active', true)->get(['id', 'name']),
            'olts' => Olt::where('is_active', true)->get(['id', 'name']),
            'ponPorts' => PonPort::with('olt:id,name')->where('is_active', true)->get(['id', 'olt_id', 'slot', 'port']),
            'bandwidthPlans' => BandwidthPlan::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'bandwidth' => 'nullable|string|max:20',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'status' => 'nullable|in:active,inactive,suspended',
            'notes' => 'nullable|string',
            // ONT fields
            'ont_name' => 'nullable|string|max:255',
            'ont_serial_number' => 'nullable|string|unique:onts,serial_number',
            'odp_id' => 'nullable|exists:odps,id',
            'olt_id' => 'nullable|exists:olts,id',
            'pon_port_id' => 'nullable|exists:pon_ports,id',
            'ont_id_number' => 'nullable|integer',
        ]);

        $customer = Customer::create([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'bandwidth' => $validated['bandwidth'] ?? null,
            'lat' => $validated['lat'] ?? null,
            'lng' => $validated['lng'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        // Create ONT linked to customer
        $hasOntData = !empty($validated['ont_name']) || !empty($validated['ont_serial_number'])
            || !empty($validated['odp_id']) || !empty($validated['olt_id'])
            || !empty($validated['pon_port_id']) || !empty($validated['ont_id_number']);
        if ($hasOntData) {
            $ont = Ont::create([
                'customer_id' => $customer->id,
                'name' => $validated['ont_name'] ?? null,
                'serial_number' => $validated['ont_serial_number'] ?? null,
                'odp_id' => $validated['odp_id'] ?? null,
                'olt_id' => $validated['olt_id'] ?? null,
                'pon_port_id' => $validated['pon_port_id'] ?? null,
                'ont_id_number' => $validated['ont_id_number'] ?? null,
                'lat' => $validated['lat'] ?? null,
                'lng' => $validated['lng'] ?? null,
                'status' => 'unknown',
            ]);
        }

        return redirect()->route('customers.index')->with('success', 'Customer berhasil ditambahkan.');
    }

    public function show(Customer $customer)
    {
        return Inertia::render('Customer/Show', [
            'customer' => $customer->load(['onts.odp', 'troubleTickets']),
        ]);
    }

    public function edit(Customer $customer)
    {
        return Inertia::render('Customer/Edit', [
            'customer' => $customer->load('onts'),
            'odps' => Odp::where('is_active', true)->get(['id', 'name']),
            'olts' => Olt::where('is_active', true)->get(['id', 'name']),
            'ponPorts' => PonPort::with('olt:id,name')->where('is_active', true)->get(['id', 'olt_id', 'slot', 'port']),
            'bandwidthPlans' => BandwidthPlan::where('is_active', true)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'bandwidth' => 'nullable|string|max:20',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'status' => 'nullable|in:active,inactive,suspended',
            'notes' => 'nullable|string',
            // ONT fields
            'ont_name' => 'nullable|string|max:255',
            'ont_serial_number' => 'nullable|string',
            'odp_id' => 'nullable|exists:odps,id',
            'olt_id' => 'nullable|exists:olts,id',
            'pon_port_id' => 'nullable|exists:pon_ports,id',
            'ont_id_number' => 'nullable|integer',
        ]);

        $customer->update([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'phone' => $validated['phone'],
            'bandwidth' => $validated['bandwidth'] ?? null,
            'lat' => $validated['lat'] ?? null,
            'lng' => $validated['lng'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'notes' => $validated['notes'] ?? null,
        ]);

        // Update or create ONT
        $ont = $customer->onts()->first();
        $ontData = [
            'customer_id' => $customer->id,
            'name' => $validated['ont_name'] ?? null,
            'serial_number' => $validated['ont_serial_number'] ?? null,
            'odp_id' => $validated['odp_id'] ?? null,
            'olt_id' => $validated['olt_id'] ?? null,
            'pon_port_id' => $validated['pon_port_id'] ?? null,
            'ont_id_number' => $validated['ont_id_number'] ?? null,
            'lat' => $validated['lat'] ?? null,
            'lng' => $validated['lng'] ?? null,
        ];

        $hasOntData = !empty($validated['ont_name']) || !empty($validated['ont_serial_number'])
            || !empty($validated['odp_id']) || !empty($validated['olt_id'])
            || !empty($validated['pon_port_id']) || !empty($validated['ont_id_number']);

        if ($ont) {
            // Validate unique serial number excluding current ONT
            if (!empty($validated['ont_serial_number'])) {
                $exists = Ont::where('serial_number', $validated['ont_serial_number'])
                    ->where('id', '!=', $ont->id)->exists();
                if ($exists) {
                    return back()->withErrors(['ont_serial_number' => 'Serial number sudah digunakan.']);
                }
            }
            $ont->update($ontData);
        } elseif ($hasOntData) {
            // Validate unique serial number for new ONT
            if (!empty($validated['ont_serial_number'])) {
                $exists = Ont::where('serial_number', $validated['ont_serial_number'])->exists();
                if ($exists) {
                    return back()->withErrors(['ont_serial_number' => 'Serial number sudah digunakan.']);
                }
            }
            $ont = Ont::create(array_merge($ontData, ['status' => 'unknown']));
        }

        return redirect()->route('customers.index')->with('success', 'Customer berhasil diupdate.');
    }

    public function destroy(Customer $customer)
    {
        // Delete related ONTs and their fiber routes
        foreach ($customer->onts as $ont) {
            FiberRoute::where('destination_type', 'ont')->where('destination_id', $ont->id)->delete();
            $ont->delete();
        }
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer berhasil dihapus.');
    }
}
