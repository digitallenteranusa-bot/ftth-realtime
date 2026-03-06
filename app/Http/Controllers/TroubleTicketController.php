<?php
namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\TroubleTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TroubleTicketController extends Controller
{
    public function index(Request $request)
    {
        $query = TroubleTicket::with(['customer', 'assignedUser']);
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Ticket/Index', [
            'tickets' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Ticket/Create', [
            'customers' => Customer::where('status', 'active')->get(['id', 'name']),
            'operators' => User::whereIn('role', ['admin', 'operator'])->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $validated['ticket_number'] = 'TT-' . date('Ymd') . '-' . str_pad(TroubleTicket::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
        $validated['status'] = 'open';

        TroubleTicket::create($validated);
        return redirect()->route('tickets.index')->with('success', 'Tiket berhasil dibuat.');
    }

    public function show(TroubleTicket $ticket)
    {
        return Inertia::render('Ticket/Show', [
            'ticket' => $ticket->load(['customer', 'assignedUser']),
        ]);
    }

    public function update(Request $request, TroubleTicket $ticket)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:open,in_progress,resolved,closed',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'assigned_to' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        if (isset($validated['status'])) {
            if ($validated['status'] === 'resolved') $validated['resolved_at'] = now();
            if ($validated['status'] === 'closed') $validated['closed_at'] = now();
        }

        $ticket->update($validated);
        return redirect()->back()->with('success', 'Tiket berhasil diupdate.');
    }

    public function destroy(TroubleTicket $ticket)
    {
        $ticket->delete();
        return redirect()->route('tickets.index')->with('success', 'Tiket berhasil dihapus.');
    }
}
