<?php

namespace Tests\Feature;

use App\Support\LegacyHtmlFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_responses_include_security_headers(): void
    {
        $response = $this->get('/eula');

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy', "object-src 'none'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'");
    }

    public function test_https_responses_enable_hsts(): void
    {
        $response = $this->withHeaders(['X-Forwarded-Proto' => 'https'])->get('/eula');

        $response->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_rich_text_removes_script_capable_markup_and_editor_metadata(): void
    {
        $html = '<script>alert(1)</script>'
            . '<p onclick="alert(1)" data-mce-href="javascript:alert(1)">Text</p>'
            . '<a href="javascript:alert(1)">Link</a>'
            . '<img src="javascript:alert(1)" onerror="alert(1)">';

        $sanitized = LegacyHtmlFormatter::linkify($html);

        $this->assertStringNotContainsStringIgnoringCase('<script', $sanitized);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $sanitized);
        $this->assertStringNotContainsStringIgnoringCase('onerror', $sanitized);
        $this->assertStringNotContainsStringIgnoringCase('data-mce-', $sanitized);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $sanitized);
        $this->assertStringContainsString('Text', $sanitized);
    }
}
