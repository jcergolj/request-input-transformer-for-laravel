<?php

namespace Jcergolj\RequestPreprocessor;

use Illuminate\Support\ServiceProvider;
use Jcergolj\RequestPreprocessor\Commands\MakeModifierCommand;
use Jcergolj\RequestPreprocessor\Commands\MakeTransformerCommand;

class RequestPreprocessorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([MakeModifierCommand::class]);

        $this->commands([MakeTransformerCommand::class]);
    }

    public function boot(): void {}
}
