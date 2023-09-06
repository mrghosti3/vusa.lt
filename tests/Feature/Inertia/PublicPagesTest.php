<?php

namespace Tests\Feature\Inertia;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\CoversNothing;
// use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

#[CoversNothing]
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    // check if public inertia response returns default props
    public function test_home_page_gets_default_public_props(): void
    {
        $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/HomePage')
                ->has('app', fn (Assert $page) => $page
                    ->has('env')
                    ->has('locale')
                    ->has('path')
                    ->has('url')
                )
                ->has('mainNavigation')
                ->has('padaliniai')
                ->has('padalinys', fn (Assert $page) => $page
                    ->has('alias')
                    ->has('banners')
                    ->has('id')
                    ->has('shortname')
                    ->has('type')
                    ->has('subdomain')
                    ->has('links')
                )
            );
    }

    // check if public inertia response doesn't return any auth

    public function test_no_auth_without_authentication(): void
    {
        $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/HomePage')
                ->where('auth', null)
            );
    }

    public function test_can_open_the_home_page(): void
    {
        $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/HomePage')
                ->has('news')
                ->has('calendar')
                ->has('banners')
            );
    }
}
