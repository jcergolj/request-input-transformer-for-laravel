<?php

namespace Jcergolj\RequestPreprocessor\Tests;

use Jcergolj\RequestPreprocessor\RequestPreprocessorServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [RequestPreprocessorServiceProvider::class];
    }
}
