<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class AuditAdminActions
{
    public function handle(Request $request, Closure $next)
    {
        if (! in_array($request->method(), ['POST','PUT','PATCH','DELETE'], true) || ! Schema::hasTable('audit_logs')) {
            return $next($request);
        }

        $subject = $this->subject($request);
        $before = $subject ? $subject->toArray() : null;
        $response = $next($request);

        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        $after = null;
        if ($subject && $subject->exists) {
            $subject->refresh();
            $after = $subject->toArray();
        }

        AuditLog::create([
            'user_id' => optional($request->user())->id,
            'action' => $this->action($request),
            'route_name' => optional($request->route())->getName(),
            'method' => $request->method(),
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'before' => $before,
            'after' => $after,
            'metadata' => ['input' => $this->sanitizedInput($request)],
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'created_at' => now(),
        ]);

        return $response;
    }

    private function subject(Request $request): ?Model
    {
        foreach ((array) optional($request->route())->parameters() as $value) {
            if ($value instanceof Model) {
                return $value;
            }
        }
        return null;
    }

    private function action(Request $request): string
    {
        $route = (string) optional($request->route())->getName();
        return match (true) {
            str_contains($route, 'refund') => 'refund',
            str_contains($route, 'cancel') => 'cancel',
            str_contains($route, 'redeem') => 'redeem',
            str_contains($route, 'water') => 'water_measurement',
            str_contains($route, 'wallet') => 'wallet_adjustment',
            str_contains($route, 'attendance') => 'attendance_update',
            str_contains($route, 'progress') => 'progress_update',
            $request->isMethod('delete') => 'delete',
            $request->isMethod('post') => 'create_or_action',
            default => 'update',
        };
    }

    private function sanitizedInput(Request $request): array
    {
        $data = Arr::except($request->all(), ['_token','password','password_confirmation','token','secret','api_key']);

        return $this->sanitizeValue($data);
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) {
            return [
                'uploaded_file' => true,
                'name' => $value->getClientOriginalName(),
                'mime' => $value->getClientMimeType(),
                'size' => $value->getSize(),
            ];
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->sanitizeValue($item);
            }

            return $value;
        }

        if (is_string($value) && mb_strlen($value) > 2000) {
            return mb_substr($value, 0, 2000).'…';
        }

        if (is_null($value) || is_scalar($value)) {
            return $value;
        }

        return '['.get_debug_type($value).']';
    }
}
