<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Odp;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NearbyController extends Controller
{
    public function index()
    {
        return Inertia::render('Nearby/Index');
    }

    public function search(Request $request)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:50',
            'type' => 'nullable|in:all,odp,customer',
        ]);

        $lat = $request->lat;
        $lng = $request->lng;
        $radius = $request->radius ?? 5; // default 5 km
        $type = $request->type ?? 'all';

        $results = ['odps' => [], 'customers' => []];

        // Haversine formula for distance in km
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat))))";

        if ($type === 'all' || $type === 'odp') {
            $results['odps'] = Odp::select('odps.*')
                ->selectRaw("{$haversine} AS distance", [$lat, $lng, $lat])
                ->with('odc:id,name')
                ->withCount('onts')
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->having('distance', '<=', $radius)
                ->orderBy('distance')
                ->limit(50)
                ->get();
        }

        if ($type === 'all' || $type === 'customer') {
            $results['customers'] = Customer::select('customers.*')
                ->selectRaw("{$haversine} AS distance", [$lat, $lng, $lat])
                ->whereNotNull('lat')
                ->whereNotNull('lng')
                ->having('distance', '<=', $radius)
                ->orderBy('distance')
                ->limit(50)
                ->get();
        }

        return response()->json($results);
    }
}
