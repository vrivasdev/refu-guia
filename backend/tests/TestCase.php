<?php

namespace Tests;

require_once __DIR__ . '/CreatesApplication.php';

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}
