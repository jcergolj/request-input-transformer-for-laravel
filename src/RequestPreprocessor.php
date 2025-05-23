<?php

namespace Jcergolj\RequestPreprocessor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;

class RequestPreprocessor
{
    protected array $modifiers = [];

    protected array $transformers = [];

    public function __construct(protected FormRequest $request) {}

    public static function make(FormRequest $request, array $modifiers = [], array $transformers = []): static
    {
        // @phpstan-ignore-next-line
        return (new static($request))->withModifiers($modifiers)->withTransformers($transformers);
    }

    public function withModifiers(array $modifiers): static
    {
        $this->modifiers = $modifiers;

        return $this;
    }

    public function withTransformers(array $transformers): static
    {
        $this->transformers = $transformers;

        return $this;
    }

    public function apply(): void
    {
        $this->applyTransformers();

        $this->applyModifiers();

    }

    protected function applyModifiers(): void
    {
        if (empty($this->modifiers)) {
            return;
        }

        foreach ($this->modifiers as $field => $modifiers) {
            if (str_contains($field, '*')) {
                $this->applyWildcard($field, $modifiers);
            } else {
                $this->applyToField($field, $modifiers);
            }
        }
    }

    protected function applyTransformers(): void
    {
        if (empty($this->transformers)) {
            return;
        }

        $this->request = app(Pipeline::class)
            ->send($this->request)
            ->through($this->transformers)
            ->thenReturn();
    }

    protected function applyToField(string $field, array $modifiers): void
    {
        $value = $this->request->input($field);

        if ($value === null || (is_string($value) && trim($value) === '')) {
            return;
        }

        $pipelinedItem = new PipelinedItem(
            field: $field,
            value: $value,
            request: $this->request
        );

        app(Pipeline::class)
            ->send($pipelinedItem)
            ->through($modifiers)
            ->then(function (PipelinedItem $transformedItem) use ($field) {
                $data = $this->request->all();
                $flattened = Arr::dot($data);
                $flattened[$field] = $transformedItem->value;
                $this->request->merge(Arr::undot($flattened));
            });
    }

    protected function applyWildcard(string $field, array $modifiers): void
    {
        $this->expandAndApply($this->request->all(), explode('.', $field), '', $modifiers);
    }

    protected function expandAndApply(array $data, array $segments, string $path, array $modifiers): void
    {
        $segment = array_shift($segments);
        if ($segment === '*') {
            foreach (data_get($data, $path, []) as $index => $value) {
                $this->expandAndApply($data, $segments, trim($path.'.'.$index, '.'), $modifiers);
            }
        } else {
            $newPath = trim($path.'.'.$segment, '.');

            if (empty($segments)) {
                $this->applyToField($newPath, $modifiers);
            } else {
                $this->expandAndApply($data, $segments, $newPath, $modifiers);
            }
        }
    }

    protected function wildcardBase(string $field): string
    {
        return explode('.*', $field)[0];
    }
}
