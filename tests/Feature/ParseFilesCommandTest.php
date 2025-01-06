<?php

namespace Tests\Feature;

use App\Models\ParsedItem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ParseFilesCommandTest extends TestCase
{
    use RefreshDatabase;
}
