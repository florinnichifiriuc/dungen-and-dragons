<?php

namespace App\Http\Controllers;

use App\Http\Requests\InitiativeEntryStoreRequest;
use App\Http\Requests\InitiativeEntryUpdateRequest;
use App\Models\Campaign;
use App\Models\InitiativeEntry;
use App\Models\CampaignSession;
use App\Services\DiceRoller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class InitiativeEntryController extends Controller
{
    public function __construct(private readonly DiceRoller $diceRoller)
    {
    }

    public function store(InitiativeEntryStoreRequest $request, Campaign $campaign, CampaignSession $session): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('create', [InitiativeEntry::class, $session]);

        $initiative = $request->input('initiative');
        $dexterity = $request->integer('dexterity_mod') ?? 0;

        if ($initiative === null) {
            $expression = '1d20'.($dexterity >= 0 ? '+'.$dexterity : (string) $dexterity);

            try {
                $result = $this->diceRoller->roll($expression);
                $initiative = $result['total'];
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages([
                    'initiative' => 'Unable to roll initiative: '.$exception->getMessage(),
                ]);
            }
        }

        $order = (int) $session->initiativeEntries()->max('order_index');

        $entry = $session->initiativeEntries()->create([
            'name' => $request->string('name')->toString(),
            'entity_type' => $request->input('entity_type'),
            'entity_id' => $request->input('entity_id'),
            'dexterity_mod' => $dexterity,
            'initiative' => $initiative,
            'is_current' => $request->boolean('is_current'),
            'order_index' => $order + 1,
        ]);

        if ($entry->is_current) {
            $session->initiativeEntries()
                ->where('id', '!=', $entry->id)
                ->update(['is_current' => false]);
        }

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Initiative entry added.');
    }

    public function update(InitiativeEntryUpdateRequest $request, Campaign $campaign, CampaignSession $session, InitiativeEntry $entry): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->ensureEntryBelongsToSession($session, $entry);
        $this->authorize('update', $entry);

        $payload = $request->validated();

        if (array_key_exists('order_index', $payload)) {
            $payload['order_index'] = max(0, (int) $payload['order_index']);
        }

        $entry->update($payload);

        if ($request->boolean('is_current')) {
            $session->initiativeEntries()
                ->where('id', '!=', $entry->id)
                ->update(['is_current' => false]);
        }

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Initiative updated.');
    }

    public function destroy(Campaign $campaign, CampaignSession $session, InitiativeEntry $entry): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->ensureEntryBelongsToSession($session, $entry);
        $this->authorize('delete', $entry);

        $entry->delete();

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Initiative removed.');
    }

    protected function ensureSessionBelongsToCampaign(Campaign $campaign, CampaignSession $session): void
    {
        if ($session->campaign_id !== $campaign->id) {
            abort(404);
        }
    }

    protected function ensureEntryBelongsToSession(CampaignSession $session, InitiativeEntry $entry): void
    {
        if ($entry->campaign_session_id !== $session->id) {
            abort(404);
        }
    }
}
