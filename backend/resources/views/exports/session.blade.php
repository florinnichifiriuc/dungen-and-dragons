<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $metadata['session']['title'] }} – Session Chronicle</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #111827;
            margin: 0;
            padding: 2.5rem;
            font-size: 14px;
            line-height: 1.6;
            background-color: #f8fafc;
        }

        h1, h2, h3, h4 {
            color: #111827;
            margin-bottom: 0.5rem;
        }

        h1 {
            font-size: 28px;
        }

        h2 {
            font-size: 20px;
            margin-top: 2rem;
        }

        h3 {
            font-size: 16px;
            margin-top: 1.5rem;
        }

        p {
            margin-top: 0.35rem;
            margin-bottom: 0.35rem;
        }

        .meta-list {
            list-style: none;
            padding: 0;
            margin: 0 0 1.5rem 0;
        }

        .meta-list li {
            margin-bottom: 0.25rem;
        }

        .section {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
        }

        .note-card,
        .dice-card,
        .dialogue-card {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #fdfdfd;
        }

        .muted {
            color: #64748b;
            font-size: 12px;
        }

        .divider {
            border-top: 1px solid #e2e8f0;
            margin: 2rem 0;
        }
    </style>
</head>
<body>
@php
    $session = $metadata['session'];
    $campaign = $metadata['campaign'];
    $viewer = $metadata['viewer'];
    $conditionSummary = $condition_timer_summary ?? null;
    $conditionShare = $condition_timer_summary_share ?? null;
    $conditionChronicle = $condition_timer_chronicle ?? [];
    $viewerCanManage = $viewer['can_manage'] ?? false;
    $format = fn($carbon) => $carbon ? $carbon->format('Y-m-d H:i \U\T\C') : null;
    $formatIso = static function (?string $timestamp) {
        if (! $timestamp) {
            return 'Unknown time';
        }

        try {
            return \Carbon\CarbonImmutable::parse($timestamp)->setTimezone('UTC')->format('Y-m-d H:i \U\T\C');
        } catch (\Throwable $exception) {
            return $timestamp;
        }
    };
    $formatRounds = static function (array $condition): string {
        if (array_key_exists('rounds', $condition) && $condition['rounds'] !== null) {
            $value = (int) $condition['rounds'];

            return $value === 1 ? '1 round remaining' : sprintf('%d rounds remaining', $value);
        }

        if (! empty($condition['rounds_hint'])) {
            return (string) $condition['rounds_hint'];
        }

        return 'Duration unknown';
    };
