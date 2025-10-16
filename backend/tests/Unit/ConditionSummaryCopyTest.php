<?php

use App\Models\MapToken;
use App\Support\ConditionSummaryCopy;

it('provides copy for every supported condition and tone', function () {
    $tones = ['calm', 'warning', 'critical'];

    foreach (MapToken::CONDITIONS as $condition) {
        foreach ($tones as $tone) {
            $copy = ConditionSummaryCopy::for($condition, $tone, [
                ':target' => 'Aria',
            ]);

            expect($copy)->not->toBe('');
            expect($copy)->toContain('Aria');
        }
    }
});

it('falls back to the default template for unknown conditions', function () {
    $copy = ConditionSummaryCopy::for('unknown', 'warning', [
        ':target' => 'Cipher',
    ]);

    expect($copy)->toBe('Cipher struggles as the effect tightens its hold.');
});
