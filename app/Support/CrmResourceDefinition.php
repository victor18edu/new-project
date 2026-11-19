<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Str;

final class CrmResourceDefinition
{
    /**
     * @param  array<int, array<string, mixed>>  $columns
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<int, array<string, mixed>>  $filters
     * @param  array<int, string>  $searchable
     * @param  array<int, string>  $with
     * @param  array<string, mixed>  $seed
     * @param  array<int, array<string, mixed>>  $dependencies
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $singularLabel,
        public readonly string $group,
        public readonly string $modelClass,
        public readonly string $storeRequestClass,
        public readonly string $updateRequestClass,
        public readonly array $columns,
        public readonly array $fields,
        public readonly array $filters = [],
        public readonly array $searchable = [],
        public readonly array $with = [],
        public readonly array $seed = [],
        public readonly array $dependencies = [],
        public readonly ?string $dependencyMessage = null,
        public readonly mixed $afterUpdate = null,
        public readonly string $orderBy = 'id',
        public readonly string $orderDirection = 'desc',
        public readonly ?string $icon = null,
        public readonly string $gender = 'm',
    ) {}

    public function participle(string $masculine, string $feminine): string
    {
        return $this->gender === 'f' ? $feminine : $masculine;
    }

    public function indexRoute(): string
    {
        return "web.{$this->key}.index";
    }

    public function route(string $action): string
    {
        return "web.{$this->key}.{$action}";
    }

    public function routeParameter(): string
    {
        return Str::snake(Str::singular(str_replace('-', '_', $this->key)));
    }

    /**
     * Build every row shown on the details page: the list columns plus any
     * registration field that is not already represented by a column.
     *
     * @return array<int, array<string, mixed>>
     */
    public function detailRows(mixed $record): array
    {
        $rows = [];
        $coveredKeys = [];

        foreach ($this->columns as $column) {
            $coveredKeys[] = $column['key'];

            $rows[] = [
                'label' => $column['label'],
                'key' => $column['key'],
                'format' => $column['format'] ?? 'text',
                'options' => $column['options'] ?? null,
                'default' => $column['default'] ?? null,
                'value' => data_get($record, $column['key']),
            ];
        }

        foreach ($this->fields as $field) {
            $name = $field['name'];

            if ($this->fieldIsCovered($name, $coveredKeys)) {
                continue;
            }

            $type = $field['type'] ?? 'text';
            $value = data_get($record, $name);

            if ($type === 'select' && isset($field['options'])) {
                $options = $this->resolveOptions($field['options']);
                $value = $options[$value] ?? $value;
            }

            $rows[] = [
                'label' => $field['label'] ?? Str::headline($name),
                'key' => $name,
                'format' => match ($type) {
                    'checkbox' => 'boolean',
                    'date' => 'date',
                    'datetime-local' => 'datetime',
                    default => 'text',
                },
                'options' => null,
                'default' => null,
                'value' => $value,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, string>  $coveredKeys
     */
    private function fieldIsCovered(string $field, array $coveredKeys): bool
    {
        if (in_array($field, $coveredKeys, true)) {
            return true;
        }

        foreach ($coveredKeys as $key) {
            if (! str_contains($key, '.')) {
                continue;
            }

            $relation = Str::snake(explode('.', $key, 2)[0]);

            if ($field === $relation || $field === $relation.'_id') {
                return true;
            }
        }

        return false;
    }

    private function resolveOptions(mixed $options): array
    {
        if ($options instanceof Closure) {
            $options = $options();
        }

        return is_array($options) ? $options : [];
    }
}
