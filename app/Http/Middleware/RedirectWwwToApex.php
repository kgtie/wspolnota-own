<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectWwwToApex
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $canonicalHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (
            is_string($host) &&
            is_string($canonicalHost) &&
            str_starts_with($host, 'www.') &&
            substr($host, 4) === $canonicalHost
        ) {
            return redirect()->to(
                $request->getScheme().'://'.$canonicalHost.$request->getRequestUri(),
                301
            );
        }

        return $next($request);
    }
}
