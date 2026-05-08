<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Desactiva la resolución del manifest de Vite en tests.
        // Los Feature tests validan lógica HTTP/negocio — no necesitan el bundle compilado.
        $this->withoutVite();
    }
}
