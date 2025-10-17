<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConditionTransparencyExportRequest;
use App\Models\ConditionTransparencyExport;
use App\Models\Group;
use App\Services\ConditionTransparencyExportService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class ConditionTransparencyExportController extends Controller
{
    public function __construct(private readonly ConditionTransparencyExportService $exports)
    {
    }

    public function store(ConditionTransparencyExportRequest $request, Group $group): RedirectResponse
    {
        $this->authorize('update', $group);

        /** @var Authenticatable $user */
        $user = $request->user();
        $filters = array_filter([
            'since' => $request->input('since'),
        ]);

        $this->exports->requestExport(
            $group,
            $user,
            $request->input('format', 'csv'),
            $request->input('visibility_mode', 'counts'),
            $filters,
        );

        return redirect()->back()->with('success', 'Export queued.');
    }

    public function download(Group $group, ConditionTransparencyExport $export): Response
    {
        $this->authorize('update', $group);

        if ($export->group_id !== $group->id) {
            abort(404);
        }

        if ($export->status !== ConditionTransparencyExport::STATUS_COMPLETED || ! $export->file_path) {
            abort(404);
        }

        $disk = config('condition-transparency.exports.storage_disk', 'local');

        if (! Storage::disk($disk)->exists($export->file_path)) {
            abort(404);
        }

        $filename = basename($export->file_path);

        return Storage::disk($disk)->download($export->file_path, $filename);
    }
}
