<?php

namespace Jcergolj\RequestPreprocessor\Tests;

use Illuminate\Foundation\Http\FormRequest;

class TestFormRequest extends FormRequest
{
    protected array $data;

    public function __construct(array $data = [])
    {
        parent::__construct();

        $this->data = $data;
    }

    public function input($key = null, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    public function merge(array $data)
    {
        $this->data = array_merge($this->data, $data);
    }

    public function all($keys = null)
    {
        return $this->data;
    }
}
