<?php
namespace App\Http\Controllers;

use App\Models\Alarm;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlarmController extends Controller
{
    public function index(Request $request)
    {
        $query = Alarm::query();
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }
        if ($request->boolean('unresolved', false)) {
            $query->where('is_resolved', false);
        }

        return Inertia::render('Alarm/Index', [
            'alarms' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['severity', 'unresolved']),
        ]);
    }

    public function resolve(Alarm $alarm, Request $request)
    {
        $alarm->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Alarm resolved.');
    }
}
