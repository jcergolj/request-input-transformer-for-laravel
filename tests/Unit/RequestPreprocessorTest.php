<?php

namespace Jcergolj\RequestPreprocessor\Tests\Unit;

use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Jcergolj\RequestPreprocessor\PipelinedItem;
use Jcergolj\RequestPreprocessor\Tests\TestFormRequest;
use Jcergolj\RequestPreprocessor\Facades\RequestPreprocessor;

class RequestPreprocessorTest extends TestCase
{
    #[Test]
    public function it_transforms_simple_field_using_modifiers()
    {
        $request = new TestFormRequest(['email' => '  Joe.DOE@eXample.com ']);

        $trim = new class
        {
            public function handle(PipelinedItem $item, $next)
            {
                $item->value = trim($item->value);

                return $next($item);
            }
        };

        $lowercase = new class
        {
            public function handle(PipelinedItem $item, $next)
            {
                $item->value = strtolower($item->value);

                return $next($item);
            }
        };

        RequestPreprocessor::make($request, [
            'email' => [$trim, $lowercase],
        ])->apply();

        $this->assertSame('joe.doe@example.com', $request->all()['email']);
    }

    #[Test]
    public function it_transforms_nested_wildcard_fields_using_modifiers()
    {
        $request = new TestFormRequest([
            'items' => [
                ['name' => [['first' => ' Joe '], ['last' => '  Doe ']]],
                ['name' => [['first' => ' Will '], ['last' => '  Smith ']]],
            ],
        ]);

        $trim = new class
        {
            public function handle(PipelinedItem $item, $next)
            {
                $item->value = trim($item->value);

                return $next($item);
            }
        };

        RequestPreprocessor::make($request, [
            'items.*.name.*.first' => [$trim],
            'items.*.name.*.last' => [$trim],
        ])->apply();

        $data = $request->all();

        $this->assertSame('Joe', $data['items'][0]['name'][0]['first']);

        $this->assertSame('Doe', $data['items'][0]['name'][1]['last']);

        $this->assertSame('Will', $data['items'][1]['name'][0]['first']);

        $this->assertSame('Smith', $data['items'][1]['name'][1]['last']);
    }

    #[Test]
    public function it_transforms_wildcard_fields_using_modifiers()
    {
        $request = new TestFormRequest([
            'items' => [
                ['name' => '  Foo '],
                ['name' => '  Bar '],
            ],
        ]);

        $trim = new class
        {
            public function handle(PipelinedItem $item, $next)
            {
                $item->value = trim($item->value);

                return $next($item);
            }
        };

        RequestPreprocessor::make($request, [
            'items.*.name' => [$trim],
        ])->apply();

        $data = $request->all();

        $this->assertSame('Foo', $data['items'][0]['name']);

        $this->assertSame('Bar', $data['items'][1]['name']);
    }

    #[Test]
    public function it_skips_transformation_for_empty_or_null_values()
    {
        $request = new TestFormRequest(['field' => '  ']);

        $modifier = new class
        {
            public function handle(PipelinedItem $item, $next)
            {
                $item->value = 'changed';

                return $next($item);
            }
        };

        RequestPreprocessor::make($request, [
            'field' => [$modifier],
        ])->apply();

        $this->assertSame('  ', $request->all()['field']);
    }

    #[Test]
    public function it_applies_transformer_to_request()
    {
        $request = new TestFormRequest([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $fullNameTransformer = new class
        {
            public function handle($request, $next)
            {
                $data = $request->all();
                $fullName = $data['first_name'].' '.$data['last_name'];

                $request->merge(['full_name' => $fullName]);

                return $next($request);
            }
        };

        RequestPreprocessor::make(request: $request, transformers: [$fullNameTransformer])->apply();

        $this->assertSame('John Doe', $request->all()['full_name']);
    }

    #[Test]
    public function it_applies_transformers_to_request()
    {
        $request = new TestFormRequest([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $fullNameTransformer = new class
        {
            public function handle($request, $next)
            {
                $data = $request->all();

                $fullName = $data['first_name'].' '.$data['last_name'];

                $request->merge(['full_name' => $fullName]);

                return $next($request);
            }
        };

        $addEmailTransformer = new class
        {
            public function handle($request, $next)
            {
                $request->merge(['email' => 'joe.doe@example.com']);

                return $next($request);
            }
        };

        RequestPreprocessor::make(request: $request, transformers: [$fullNameTransformer, $addEmailTransformer])->apply();

        $this->assertSame('John Doe', $request->all()['full_name']);

        $this->assertSame('joe.doe@example.com', $request->all()['email']);
    }
}
