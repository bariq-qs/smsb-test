<?php

namespace App\Imports;

use App\Support\ExportableModels;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DynamicImport implements ToCollection, WithHeadingRow
{
    public int $createdCount = 0;

    public int $updatedCount = 0;

    public array $rowErrors = [];

    public function __construct(private readonly string $model)
    {
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $this->processRow($index, $row);
        }
    }

    private function processRow(int $index, Collection $row): void
    {
        $config = ExportableModels::config($this->model);
        $importers = $config['import'];
        $matchKey = $config['match_key'];
        $modelClass = $config['class'];

        $normalized = collect($row)->mapWithKeys(fn ($value, $key) => [strtolower(trim($key)) => $value]);
        $recognizedFields = array_intersect_key($importers, $normalized->toArray());

        if (empty($recognizedFields) || blank($normalized->get($matchKey))) {
            $this->rowErrors[] = "Row ".($index + 2).": missing required column [{$matchKey}] or no recognized columns.";

            return;
        }

        try {
            [$attributes, $role, $permissions] = $this->resolveFields($recognizedFields, $normalized);

            $matchValue = $normalized->get($matchKey);
            $existing = $modelClass::where($matchKey, $matchValue)->first();

            if ($this->model === 'users' && ! $existing && empty($attributes['password'] ?? null)) {
                $attributes['password'] = bcrypt('password');
            }

            $record = $modelClass::updateOrCreate([$matchKey => $matchValue], $attributes);

            if ($role && method_exists($record, 'syncRoles')) {
                $record->syncRoles([$role]);
            }

            if ($permissions && method_exists($record, 'syncPermissions')) {
                $record->syncPermissions(array_map('trim', explode(',', $permissions)));
            }

            $existing ? $this->updatedCount++ : $this->createdCount++;
        } catch (\Throwable $e) {
            $this->rowErrors[] = 'Row '.($index + 2).': '.$e->getMessage();
        }
    }

    private function resolveFields(array $recognizedFields, Collection $normalized): array
    {
        $attributes = [];
        $role = null;
        $permissions = null;

        foreach ($recognizedFields as $field => $resolver) {
            $result = $resolver($normalized->get($field));

            if (array_key_exists('__role', $result)) {
                $role = $result['__role'];

                continue;
            }

            if (array_key_exists('__permissions', $result)) {
                $permissions = $result['__permissions'];

                continue;
            }

            $attributes = [...$attributes, ...$result];
        }

        return [$attributes, $role, $permissions];
    }
}
