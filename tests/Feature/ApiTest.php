<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use WithFaker;

    public function test_path()
    {
        $this->get('/')->assertStatus(404);
        $this->get('/api/sfsf')->assertStatus(404);
    }

    public function test_api_forbidden()
    {
        $fakeToken = $this->faker()->bothify('?????-#####');
        $this->get("/api/search?q=site:test.com&api_key=$fakeToken")
            ->assertStatus(200)
            ->assertSee('<error code="403">Forbidden</error>', false);
    }

    public function test_api_search()
    {
        $token = config('app.api_key');
        $this->get("/api/search?q=site:google.com&api_key=$token")
            ->assertStatus(200)
            ->assertSee('<found priority="all">', false);
    }
}
