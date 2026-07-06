<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessExport;
use App\Models\Export;
use App\Support\ExportableModels;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ExportController extends Controller
{
    public function fields(Request $request)
    {
        $request->validate([
            'model' => ['required', Rule::in(ExportableModels::models())],
        ]);

        return response()->json([
            'fields' => array_keys(ExportableModels::config($request->query('model'))['fields']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'model' => ['required', Rule::in(ExportableModels::models())],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['string'],
        ]);

        $availableFields = array_keys(ExportableModels::config($data['model'])['fields']);
        $invalidFields = array_diff($data['fields'], $availableFields);

        if (! empty($invalidFields)) {
            throw ValidationException::withMessages([
                'fields' => 'Unknown field(s): '.implode(', ', $invalidFields),
            ]);
        }

        $export = Export::create([
            'model' => $data['model'],
            'fields' => $data['fields'],
            'requested_by' => $request->user()->id,
        ]);

        ProcessExport::dispatch($export->id);

        return response()->json($export, 201);
    }

    public function show(Export $export)
    {
        return response()->json([
            ...$export->toArray(),
            'download_url' => $export->file_path ? Storage::disk('public')->url($export->file_path) : null,
        ]);
    }

    public function index(Request $request)
    {
        return response()->json(
            Export::query()
                ->where('requested_by', $request->user()->id)
                ->latest()
                ->limit(20)
                ->get()
        );
    }
}
