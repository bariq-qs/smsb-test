<?php

namespace App\Http\Controllers\Api;

use App\Exports\ImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessImport;
use App\Models\Import;
use App\Support\ExportableModels;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function template(Request $request)
    {
        $data = $request->validate([
            'model' => ['required', Rule::in(ExportableModels::models())],
        ]);

        return Excel::download(
            new ImportTemplateExport($data['model']),
            "{$data['model']}-import-template.xlsx"
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'model' => ['required', Rule::in(ExportableModels::models())],
            'file' => ['required', 'file', 'mimes:xlsx,csv,xls', 'max:5120'],
        ]);

        $path = $request->file('file')->store('imports', 'public');

        $import = Import::create([
            'model' => $data['model'],
            'file_path' => $path,
            'requested_by' => $request->user()->id,
        ]);

        ProcessImport::dispatch($import->id);

        return response()->json($import, 201);
    }

    public function show(Import $import)
    {
        return response()->json($import);
    }

    public function index(Request $request)
    {
        return response()->json(
            Import::query()
                ->where('requested_by', $request->user()->id)
                ->latest()
                ->limit(20)
                ->get()
        );
    }
}
