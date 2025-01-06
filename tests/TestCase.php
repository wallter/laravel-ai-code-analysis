<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    
    //
}
