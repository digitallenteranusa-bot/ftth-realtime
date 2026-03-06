<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Customer/Index', [
            'customers' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Customer/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'nik' => 'nullable|string|unique:customers|max:20',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'status' => 'nullable|in:active,inactive,suspended',
            'notes' => 'nullable|string',
        ]);

        Customer::create($validated);
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
        return Inertia::render('Customer/Edit', ['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'nik' => 'nullable|string|max:20|unique:customers,nik,' . $customer->id,
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'status' => 'nullable|in:active,inactive,suspended',
            'notes' => 'nullable|string',
        ]);

        $customer->update($validated);
        return redirect()->route('customers.index')->with('success', 'Customer berhasil diupdate.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', 'Customer berhasil dihapus.');
    }
}
