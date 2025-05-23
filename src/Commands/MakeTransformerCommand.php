<?php

namespace Jcergolj\RequestPreprocessor\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeTransformerCommand extends GeneratorCommand
{
    protected $name = 'make:transformer';

    protected $description = 'Create a new request transformer class';

    protected $type = 'Transformer';

    protected function getStub()
    {
        return __DIR__.'/../../stubs/transformer.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }
}
