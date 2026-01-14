<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * Log an audit event.
     */
    public static function log(
        Model $model,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): AuditLog {
        $user = Auth::user();
        $description = $description ?? self::generateDescription($model, $action);

        // Create audit log entry
        $auditLog = AuditLog::create([
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'action' => $action,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);

        // Log to Laravel audit log channel for financial transactions
        $modelName = class_basename($model);
        if (in_array($modelName, ['Transaction', 'JournalEntry', 'Account'])) {
            Log::channel('financial')->info("Financial {$action}: {$modelName}", [
                'audit_log_id' => $auditLog->id,
                'model_id' => $model->id,
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'description' => $description,
            ]);
        } else {
            Log::channel('audit')->info("{$action}: {$modelName}", [
                'audit_log_id' => $auditLog->id,
                'model_id' => $model->id,
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'description' => $description,
            ]);
        }

        return $auditLog;
    }

    /**
     * Log creation of a model.
     */
    public static function logCreate(Model $model, ?string $description = null): AuditLog
    {
        return self::log(
            $model,
            'created',
            null,
            $model->getAttributes(),
            $description
        );
    }

    /**
     * Log update of a model.
     */
    public static function logUpdate(Model $model, array $oldValues, ?string $description = null): AuditLog
    {
        return self::log(
            $model,
            'updated',
            $oldValues,
            $model->getAttributes(),
            $description
        );
    }

    /**
     * Log deletion of a model.
     */
    public static function logDelete(Model $model, ?string $description = null): AuditLog
    {
        return self::log(
            $model,
            'deleted',
            $model->getAttributes(),
            null,
            $description
        );
    }

    /**
     * Generate a description for the audit log.
     */
    protected static function generateDescription(Model $model, string $action): string
    {
        $modelName = class_basename($model);
        
        $descriptions = [
            'created' => "{$modelName} was created",
            'updated' => "{$modelName} was updated",
            'deleted' => "{$modelName} was deleted",
        ];

        return $descriptions[$action] ?? "{$modelName} action: {$action}";
    }

    /**
     * Get audit logs for a specific model.
     */
    public static function getLogsFor(Model $model, ?int $limit = null)
    {
        $query = AuditLog::where('auditable_type', get_class($model))
            ->where('auditable_id', $model->id)
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get audit logs for a user.
     */
    public static function getLogsForUser(int $userId, ?int $limit = null)
    {
        $query = AuditLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
