<?php

namespace App\Traits;

use App\Models\AuditLog;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            self::logAudit('created', $model, null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $old = collect($model->getOriginal())->only(array_keys($model->getDirty()));
            $new = $model->getDirty();
            if (!empty($new)) {
                self::logAudit('updated', $model, $old->toArray(), $new);
            }
        });

        static::deleted(function ($model) {
            self::logAudit('deleted', $model, $model->getOriginal(), null);
        });
    }

    protected static function logAudit(string $action, $model, ?array $old, ?array $new): void
    {
        // Remove sensitive fields from audit log
        $hidden = ['password', 'api_password', 'remember_token'];
        foreach ($hidden as $field) {
            if (isset($old[$field])) $old[$field] = '***';
            if (isset($new[$field])) $new[$field] = '***';
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => request()?->ip(),
        ]);
    }
}
