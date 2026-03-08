<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('model_type', 'like', "%{$search}%")
                  ->orWhere('model_id', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $modelTypes = AuditLog::distinct()->pluck('model_type')->sort()->values();
        $users = User::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('AuditLog/Index', [
            'logs' => $query->latest()->paginate(30)->withQueryString(),
            'filters' => $request->only(['action', 'model_type', 'user_id', 'search']),
            'modelTypes' => $modelTypes,
            'users' => $users,
        ]);
    }
}
