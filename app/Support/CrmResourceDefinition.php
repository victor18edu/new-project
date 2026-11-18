<?php

namespace App\Support;

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
}
