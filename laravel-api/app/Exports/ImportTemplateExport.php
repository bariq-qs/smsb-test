<?php

namespace App\Exports;

use App\Support\ExportableModels;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportTemplateExport implements FromArray, WithHeadings
{
    private array $columns;

    private array $example;

    public function __construct(string $model)
    {
        $config = ExportableModels::config($model);
        $this->columns = array_keys($config['import']);
        $this->example = $config['template_example'] ?? [];
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function array(): array
    {
        return [
            array_map(fn ($column) => $this->example[$column] ?? '', $this->columns),
        ];
    }
}
