<?php

namespace App\Http\Controllers;

use App\Models\Olt;
use App\Models\PonPort;
use Illuminate\Http\Request;

class PonPortController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'olt_id' => 'required|exists:olts,id',
            'slot' => 'required|integer|min:0',
            'port' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        PonPort::create($validated);
        return redirect()->back()->with('success', 'PON Port berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $ponPort = PonPort::findOrFail($id);
        $ponPort->delete();
        return redirect()->back()->with('success', 'PON Port berhasil dihapus.');
    }
}
