<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /**
     * Sets up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Additional setup can be placed here if needed
    }

    /**
     * Tears down the test environment.
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
