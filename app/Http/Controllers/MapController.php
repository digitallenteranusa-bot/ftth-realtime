<?php
namespace App\Http\Controllers;

use App\Models\FiberRoute;
use App\Models\Mikrotik;
use App\Models\Odc;
use App\Models\Odp;
use App\Models\Olt;
use App\Models\Ont;
use Inertia\Inertia;

class MapController extends Controller
{
    public function index()
    {
        return Inertia::render('Map/Index');
    }

    public function elements()
    {
        return response()->json([
            'mikrotiks' => Mikrotik::where('is_active', true)->whereNotNull('lat')->get(['id', 'name', 'host', 'lat', 'lng', 'location']),
            'olts' => Olt::where('is_active', true)->whereNotNull('lat')->get(['id', 'name', 'vendor', 'host', 'lat', 'lng', 'location']),
            'odcs' => Odc::where('is_active', true)->get(['id', 'name', 'lat', 'lng', 'capacity', 'used_ports', 'geojson_area']),
            'odps' => Odp::where('is_active', true)->get(['id', 'name', 'odc_id', 'lat', 'lng', 'capacity', 'used_ports']),
            'onts' => Ont::whereNotNull('lat')->get(['id', 'name', 'serial_number', 'status', 'lat', 'lng', 'odp_id', 'customer_id', 'rx_power']),
        ]);
    }

    public function fiberRoutes()
    {
        return response()->json(
            FiberRoute::where('status', 'active')->get(['id', 'name', 'source_type', 'source_id', 'destination_type', 'destination_id', 'coordinates', 'color', 'status'])
        );
    }
}
