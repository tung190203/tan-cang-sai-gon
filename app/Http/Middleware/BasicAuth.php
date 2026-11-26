<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuth
{
    /**
     * Enforce HTTP Basic Authentication when enabled in security config.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.basic_auth.enabled', false)) {
            return $next($request);
        }

        foreach (config('security.basic_auth.except', []) as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        $expectedUser = (string) config('security.basic_auth.username', '');
        $expectedPassword = (string) config('security.basic_auth.password', '');

        if ($expectedUser === '' || $expectedPassword === '') {
            return $this->unauthorizedResponse();
        }

        $providedUser = $request->getUser();
        $providedPassword = $request->getPassword();

        if (
            $providedUser === null
            || $providedPassword === null
            || ! hash_equals($expectedUser, $providedUser)
            || ! hash_equals($expectedPassword, $providedPassword)
        ) {
            return $this->unauthorizedResponse();
        }

        return $next($request);
    }

    private function unauthorizedResponse(): Response
    {
        $realm = addslashes((string) config('security.basic_auth.realm', 'Restricted'));

        return response('Unauthorized.', 401, [
            'WWW-Authenticate' => 'Basic realm="' . $realm . '", charset="UTF-8"',
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
