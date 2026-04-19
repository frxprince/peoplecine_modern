<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookieConsentBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_time_visitor_sees_cookie_consent_banner(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Cookie Notice');
        $response->assertSee('data-cookie-consent-banner', false);
        $response->assertSee('cookie-consent.js');
    }

    public function test_banner_is_hidden_after_cookie_consent_is_present(): void
    {
        $response = $this->withCookie('peoplecine_cookie_consent', 'accepted')
            ->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Cookie Notice');
        $response->assertDontSee('data-cookie-consent-banner', false);
    }
}
