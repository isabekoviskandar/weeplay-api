<?php

it('returns CORS headers for requests from an allowed origin', function () {
    $this->withHeader('Origin', 'https://weeplay.isabekoff.uz')
        ->get('/up')
        ->assertOk()
        ->assertHeader('Access-Control-Allow-Origin', 'https://weeplay.isabekoff.uz')
        ->assertHeader('Access-Control-Allow-Credentials', 'true')
        ->assertHeader('Vary', 'Origin');
});

it('answers an allowed API preflight request with CORS headers', function () {
    $this->withHeaders([
        'Origin' => 'https://weeplay.isabekoff.uz',
        'Access-Control-Request-Method' => 'GET',
        'Access-Control-Request-Headers' => 'authorization, content-type',
    ])->options('/api/v1/get-latest-categories')
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'https://weeplay.isabekoff.uz')
        ->assertHeader('Access-Control-Allow-Headers', 'authorization, content-type');
});

it('rejects a preflight request from an untrusted origin', function () {
    $this->withHeader('Origin', 'https://untrusted.example')
        ->options('/api/v1/get-latest-categories')
        ->assertForbidden()
        ->assertHeaderMissing('Access-Control-Allow-Origin');
});
