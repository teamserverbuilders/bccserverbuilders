<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('user:id,name')
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->event, fn($q) => $q->where('event', 'like', "%{$request->event}%"))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest();

        return response()->json($query->paginate($request->per_page ?? 50));
    }

    public function loginLogs(Request $request)
    {
        $query = LoginLog::with('user:id,name')
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->action, fn($q) => $q->where('action', $request->action))
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest();

        return response()->json($query->paginate($request->per_page ?? 50));
    }

    public function activityLogs(Request $request)
    {
        $query = ActivityLog::with('user:id,name')
            ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
            ->latest();

        return response()->json($query->paginate($request->per_page ?? 50));
    }
}

