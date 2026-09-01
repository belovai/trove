<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class TrustedProxyTest extends TestCase
{
    /**
     * Behind a TLS-terminating reverse proxy (Traefik, nginx, Caddy) the app
     * itself is spoken to over plain HTTP, and only X-Forwarded-Proto says the
     * client used HTTPS. Without a trusted proxy Laravel ignores that header
     * and builds every absolute URL with the http scheme: the browser blocks
     * the assets as mixed content, and a redirect points at a port the proxy
     * does not even serve.
     */
    public function test_forwarded_proto_decides_the_scheme_of_generated_urls(): void
    {
        $this->withoutVite();

        $this->get('/', ['X-Forwarded-Proto' => 'https'])->assertOk();

        $this->assertStringStartsWith('https://', url('/build/assets/app.css'));
    }

    public function test_the_scheme_is_http_without_the_header(): void
    {
        $this->withoutVite();

        // The flip side of the test above: trusting the proxy must not hardcode
        // https, or a plain-HTTP deployment would generate unreachable URLs.
        $this->get('/')->assertOk();

        $this->assertStringStartsWith('http://', url('/build/assets/app.css'));
    }
}
