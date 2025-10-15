<?php

namespace App\Http\Controllers;

use App\Events\DiceRollBroadcasted;
use App\Http\Requests\DiceRollStoreRequest;
use App\Models\Campaign;
use App\Models\DiceRoll;
use App\Models\CampaignSession;
use App\Services\DiceRoller;
use App\Support\Broadcasting\SessionWorkspacePayload;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class DiceRollController extends Controller
{
    public function __construct(private readonly DiceRoller $diceRoller)
    {
    }

    public function store(DiceRollStoreRequest $request, Campaign $campaign, CampaignSession $session): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->authorize('create', [DiceRoll::class, $session]);

        $expression = strtoupper($request->string('expression')->toString());

        try {
            $result = $this->diceRoller->roll($expression);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'expression' => $exception->getMessage(),
            ]);
        }

        /** @var Authenticatable $user */
        $user = $request->user();

        $roll = $session->diceRolls()->create([
            'campaign_id' => $campaign->id,
            'campaign_session_id' => $session->id,
            'roller_id' => $user?->getAuthIdentifier(),
            'expression' => $expression,
            'result_breakdown' => [
                'rolls' => $result['rolls'],
                'modifier' => $result['modifier'],
            ],
            'result_total' => $result['total'],
        ]);

        event(new DiceRollBroadcasted(
            $session,
            'created',
            SessionWorkspacePayload::diceRoll($roll),
        ));

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Dice roll recorded.');
    }

    public function destroy(Campaign $campaign, CampaignSession $session, DiceRoll $roll): RedirectResponse
    {
        $this->ensureSessionBelongsToCampaign($campaign, $session);
        $this->ensureRollBelongsToSession($session, $roll);
        $this->authorize('delete', $roll);

        $payload = [
            'id' => (int) $roll->id,
        ];

        $roll->delete();

        event(new DiceRollBroadcasted($session, 'deleted', $payload));

        return redirect()
            ->route('campaigns.sessions.show', [$campaign, $session])
            ->with('success', 'Dice roll removed.');
    }

    protected function ensureSessionBelongsToCampaign(Campaign $campaign, CampaignSession $session): void
    {
        if ($session->campaign_id !== $campaign->id) {
            abort(404);
        }
    }

    protected function ensureRollBelongsToSession(CampaignSession $session, DiceRoll $roll): void
    {
        if ($roll->campaign_session_id !== $session->id) {
            abort(404);
        }
    }
}
