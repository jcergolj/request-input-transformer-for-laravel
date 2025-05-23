<?php

namespace Jcergolj\RequestPreprocessor\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeModifierCommand extends GeneratorCommand
{
    protected $name = 'make:modifier';

    protected $description = 'Create a new input modifier class';

    protected $type = 'Modifier';

    protected function getStub()
    {
        return __DIR__.'/../../stubs/modifier.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }
}
