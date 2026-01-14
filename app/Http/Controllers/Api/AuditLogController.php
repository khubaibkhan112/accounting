<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        // Filter by action
        if ($request->has('action') && $request->action) {
            $query->where('action', $request->action);
        }

        // Filter by user
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by auditable type
        if ($request->has('auditable_type') && $request->auditable_type) {
            $query->where('auditable_type', $request->auditable_type);
        }

        // Filter by auditable ID
        if ($request->has('auditable_id') && $request->auditable_id) {
            $query->where('auditable_id', $request->auditable_id);
        }

        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Pagination
        $perPage = min($request->get('per_page', 50), 100);
        $auditLogs = $query->paginate($perPage);

        return response()->json($auditLogs);
    }

    /**
     * Display the specified audit log.
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        $auditLog->load('user:id,name,email');

        return response()->json($auditLog);
    }

    /**
     * Get audit logs for a specific model.
     */
    public function forModel(Request $request, string $type, int $id): JsonResponse
    {
        $auditLogs = AuditLog::where('auditable_type', $type)
            ->where('auditable_id', $id)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($auditLogs);
    }
}
