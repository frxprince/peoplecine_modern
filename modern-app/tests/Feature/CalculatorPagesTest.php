<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculatorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_calculator_menu_links(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('เครื่องคิดเลข');
        $response->assertSee(route('calculator.throw'), false);
        $response->assertSee(route('calculator.lenssim'), false);
        $response->assertSee(route('calculator.screendesign'), false);
    }

    public function test_calculator_pages_render_successfully(): void
    {
        $this->get(route('calculator.throw'))
            ->assertOk()
            ->assertSee('คำนวณระยะฉาย')
            ->assertSee('หน้าถัดไป');

        $this->get(route('calculator.throw.screen', ['screen' => 'scope', 'print_format' => 'both']))
            ->assertOk()
            ->assertSee('คำนวณระยะฉายสำหรับจอสโคป')
            ->assertSee('สโคป + ตัดซีน');

        $this->get(route('calculator.lenssim'))
            ->assertOk()
            ->assertSee('จำลองขนาดภาพจากคู่เลนส์');

        $this->get(route('calculator.screendesign'))
            ->assertOk()
            ->assertSee('คำนวณขนาดจอ')
            ->assertSee('โปรเจคเตอร์');
    }

    public function test_calculator_pages_render_in_english_when_locale_is_english(): void
    {
        $this->withCookie('peoplecine_locale', 'en')
            ->get(route('calculator.throw'))
            ->assertOk()
            ->assertSee('Throw Distance Calculator')
            ->assertSee('Next');

        $this->withCookie('peoplecine_locale', 'en')
            ->get(route('calculator.throw.screen', ['screen' => 'scope', 'print_format' => 'both']))
            ->assertOk()
            ->assertSee('Throw Distance Calculator for Scope Screen')
            ->assertSee('Scope + Flat');

        $this->withCookie('peoplecine_locale', 'en')
            ->get(route('calculator.lenssim'))
            ->assertOk()
            ->assertSee('Lens Pair Image Simulator');

        $this->withCookie('peoplecine_locale', 'en')
            ->get(route('calculator.screendesign'))
            ->assertOk()
            ->assertSee('Screen Size Calculator')
            ->assertSee('Projector Widescreen');
    }
}