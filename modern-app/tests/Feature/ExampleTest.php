<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ensure the rebuilt landing page renders on a clean schema.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('เว็บบอร์ดหลัก');
        $response->assertSee('ยังไม่มีการนำเข้าห้องสนทนา');
        $response->assertSee('legacy-topnav__locale-text">TH', false);
        $response->assertSee('legacy-topnav__locale-text">EN', false);
        $response->assertSee('images/peoplecine-logo.png', false);
        $response->assertSee('window.peoplecineTinyMceBase = "\/vendor\/tinymce"', false);
        $response->assertSee('<script src="/vendor/tinymce/tinymce.min.js" defer></script>', false);
    }
}
