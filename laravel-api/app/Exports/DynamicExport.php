<?php

namespace App\Exports;

use App\Support\ExportableModels;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DynamicExport implements FromCollection, WithHeadings, WithMapping
{
    private array $fieldResolvers;

    public function __construct(
        private readonly string $model,
        private readonly array $fields,
    ) {
        $this->fieldResolvers = ExportableModels::config($model)['fields'];
    }

    public function collection()
    {
        return ExportableModels::query($this->model)->get();
    }

    public function headings(): array
    {
        return array_map(
            fn ($field) => ucwords(str_replace('_', ' ', $field)),
            $this->fields
        );
    }

    public function map($row): array
    {
        return array_map(
            fn ($field) => ($this->fieldResolvers[$field])($row),
            $this->fields
        );
    }
}
