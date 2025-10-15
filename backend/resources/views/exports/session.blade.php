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
    $format = fn($carbon) => $carbon ? $carbon->format('Y-m-d H:i \U\T\C') : null;
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
