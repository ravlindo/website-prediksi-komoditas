<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;

class AuditAdminActivity
{
    public function handle(Request $request, Closure $next)
    {
        $userBefore = $request->user();
        $response = $next($request);
        $user = $userBefore ?? $request->user();
        if ($user && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            ActivityLog::create([
                'user_id' => $user->id, 'user_name' => $user->name,
                'event' => $this->event($request), 'route_name' => $request->route()?->getName(),
                'method' => $request->method(), 'path' => $request->path(), 'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'response_status' => $response->getStatusCode(),
                'metadata' => ['resources' => collect($request->route()?->parameters() ?? [])->map(fn ($value) => is_object($value) && isset($value->id) ? $value->id : $value)->all()],
            ]);
        }

        return $response;
    }

    private function event(Request $request): string
    {
        $name = (string) $request->route()?->getName();

        return str_contains($name, 'destroy') || $request->isMethod('delete') ? 'delete' : (str_contains($name, 'restore') ? 'restore' : (str_contains($name, 'import') ? 'import' : (str_contains($name, 'login') ? 'login' : (str_contains($name, 'logout') ? 'logout' : 'change'))));
    }
}