@endphp

    <h1>{{ $session['title'] }}</h1>
    <ul class="meta-list">
        <li><strong>Campaign:</strong> {{ $campaign['title'] }}</li>
        <li><strong>Generated:</strong> {{ $format($metadata['generated_at']) }}</li>
        @if($session['session_date'])
            <li><strong>Session date:</strong> {{ $format($session['session_date']) }}</li>
        @endif
        @if($session['duration_minutes'])
            <li><strong>Duration:</strong> {{ $session['duration_minutes'] }} minutes</li>
        @endif
        @if($session['location'])
            <li><strong>Location:</strong> {{ $session['location'] }}</li>
        @endif
        @if($session['turn'])
            <li><strong>Linked turn:</strong> #{{ $session['turn']['number'] }}</li>
        @endif
        @if($session['recording_url'])
            <li><strong>External recording:</strong> {{ $session['recording_url'] }}</li>
        @endif
        @if($session['stored_recording_url'])
            <li><strong>Vault recording:</strong> {{ $session['stored_recording_name'] }}</li>
        @endif
        <li><strong>Compiled for:</strong> {{ $viewer['name'] }} {{ $viewer['can_manage'] ? '(GM)' : '' }}</li>
    </ul>

    <div class="section">
        <h2>Agenda</h2>
        <p>{!! nl2br(e($session['agenda'] ?? 'No agenda recorded.')) !!}</p>
    </div>

    <div class="section">
        <h2>Summary</h2>
        <p>{!! nl2br(e($session['summary'] ?? 'No summary captured yet.')) !!}</p>
    </div>

    <div class="section">
        <h2>Active Condition Outlook</h2>
        @if($conditionSummary && count($conditionSummary['entries'] ?? []) > 0)
            @if($conditionShare && !empty($conditionShare['url']))
                <p class="muted">
                    Shareable view: <a href="{{ $conditionShare['url'] }}">{{ $conditionShare['url'] }}</a>
                    @if(!empty($conditionShare['expires_at']))
                        <br>
                        Expires {{ $formatIso(optional($conditionShare['expires_at'])->toIso8601String()) }}
                    @endif
                    @if(!empty($conditionShare['expiry']['label']))
                        <br>
                        Status: {{ $conditionShare['expiry']['label'] }}
                    @endif
                </p>

                @php $shareStats = $conditionShare['stats'] ?? null; @endphp
                @if($shareStats)
                    <div class="muted" style="margin-top: 0.75rem;">Access overview</div>
                    <ul class="muted" style="margin: 0.25rem 0 0 1rem;">
                        <li>Total opens: {{ $shareStats['total_views'] ?? 0 }}</li>
                        @if(!empty($shareStats['last_accessed_at']))
                            <li>
                                Last opened: {{ $formatIso(optional($shareStats['last_accessed_at'])->toIso8601String()) }}
                            </li>
                        @endif
                        @if(!empty($shareStats['daily_views']))
                            @php
                                $dailyCollection = collect($shareStats['daily_views']);
                                $weeklyTotal = $dailyCollection->sum('total');
                                $peakDay = $dailyCollection->sortByDesc('total')->first();
                            @endphp
                            <li>
                                Last 7 days: {{ $weeklyTotal }} opens
                                @if($peakDay && $peakDay['total'] > 0)
                                    (peak {{ $peakDay['total'] }} on
                                    {{ $formatIso(optional($peakDay['date'])->toIso8601String()) }})
                                @endif
                            </li>
                        @endif
                    </ul>

                    @if(!empty($shareStats['recent_accesses']))
                        <div class="muted" style="margin-top: 0.75rem;">Recent guests</div>
                        <ul class="muted" style="margin: 0.25rem 0 0 1rem;">
                            @foreach($shareStats['recent_accesses'] as $access)
                                <li style="margin-bottom: 0.35rem;">
                                    <strong>{{ $formatIso(optional($access['accessed_at'])->toIso8601String()) }}</strong>
                                    @php
                                        $details = collect([
                                            $access['ip_address'] ?? null,
                                            $access['user_agent'] ?? null,
                                        ])->filter()->implode(' • ');
                                    @endphp
                                    @if($details)
                                        <div>{{ $details }}</div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            @endif
            @foreach($conditionSummary['entries'] as $entry)
                <div class="note-card">
                    <h3>{{ $entry['token']['label'] ?? 'Unknown presence' }}</h3>
                    <p class="muted">
                        {{ \Illuminate\Support\Str::headline($entry['token']['disposition'] ?? 'unknown') }}
                        • {{ $entry['map']['title'] ?? 'Unknown map' }}
                    </p>

                    @foreach($entry['conditions'] as $condition)
                        @if(!$loop->first)
                            <div class="divider"></div>
                        @endif
                        <h4>{{ $condition['label'] ?? \Illuminate\Support\Str::headline($condition['key'] ?? 'Condition') }}</h4>
                        <p class="muted">{{ $formatRounds($condition) }} • {{ ucfirst($condition['urgency'] ?? 'calm') }}</p>
                        <p>{!! nl2br(e($condition['summary'] ?? 'No narrative summary available.')) !!}</p>

                        @if(!empty($condition['acknowledged_by_viewer']))
                            <p class="muted">You have acknowledged this condition.</p>
                        @endif

                        @if($viewerCanManage && array_key_exists('acknowledged_count', $condition))
                            <p class="muted">
                                @php $count = (int) ($condition['acknowledged_count'] ?? 0); @endphp
                                {{ $count === 1 ? 'Acknowledged by 1 party member.' : 'Acknowledged by ' . $count . ' party members.' }}
                            </p>
                        @endif

                        @if(!empty($condition['timeline']))
                            <div class="muted" style="margin-top: 0.75rem;">Timeline</div>
                            <ul class="muted" style="margin: 0.5rem 0 0 1rem;">
                                @foreach($condition['timeline'] as $event)
                                    <li style="margin-bottom: 0.35rem;">
                                        <strong>{{ $formatIso($event['recorded_at'] ?? null) }}</strong>
                                        — {{ $event['summary'] ?? 'Adjustment' }}
                                        @if(!empty($event['detail']['summary']))
                                            <div>{{ $event['detail']['summary'] }}</div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach
                </div>
            @endforeach
        @elseif($conditionShare && !empty($conditionShare['url']))
            <p class="muted">No active condition timers at this time.</p>
            <p class="muted mt-2">
                Shareable view: <a href="{{ $conditionShare['url'] }}">{{ $conditionShare['url'] }}</a>
                @if(!empty($conditionShare['expires_at']))
                    <br>
                    Expires {{ $formatIso(optional($conditionShare['expires_at'])->toIso8601String()) }}
                @endif
            </p>

            @php $shareStats = $conditionShare['stats'] ?? null; @endphp
            @if($shareStats)
                <div class="muted" style="margin-top: 0.75rem;">Access overview</div>
                <ul class="muted" style="margin: 0.25rem 0 0 1rem;">
                    <li>Total opens: {{ $shareStats['total_views'] ?? 0 }}</li>
                    @if(!empty($shareStats['last_accessed_at']))
                        <li>
                            Last opened: {{ $formatIso(optional($shareStats['last_accessed_at'])->toIso8601String()) }}
                        </li>
                    @endif
                </ul>

                @if(!empty($shareStats['recent_accesses']))
                    <div class="muted" style="margin-top: 0.75rem;">Recent guests</div>
                    <ul class="muted" style="margin: 0.25rem 0 0 1rem;">
                        @foreach($shareStats['recent_accesses'] as $access)
                            <li style="margin-bottom: 0.35rem;">
                                <strong>{{ $formatIso(optional($access['accessed_at'])->toIso8601String()) }}</strong>
                                @php
                                    $details = collect([
                                        $access['ip_address'] ?? null,
                                        $access['user_agent'] ?? null,
                                    ])->filter()->implode(' • ');
                                @endphp
                                @if($details)
                                    <div>{{ $details }}</div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        @else
            <p class="muted">No active condition timers at this time.</p>
        @endif
    </div>

    <div class="section">
        <h2>Condition Timer Chronicle</h2>
        @forelse($conditionChronicle as $entry)
            <div class="note-card">
                <h3>{{ $entry['token']['label'] ?? 'Unknown presence' }}</h3>
                <p class="muted">
                    {{ $formatIso($entry['recorded_at'] instanceof \Carbon\CarbonInterface ? $entry['recorded_at']->toIso8601String() : ($entry['recorded_at'] ?? null)) }}
                    • {{ \Illuminate\Support\Str::headline($entry['condition_key']) }}
                </p>
                <p>{!! nl2br(e($entry['summary'])) !!}</p>

                @if(!empty($entry['actor']))
                    <p class="muted">
                        Recorded by {{ $entry['actor']['name'] }}@if(!empty($entry['actor']['role'])) ({{ $entry['actor']['role'] }})@endif
                    </p>
                @endif

                @if($entry['previous_rounds'] !== null || $entry['new_rounds'] !== null)
                    <p class="muted">
                        Rounds: {{ $entry['previous_rounds'] ?? '—' }} → {{ $entry['new_rounds'] ?? '—' }}
                    </p>
                @endif

                @if(!empty($entry['context']) && $viewerCanManage)
                    <pre style="background-color: #f8fafc; padding: 0.75rem; border-radius: 8px; border: 1px solid #e2e8f0;">{{ json_encode($entry['context'], JSON_PRETTY_PRINT) }}</pre>
                @endif
            </div>
        @empty
            <p class="muted">No timer adjustments have been recorded yet.</p>
        @endforelse
    </div>

    <div class="section">
        <h2>Rewards & Loot Ledger</h2>
        @forelse($rewards as $reward)
            <div class="note-card">
                <h3>{{ $reward['title'] }}@if($reward['quantity']) ×{{ $reward['quantity'] }}@endif</h3>
                <p class="muted">
                    {{ \Illuminate\Support\Str::headline($reward['reward_type']) }} •
                    Logged by {{ $reward['recorder']['name'] }}
                    • {{ $format($reward['recorded_at']) ?? 'Unknown time' }}
                    @if($reward['awarded_to'])
                        • Awarded to {{ $reward['awarded_to'] }}
                    @endif
                </p>
                @if($reward['notes'])
                    <p>{!! nl2br(e($reward['notes'])) !!}</p>
                @endif
            </div>
        @empty
            <p class="muted">No rewards or loot have been logged for this session.</p>
        @endforelse
    </div>

    <div class="section">
        <h2>Session Recaps</h2>
        @forelse($recaps as $recap)
            <div class="note-card">
                <h3>{{ $recap['title'] ?? ($recap['author']['name'] . ' recap') }}</h3>
                <p class="muted">{{ $recap['author']['name'] }} • {{ $format($recap['created_at']) ?? 'Unknown time' }}</p>
                <p>{!! nl2br(e($recap['body'])) !!}</p>
            </div>
        @empty
            <p class="muted">No recaps recorded yet.</p>
        @endforelse
    </div>

    <div class="section">
        <h2>Notes</h2>
        @forelse($notes as $note)
            <div class="note-card">
                <h3>{{ $note['author']['name'] }} – {{ $format($note['created_at']) ?? 'Unknown time' }}</h3>
                <p class="muted">Visibility: {{ ucfirst($note['visibility']) }} @if($note['is_pinned']) • Pinned @endif</p>
                <p>{!! nl2br(e($note['content'])) !!}</p>
            </div>
        @empty
            <p class="muted">No notes available.</p>
        @endforelse
    </div>

    <div class="section">
        <h2>Dice Log</h2>
        @forelse($dice_rolls as $roll)
            <div class="dice-card">
                <h3>{{ $roll['expression'] }} → {{ $roll['result_total'] }}</h3>
                <p class="muted">
                    {{ $roll['roller']['name'] }} • {{ $format($roll['created_at']) ?? 'Unknown time' }}
                </p>
                @if(!empty($roll['result_breakdown']))
                    <p>Breakdown: {{ json_encode($roll['result_breakdown']) }}</p>
                @endif
            </div>
        @empty
            <p class="muted">No dice rolls were logged.</p>
        @endforelse
    </div>

    <div class="section">
        <h2>Initiative Order</h2>
        @if(count($initiative) > 0)
            <ul>
                @foreach($initiative as $entry)
                    <li>
                        {{ $entry['name'] }} — Initiative {{ $entry['initiative'] }} (Dex {{ $entry['dexterity_mod'] }})
                        @if($entry['is_current'])
                            <strong> ← current</strong>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="muted">No initiative entries recorded.</p>
        @endif
    </div>

    <div class="section">
        <h2>AI Dialogue</h2>
        @forelse($ai_dialogues as $dialogue)
            <div class="dialogue-card">
                <h3>
                    {{ $dialogue['npc_name'] ? $dialogue['npc_name'] : 'NPC Dialogue' }}
                    @if($dialogue['tone'])
                        ({{ $dialogue['tone'] }})
                    @endif
                </h3>
                <p class="muted">{{ $format($dialogue['created_at']) ?? 'Unknown time' }}</p>
                <p><strong>Prompt:</strong> {!! nl2br(e($dialogue['prompt'])) !!}</p>
                @if($dialogue['reply'])
                    <div class="divider"></div>
                    <p><strong>Reply:</strong> {!! nl2br(e($dialogue['reply'])) !!}</p>
                @endif
            </div>
        @empty
            <p class="muted">No AI dialogue captured this session.</p>
        @endforelse
    </div>
</body>
</html>
