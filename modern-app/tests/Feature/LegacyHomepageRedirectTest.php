<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyHomepageRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_homepage_redirects_permanently_to_the_landing_page(): void
    {
        $this->get('/wboard/default.php')
            ->assertStatus(301)
            ->assertRedirect(route('landing'));
    }

    public function test_legacy_homepage_supports_head_requests(): void
    {
        $this->head('/wboard/default.php')
            ->assertStatus(301)
            ->assertRedirect(route('landing'));
    }

    public function test_legacy_homepage_discards_old_query_parameters_and_accepts_a_trailing_slash(): void
    {
        foreach (['/wboard/default.php?room=150', '/wboard/default.php/'] as $url) {
            $this->get($url)
                ->assertStatus(301)
                ->assertRedirect(route('landing'));
        }
    }

    public function test_other_missing_pages_still_redirect_to_the_landing_page(): void
    {
        $this->get('/no-such-peoplecine-page')
            ->assertRedirect(route('landing'))
            ->assertSessionHas('header_error');
    }

    public function test_missing_assets_and_json_requests_keep_their_not_found_status(): void
    {
        $this->get('/images/no-such-peoplecine-image.png')->assertNotFound();
        $this->getJson('/no-such-peoplecine-page')->assertNotFound();
    }
}
