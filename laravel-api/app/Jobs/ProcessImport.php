<?php

namespace App\Jobs;

use App\Imports\DynamicImport;
use App\Models\Import;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessImport implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $importId)
    {
    }

    public function handle(): void
    {
        $import = Import::findOrFail($this->importId);
        $import->update(['status' => 'processing']);

        try {
            $importer = new DynamicImport($import->model);
            Excel::import($importer, Storage::disk('public')->path($import->file_path));

            $import->update([
                'status' => 'completed',
                'created_count' => $importer->createdCount,
                'updated_count' => $importer->updatedCount,
                'errors' => $importer->rowErrors,
            ]);
        } catch (\Throwable $e) {
            $import->update(['status' => 'failed', 'errors' => [$e->getMessage()]]);
        }
    }
}
