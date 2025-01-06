<?php 

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
// use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use RefreshDatabase;
}