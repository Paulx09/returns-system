<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and apply OWASP recommended security headers.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'credentialless');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set(
            'Content-Security-Policy',
            $this->buildContentSecurityPolicy($nonce)
        );

        $response->headers->remove('X-Powered-By');

        if ($response->isRedirection()) {
            $response->setContent('');
        }

        return $response;
    }

    /**
     * Build the Content-Security-Policy header value.
     *
     * Relaxes style/connect/script directives only when running locally with the
     * Vite HMR dev server active so runtime-injected styles and WebSockets work.
     */
    private function buildContentSecurityPolicy(string $nonce): string
    {
        if (app()->isLocal() && Vite::isRunningHot()) {
            [$httpOrigins, $wsOrigins] = $this->viteDevOrigins();

            return implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}' {$httpOrigins}",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net {$httpOrigins}",
                "font-src 'self' https://fonts.bunny.net {$httpOrigins} data:",
                "img-src 'self' data: blob: {$httpOrigins}",
                "connect-src 'self' {$httpOrigins} {$wsOrigins}",
                "frame-ancestors 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-src 'self'",
            ]).';';
        }

        return "default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' https://fonts.bunny.net; font-src 'self' https://fonts.bunny.net; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-src 'self';";
    }

    /**
     * Derive HTTP and WebSocket origins for the active Vite dev server.
     *
     * @return array{0: string, 1: string}
     */
    private function viteDevOrigins(): array
    {
        $hotFile = Vite::hotFile();
        $hotUrl = is_file($hotFile) ? trim((string) file_get_contents($hotFile)) : '';
        $parsed = $hotUrl !== '' ? parse_url($hotUrl) : false;

        $scheme = is_array($parsed) && isset($parsed['scheme']) ? $parsed['scheme'] : 'http';
        $port = is_array($parsed) && isset($parsed['port']) ? (int) $parsed['port'] : 5173;
        $wsScheme = $scheme === 'https' ? 'wss' : 'ws';

        $hosts = array_unique(array_filter([
            is_array($parsed) ? ($parsed['host'] ?? null) : null,
            'localhost',
            '127.0.0.1',
            '[::1]',
        ]));

        $httpOrigins = array_map(fn (string $host) => "{$scheme}://{$host}:{$port}", $hosts);
        $wsOrigins = array_map(fn (string $host) => "{$wsScheme}://{$host}:{$port}", $hosts);

        return [implode(' ', $httpOrigins), implode(' ', $wsOrigins)];
    }
}
