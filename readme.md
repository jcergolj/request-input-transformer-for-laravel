
# Request Input Transformer for Laravel

**Clean up your `prepareForValidation()` — the right way.**

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jcergolj/request-input-transformer-for-laravel.svg?style=flat-square)](https://packagist.org/packages/jcergolj/request-input-transformer-for-laravel)

[![Total Downloads](https://img.shields.io/packagist/dt/jcergolj/request-input-transformer-for-laravel.svg?style=flat-square)](https://packagist.org/packages/jcergolj/request-input-transformer-for-laravel)

---

## Why?

If you’ve built more than a couple of Laravel apps, you’ve probably ended up stuffing too much logic into `prepareForValidation()`.

Maybe you’ve written code like this:

```php
protected function prepareForValidation(): void
{
    $this->merge([
        'email' => Str::of($this->email)->trim()->lower()->toString(),
        'first_name' => Str::of($this->first_name)->trim()->ucfirst()->toString(),
        'last_name' => Str::of($this->last_name)->trim()->ucfirst()->toString(),
        //...
    ]);
}
```

We've all been there — a quick `trim()` here, a `lower()` there. But soon, `prepareForValidation()` is packed with string cleanup, formatting, and conditionals.

This package solves this.

## Installation

```bash
composer require jcergolj/request-input-transformer-for-laravel
```

## Under the Hood: Pipeline Pattern

This package uses Laravel’s [Pipeline Pattern](https://laravel.com/docs/12.x/helpers#pipeline) under the hood.

That means your modifiers and transformers are run one by one, just like middleware — each one gets the current value (or request), does its thing, and passes it to the next.

## Modifiers vs Transformers

This package gives you two ways to work with request input: **modifiers** and **transformers**.

### 🛠 Modifiers

Modifiers change values of **existing fields** in the request — great for cleanup and normalization.

```php
// Modifying the 'email' field
'email' => [new Trim(), new Lowercase()],
```

Use modifiers when the field already exists and you need to modify only that field .

**Modifiers merges fields automatically inside pipeline `then` method.**

### 🔄 Transformers
Transformers have access to the entire request and can add or change fields based on logic, relationships, or conditions.

```php
new AddFullName(),
new RemoveItemIfEmpty(),
```

Use transformers when you need to generate new fields or apply logic that involves more than one input.

## Nested Input Support

This package supports nested input keys — including wildcard syntax — out of the box.
You can apply modifiers or transformers to fields like:
```php
'members.*.email' => [new Trim()],
```

## Usage

### Step 1: Create a Modifier and Transformer

#### Modifier: Trim strings

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

#### Transformer: Generate a full name from first and last name

```php
<?php

namespace App\Http\Transformers;

class FullNameTransformer
{
    public function handle($request, $next)
    {
        $request->merge([
            'full_name' => "{$request->first_name} {$request->last_name}",
        ]);

        return $next($request);
    }
}
```

### Step 2: Apply in a FormRequest

```php
<?php

use App\Http\Modifiers\Trim;
use App\Http\Modifiers\toLowercase;
use App\Http\Transformers\FullNameTransformer;
use Illuminate\Foundation\Http\FormRequest;
use Jcergolj\RequestPreprocessor\Facades\RequestPreprocessorFacade;

class MyFormRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        RequestPreprocessorFacade::make($this, [
            'email'      => [new Trim(), new toLowercase(), // other modifiers],
            'first_name' => [new Trim()],
            'last_name'  => [new Trim()],
        ],
        [
            new FullNameTransformer(),
            /* other transformers */
        ])->apply();
    }
}
```

## Generating Modifiers and Transformers

To quickly create modifier or transformer classes, you can use the included Artisan commands:

```bash
php artisan make:modifier TrimModifier
php artisan make:transformer FullNameTransformer
```

## Testing

### What about testing modifiers and transformers?

One of the challenges with `prepareForValidation()` is that Laravel doesn't offer a clean way to test if it actually ran — especially when you're trying to do as much on a unit level as possible.

I initially tried building custom PHPUnit assertions that would inspect the applied modifiers and transformers, but it quickly became complicated.

### My current workaround

A practical alternative is to expose two public methods inside your `FormRequest` class — one returning modifiers, the other transformers — and then test those separately.

Example:

```php
class MyFormRequest extends FormRequest
{
    public function modifiers(): array
    {
        return [
            'email' => [new Trim()],
        ];
    }

    public function transformers(): array
    {
        return [
            new FullNameTransformer(),
        ];
    }

    protected function prepareForValidation()
    {
        RequestPreprocessorFacade::make($this, $this->modifiers(), $this->transformers())->apply();
    }
}
```

Now in your test, you can do something like this:

```php

public function test_my_form_request_defines_expected_modifiers()
{
    $request = new MyFormRequest();

    $this->assertEquals(
        [new Trim()],
        $request->modifiers()['email']
    );
}
```

This doesn’t guarantee that RequestPreprocessor was actually called inside prepareForValidation() — but it does confirm the configuration.

### Mocking the RequestPreprocessor in Tests

Something that I haven't tried but it might actually work is using reflection for `prepareForValidation` and to mock `RequestPreprocessorFacade`.

Something like this.
```php
use Jcergolj\RequestPreprocessor\Facades\RequestPreprocessorFacade;
use Tests\TestCase;

class MyFormRequestTest extends TestCase
{
    public function test_prepareForValidation_calls_preprocessor()
    {
        // Arrange: mock the facade
        RequestPreprocessorFacade::shouldReceive('make')
            ->once()
            ->withArgs(function ($request, $modifiers, $transformers) {
                // Optionally assert $request is the FormRequest instance
                // and $modifiers contain expected keys/classes
                return $request instanceof \App\Http\Requests\MyFormRequest
                    && isset($modifiers['email']);
            })
            ->andReturnSelf();

        RequestPreprocessorFacade::shouldReceive('apply')
            ->once()
            ->andReturnNull();

        // Act: instantiate and call prepareForValidation
        $request = new \App\Http\Requests\MyFormRequest();

        $reflection = new \ReflectionMethod($request, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($request);
    }
}
```

## Testing

To run the tests:

```bash
composer test
```

## License

The MIT License (MIT).
