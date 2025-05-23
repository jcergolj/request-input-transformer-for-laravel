<?php

namespace Jcergolj\RequestPreprocessor\Traits;

use Mockery;
use ReflectionClass;
use PHPUnit\Framework\Assert;
use Jcergolj\RequestPreprocessor\Facades\RequestPreprocessor;

trait RequestPreprocessorAssertions
{
    protected function assertRequestPreprocessorIsApplied(object $formRequest): void
    {
        // Use Reflection to access the protected prepareForValidation method
        $ref = new ReflectionClass($formRequest);
        $method = $ref->getMethod('prepareForValidation');
        $method->setAccessible(true);

        // Define a dynamic anonymous spy modifier class
        $spyModifierClass = new class {
            public static bool $wasCalled = false;

            public function handle($item, $next)
            {
                self::$wasCalled = true;
                return $next($item);
            }
        };

        // Reset static property in case of multiple runs
        $spyModifierClass::$wasCalled = false;

        // Prepare dummy data to ensure the modifier will be triggered
        $formRequest->merge(['email' => ' test@example.com ']);

        // Inject our spy modifier if the formRequest supports setRequestModifiers method
        if (method_exists($formRequest, 'setRequestModifiers')) {
            $formRequest->setRequestModifiers([
                'email' => [$spyModifierClass],
            ]);
        } else {
            $this->fail('FormRequest does not support setRequestModifiers method for injecting modifiers.');
        }

        // Invoke the protected method to trigger modifiers
        $method->invoke($formRequest);

        // Assert that our spy modifier's handle() was actually called
        $this->assertTrue(
            $spyModifierClass::$wasCalled,
            'prepareForValidation did not trigger transformation via apply() modifier handle.'
        );
    }

    protected function assertRequestHasModifiers($request, array $expectedModifiers): void
    {
        $preprocessorSpy = Mockery::mock(RequestPreprocessor::class, [$request])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Mock the Facade to intercept `make()` call, check modifiers argument keys,
        // and return our spy instance instead of a real one
        RequestPreprocessor::shouldReceive('make')
            ->once()
            ->with($request, Mockery::on(function ($modifiers) use ($expectedModifiers) {
                // Assert all expected keys exist in modifiers passed to make()
                foreach ($expectedModifiers as $key => $_) {
                    if (!isset($modifiers[$key])) {
                        return false;
                    }
                }
                return true;
            }))
            ->andReturn($preprocessorSpy);

        // Expect apply() called exactly once on the spy
        $preprocessorSpy->shouldReceive('apply')
            ->once()
            ->andReturnNull();

        // Call protected prepareForValidation method
        $ref = new ReflectionClass($request);
        $method = $ref->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        // Access protected $modifiers property on the spy instance
        $rpReflection = new ReflectionClass($preprocessorSpy);
        $modifiersProp = $rpReflection->getProperty('modifiers');
        $modifiersProp->setAccessible(true);
        $modifiersValue = $modifiersProp->getValue($preprocessorSpy);

        // Assert the modifiers contain the expected keys
        foreach ($expectedModifiers as $key => $expectedModifierInstances) {
            Assert::assertArrayHasKey($key, $modifiersValue, "Modifiers key '{$key}' not found.");

            $actualModifiers = $modifiersValue[$key];

            // Check that each expected modifier is present by class type
            foreach ($expectedModifierInstances as $expectedModifier) {
                $found = false;
                foreach ($actualModifiers as $actualModifier) {
                    if (get_class($actualModifier) === get_class($expectedModifier)) {
                        $found = true;
                        break;
                    }
                }

                Assert::assertTrue($found, "Expected modifier of class " . get_class($expectedModifier) . " not found for key '{$key}'.");
            }
        }
    }

    protected function assertTransformersApplied(object $request, array $expectedTransformers): void
    {
        $preprocessorSpy = Mockery::mock(RequestPreprocessor::class, [$request])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        RequestPreprocessor::shouldReceive('make')
            ->once()
            ->with($request, Mockery::any()) // Transformers might be separate, so just accept any modifiers here
            ->andReturn($preprocessorSpy);

        $preprocessorSpy->shouldReceive('apply')
            ->once()
            ->andReturnNull();

        $ref = new ReflectionClass($request);
        $method = $ref->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $rpReflection = new ReflectionClass($preprocessorSpy);
        $transformersProp = $rpReflection->getProperty('transformers');
        $transformersProp->setAccessible(true);
        $transformersValue = $transformersProp->getValue($preprocessorSpy);

        foreach ($expectedTransformers as $key => $expectedTransformerInstances) {
            Assert::assertArrayHasKey($key, $transformersValue, "Transformers key '{$key}' not found.");
            $actualTransformers = $transformersValue[$key];
            foreach ($expectedTransformerInstances as $expectedTransformer) {
                $found = false;
                foreach ($actualTransformers as $actualTransformer) {
                    if (get_class($actualTransformer) === get_class($expectedTransformer)) {
                        $found = true;
                        break;
                    }
                }

                Assert::assertTrue($found, "Expected transformer of class " . get_class($expectedTransformer) . " not found for key '{$key}'.");
            }
        }
    }

    protected function getProtectedProperty(object $object, string $property)
    {
        $ref = new ReflectionClass($object);
        $prop = $ref->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
    }
}
