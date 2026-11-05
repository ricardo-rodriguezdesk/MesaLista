<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Las pruebas nunca llaman a servicios externos (Supabase, Google, PAC)
        Http::preventStrayRequests();
        config(['services.supabase.url' => null, 'services.supabase.secret_key' => null]);
    }
}
