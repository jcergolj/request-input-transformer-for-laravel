
# Request Input Transformer for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jcergolj/request-input-transformer-for-laravel.svg?style=flat-square)](https://packagist.org/packages/jcergolj/request-input-transformer-for-laravel)

[![Tests](https://github.com/jcergolj/request-input-transformer-for-laravel/actions/workflows/run-tests.yml/badge.svg)](https://github.com/jcergolj/request-input-transformer-for-laravel/actions)

[![Total Downloads](https://img.shields.io/packagist/dt/jcergolj/request-input-transformer-for-laravel.svg?style=flat-square)](https://packagist.org/packages/jcergolj/request-input-transformer-for-laravel)

A Laravel package that allows you to apply input transformation logic (modifiers and transformers) to your `FormRequest` classes **before validation**.

## Why?

When building Laravel applications, it's common to want to clean or normalize input before validation runs. This package lets you declaratively attach transformation logic to fields, using a clean and testable approach, without cluttering your controller or request classes.

## Features

- Clean `FormRequest` input transformations via `modifiers` and `transformers`.
- Works transparently inside `prepareForValidation()`.
- Built-in test assertions to ensure your modifiers and transformers are applied.

## Installation

```bash
composer require jcergolj/request-input-transformer-for-laravel
```

## Usage

### Step 1: Create Modifiers/Transformers

Create a modifier class:

```php
namespace App\Http\Modifiers;

class Trim
{
    public function handle($value, $next)
    {
        return $next(trim($value));
    }
}
```

### Step 2: Use in a `FormRequest`

```php
use App\Modifiers\Trim;
use Jcergolj\RequestPreprocessor\Facades\RequestPreprocessorFacade;
use Illuminate\Foundation\Http\FormRequest;

class MyFormRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        RequestPreprocessorFacade::make($this, [
            'email' => [new Trim()],
        ])->apply();
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
```

### Step 3: Writing Tests

This package includes helpful assertions to test that your request is applying modifiers:

```php
use Tests\TestCase;

class MyFormRequestTest extends TestCase
{
    use Jcergolj\RequestPreprocessor\Testing\AssertsRequestPreprocessor;

    public function test_modifiers_are_applied()
    {
        $this->assertModifiersApplied(
            new \App\Http\Requests\MyFormRequest,
            ['email' => new \App\Http\Modifiers\Trim]
        );
    }
}
```

## Testing

To run the tests:

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
