<?php

namespace App\Http\Controllers;

use App\Http\Requests\SessionRecordingStoreRequest;
use App\Http\Requests\SessionStoreRequest;
use App\Http\Requests\SessionUpdateRequest;
use App\Models\Campaign;
use App\Models\CampaignSession;
use App\Models\SessionNote;
use App\Models\Turn;
use App\Models\User;
use App\Policies\CampaignPolicy;
use App\Services\SessionExportService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Dompdf\Dompdf;
use Dompdf\Options;

class SessionController extends Controller
{
    public function __construct(private readonly SessionExportService $sessionExportService)
    {
    }

    public function index(Campaign $campaign): InertiaResponse
    {
        $this->authorize('view', $campaign);

        $sessions = $campaign->sessions()
            ->withCount('notes')
            ->orderByDesc('session_date')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (CampaignSession $session) => [
                'id' => $session->id,
                'title' => $session->title,
                'session_date' => $session->session_date?->toIso8601String(),
                'location' => $session->location,
                'notes_count' => $session->notes_count,
            ]);

        return Inertia::render('Sessions/Index', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ],
            'sessions' => $sessions,
        ]);
    }

    public function create(Campaign $campaign): InertiaResponse
    {
        $this->authorize('create', [CampaignSession::class, $campaign]);

        $turns = $campaign->region
            ? $campaign->region->turns()->orderByDesc('number')->limit(20)->get()
            : collect();

        return Inertia::render('Sessions/Create', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ],
            'turns' => $turns->map(fn (Turn $turn) => [
                'id' => $turn->id,
                'number' => $turn->number,
                'processed_at' => $turn->processed_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function store(SessionStoreRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('create', [CampaignSession::class, $campaign]);

        $turnId = $request->input('turn_id');

        if ($turnId !== null) {
            if ($campaign->region_id === null) {
                throw ValidationException::withMessages([
                    'turn_id' => 'This campaign is not linked to a region, so turns cannot be attached yet.',
                ]);
            }

            $turnExists = Turn::query()
                ->whereKey($turnId)
                ->where('region_id', $campaign->region_id)
                ->exists();

            if (! $turnExists) {
                throw ValidationException::withMessages([
                    'turn_id' => 'Selected turn must belong to the campaign region.',
                ]);
            }
        }

        /** @var Authenticatable&User $user */
        $user = $request->user();

        $session = CampaignSession::create([
            'campaign_id' => $campaign->id,
            'turn_id' => $turnId ?: null,
            'created_by' => $user->getAuthIdentifier(),
            'title' => $request->string('title')->toString(),
            'agenda' => $request->input('agenda'),
            'session_date' => $request->input('session_date'),
            'duration_minutes' => $request->integer('duration_minutes') ?: null,
            'location' => $request->input('location'),
            'summary' => $request->input('summary'),
            'recording_url' => $request->input('recording_url'),
        ]);

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Session scheduled.');
    }

    public function show(Campaign $campaign, CampaignSession $session): InertiaResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('view', $session);

        /** @var Authenticatable&User $user */
        $user = auth()->user();

        /** @var CampaignPolicy $campaignPolicy */
        $campaignPolicy = app(CampaignPolicy::class);
        $isManager = $campaignPolicy->update($user, $campaign);

        $session->load([
            'creator:id,name',
            'turn:id,number,window_started_at,processed_at',
            'notes.author:id,name',
            'diceRolls.roller:id,name',
            'initiativeEntries',
            'aiRequests' => fn ($query) => $query
                ->where('request_type', 'npc_dialogue')
                ->latest()
                ->limit(10),
        ]);

        $notes = $session->notes
            ->filter(function (SessionNote $note) use ($isManager): bool {
                if ($isManager) {
                    return true;
                }

                return $note->visibility !== SessionNote::VISIBILITY_GM;
            })
            ->sortByDesc('created_at')
            ->sortByDesc('is_pinned')
            ->values()
            ->map(fn (SessionNote $note) => [
                'id' => $note->id,
                'content' => $note->content,
                'visibility' => $note->visibility,
                'is_pinned' => $note->is_pinned,
                'author' => [
                    'id' => $note->author->id,
                    'name' => $note->author->name,
                ],
                'created_at' => $note->created_at?->toIso8601String(),
            ]);

        $diceRolls = $session->diceRolls
            ->sortByDesc('created_at')
            ->values()
            ->map(fn ($roll) => [
                'id' => $roll->id,
                'expression' => $roll->expression,
                'result_total' => $roll->result_total,
                'result_breakdown' => $roll->result_breakdown,
                'roller' => [
                    'id' => $roll->roller->id,
                    'name' => $roll->roller->name,
                ],
                'created_at' => $roll->created_at?->toIso8601String(),
            ]);

        $initiativeEntries = $session->initiativeEntries
            ->sortBy('order_index')
            ->values()
            ->map(fn ($entry) => [
                'id' => $entry->id,
                'name' => $entry->name,
                'dexterity_mod' => $entry->dexterity_mod,
                'initiative' => $entry->initiative,
                'is_current' => $entry->is_current,
                'order_index' => $entry->order_index,
            ]);

        $aiDialogues = $session->aiRequests
            ->sortByDesc('created_at')
            ->values()
            ->map(fn ($request) => [
                'id' => $request->id,
                'npc_name' => $request->meta['npc_name'] ?? null,
                'tone' => $request->meta['tone'] ?? null,
                'prompt' => $request->prompt,
                'reply' => $request->response_text,
                'status' => $request->status,
                'created_at' => $request->created_at?->toIso8601String(),
            ]);

        $storedRecording = null;

        if ($session->hasStoredRecording()) {
            $storedRecording = [
                'download_url' => Storage::disk($session->recording_disk)->url($session->recording_path),
                'filename' => basename($session->recording_path),
            ];
        }

        return Inertia::render('Sessions/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ],
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'agenda' => $session->agenda,
                'summary' => $session->summary,
                'session_date' => $session->session_date?->toIso8601String(),
                'duration_minutes' => $session->duration_minutes,
                'location' => $session->location,
                'recording_url' => $session->recording_url,
                'stored_recording' => $storedRecording,
                'turn' => $session->turn ? [
                    'id' => $session->turn->id,
                    'number' => $session->turn->number,
                    'window_started_at' => $session->turn->window_started_at?->toIso8601String(),
                ] : null,
                'creator' => [
                    'id' => $session->creator->id,
                    'name' => $session->creator->name,
                ],
            ],
            'notes' => $notes,
            'dice_rolls' => $diceRolls,
            'initiative' => $initiativeEntries,
            'ai_dialogues' => $aiDialogues,
            'note_visibilities' => SessionNote::visibilities(),
            'permissions' => [
                'can_manage' => $isManager,
                'can_delete' => $isManager,
                'can_upload_recording' => $isManager,
            ],
        ]);
    }

    public function edit(Campaign $campaign, CampaignSession $session): InertiaResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('update', $session);

        $turns = $campaign->region
            ? $campaign->region->turns()->orderByDesc('number')->limit(20)->get()
            : collect();

        return Inertia::render('Sessions/Edit', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ],
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'agenda' => $session->agenda,
                'session_date' => $session->session_date?->format('Y-m-d\TH:i'),
                'duration_minutes' => $session->duration_minutes,
                'location' => $session->location,
                'summary' => $session->summary,
                'recording_url' => $session->recording_url,
                'turn_id' => $session->turn_id,
            ],
            'turns' => $turns->map(fn (Turn $turn) => [
                'id' => $turn->id,
                'number' => $turn->number,
                'processed_at' => $turn->processed_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function update(SessionUpdateRequest $request, Campaign $campaign, CampaignSession $session): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('update', $session);

        $turnId = $request->input('turn_id');

        if ($turnId !== null) {
            if ($campaign->region_id === null) {
                throw ValidationException::withMessages([
                    'turn_id' => 'This campaign is not linked to a region, so turns cannot be attached yet.',
                ]);
            }

            $turnExists = Turn::query()
                ->whereKey($turnId)
                ->where('region_id', $campaign->region_id)
                ->exists();

            if (! $turnExists) {
                throw ValidationException::withMessages([
                    'turn_id' => 'Selected turn must belong to the campaign region.',
                ]);
            }
        }

        $session->update([
            'title' => $request->has('title') ? $request->string('title')->toString() : $session->title,
            'agenda' => $request->has('agenda') ? $request->input('agenda') : $session->agenda,
            'session_date' => $request->has('session_date') ? $request->input('session_date') : $session->session_date,
            'duration_minutes' => $request->has('duration_minutes') ? ($request->integer('duration_minutes') ?: null) : $session->duration_minutes,
            'location' => $request->has('location') ? $request->input('location') : $session->location,
            'summary' => $request->has('summary') ? $request->input('summary') : $session->summary,
            'recording_url' => $request->has('recording_url') ? $request->input('recording_url') : $session->recording_url,
            'turn_id' => $turnId ?: null,
        ]);

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Session updated.');
    }

    public function destroy(Campaign $campaign, CampaignSession $session): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('delete', $session);

        if ($session->hasStoredRecording()) {
            Storage::disk($session->recording_disk)->delete($session->recording_path);
        }

        $session->delete();

        return redirect()
            ->route('campaigns.sessions.index', $campaign)
            ->with('success', 'Session archived.');
    }

    public function exportMarkdown(Request $request, Campaign $campaign, CampaignSession $session): HttpResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('view', $session);

        /** @var Authenticatable&User $viewer */
        $viewer = $request->user();

        $data = $this->sessionExportService->buildExportData($session, $viewer);
        $markdown = $this->sessionExportService->generateMarkdown($data);

        $filename = sprintf('session-%s.md', $session->id);

        return response($markdown)
            ->withHeaders([
                'Content-Type' => 'text/markdown; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
    }

    public function exportPdf(Request $request, Campaign $campaign, CampaignSession $session): HttpResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('view', $session);

        /** @var Authenticatable&User $viewer */
        $viewer = $request->user();

        $data = $this->sessionExportService->buildExportData($session, $viewer);

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('exports.session', $data)->render());
        $dompdf->setPaper('a4');
        $dompdf->render();

        $filename = sprintf('session-%s.pdf', $session->id);

        return response($dompdf->output())
            ->withHeaders([
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
    }

    public function storeRecording(SessionRecordingStoreRequest $request, Campaign $campaign, CampaignSession $session): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('update', $session);

        $file = $request->file('recording');
        $disk = 'public';
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $safeName = Str::slug($originalName) ?: 'recording';
        $filename = sprintf('%s-%s.%s', $session->id, $safeName, $extension ?: 'bin');
        $path = $file->storeAs('session-recordings/'.$campaign->id, $filename, $disk);

        if ($session->hasStoredRecording()) {
            Storage::disk($session->recording_disk)->delete($session->recording_path);
        }

        $session->update([
            'recording_disk' => $disk,
            'recording_path' => $path,
        ]);

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Recording uploaded.');
    }

    public function destroyRecording(Campaign $campaign, CampaignSession $session): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('update', $session);

        if ($session->hasStoredRecording()) {
            Storage::disk($session->recording_disk)->delete($session->recording_path);
        }

        $session->update([
            'recording_disk' => null,
            'recording_path' => null,
        ]);

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Recording removed.');
    }

    protected function ensureSessionBelongsToCampaign(Campaign $campaign, CampaignSession $session): void
    {
        if ($session->campaign_id !== $campaign->id) {
            abort(404);
        }
    }
}
