<?php
namespace Jcergolj\RequestPreprocessor\Facades;

use Illuminate\Support\Facades\Facade;
use Jcergolj\RequestPreprocessor\RequestPreprocessor as RequestPreprocessorClass;

class RequestPreprocessor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RequestPreprocessorClass::class;
    }

    public static function __callStatic($method, $args)
    {
        return forward_static_call_array([static::getFacadeAccessor(), $method], $args);
    }
}
