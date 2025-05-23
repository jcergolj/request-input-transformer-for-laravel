<?php

namespace Jcergolj\RequestPreprocessor;

use Illuminate\Foundation\Http\FormRequest;

class PipelinedItem
{
    public function __construct(
        public mixed $value,
        public readonly string $field,
        public readonly FormRequest $request,
    ) {}
}
