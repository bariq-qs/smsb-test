<?php

namespace App\Jobs;

use App\Exports\DynamicExport;
use App\Models\Export;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Maatwebsite\Excel\Facades\Excel;

class ProcessExport implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $exportId)
    {
    }

    public function handle(): void
    {
        $export = Export::findOrFail($this->exportId);
        $export->update(['status' => 'processing']);

        try {
            $fileName = 'exports/'.$export->model.'-'.now()->format('YmdHis').'-'.substr($export->id, 0, 8).'.xlsx';

            Excel::store(new DynamicExport($export->model, $export->fields), $fileName, 'public');

            $export->update(['status' => 'completed', 'file_path' => $fileName]);
        } catch (\Throwable $e) {
            $export->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }
}
