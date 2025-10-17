<?php

use App\Jobs\ProcessConditionTransparencyExportJob;
use App\Models\ConditionTimerAdjustment;
use App\Models\ConditionTimerAcknowledgement;
use App\Models\ConditionTimerShareConsentLog;
use App\Models\ConditionTransparencyExport;
use App\Models\ConditionTransparencyWebhook;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\Map;
use App\Models\MapToken;
use App\Models\User;
use App\Notifications\ConditionTransparencyExportFailed;
use App\Notifications\ConditionTransparencyExportReady;
use App\Services\ConditionTransparencyExportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

afterEach(function (): void {
    \Mockery::close();
});

it('queues export requests and dispatches the processing job', function () {
    Queue::fake();

    $manager = User::factory()->create();
    $group = Group::factory()->create([
        'created_by' => $manager->id,
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $response = $this->actingAs($manager)
        ->from(route('groups.condition-timers.player-summary', $group))
        ->post(route('groups.condition-transparency.exports.store', $group), [
            'format' => 'json',
            'visibility_mode' => 'details',
        ]);

    $response->assertRedirect(route('groups.condition-timers.player-summary', $group));
    $response->assertSessionHas('success');

    /** @var ConditionTransparencyExport $export */
    $export = ConditionTransparencyExport::query()->first();

    expect($export)->not->toBeNull();
    expect($export->status)->toBe(ConditionTransparencyExport::STATUS_PENDING);
    expect($export->format)->toBe('json');
    expect($export->visibility_mode)->toBe('details');

    Queue::assertPushed(ProcessConditionTransparencyExportJob::class, function (ProcessConditionTransparencyExportJob $job) use ($export) {
        return $job->exportId === $export->id;
    });
});

it('processes exports, stores the dataset, and notifies stakeholders', function () {
    Notification::fake();
    Http::fake([
        'https://example.com/webhook' => Http::response(['ok' => true], 200),
    ]);
    Storage::fake('local');

    config()->set('condition-transparency.exports.storage_disk', 'local');

    $manager = User::factory()->create();
    $group = Group::factory()->create([
        'created_by' => $manager->id,
        'name' => 'Silver Keepers',
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    $player = User::factory()->create();

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'role' => GroupMembership::ROLE_PLAYER,
    ]);

    ConditionTimerShareConsentLog::factory()->create([
        'group_id' => $group->id,
        'user_id' => $player->id,
        'recorded_by' => $manager->id,
        'action' => 'granted',
        'visibility' => 'details',
    ]);

    $map = Map::factory()->create([
        'group_id' => $group->id,
        'title' => 'Undercroft',
    ]);

    $token = MapToken::factory()->create([
        'map_id' => $map->id,
        'name' => 'Sir Reginald',
        'faction' => MapToken::FACTION_ALLIED,
        'status_conditions' => ['poisoned'],
        'status_condition_durations' => ['poisoned' => 5],
        'hidden' => false,
    ]);

    ConditionTimerAcknowledgement::factory()
        ->for($group)
        ->for($token, 'token')
        ->for($player)
        ->create([
            'condition_key' => 'poisoned',
            'summary_generated_at' => CarbonImmutable::now('UTC')->subMinutes(10),
            'acknowledged_at' => CarbonImmutable::now('UTC')->subMinutes(5),
        ]);

    ConditionTimerAdjustment::factory()
        ->for($group)
        ->for($token, 'token')
        ->create([
            'condition_key' => 'poisoned',
            'previous_rounds' => 6,
            'new_rounds' => 4,
            'delta' => -2,
            'recorded_at' => CarbonImmutable::now('UTC')->subMinutes(8),
        ]);

    $webhook = ConditionTransparencyWebhook::factory()->create([
        'group_id' => $group->id,
        'url' => 'https://example.com/webhook',
        'secret' => 'top-secret',
        'active' => true,
        'last_triggered_at' => null,
    ]);

    /** @var ConditionTransparencyExport $export */
    $export = ConditionTransparencyExport::factory()->create([
        'group_id' => $group->id,
        'requested_by' => $manager->id,
        'format' => 'json',
        'visibility_mode' => 'details',
        'status' => ConditionTransparencyExport::STATUS_PENDING,
    ]);

    $job = new ProcessConditionTransparencyExportJob($export->id);
    $job->handle(app(ConditionTransparencyExportService::class));

    $export->refresh();
    $webhook->refresh();

    expect($export->status)->toBe(ConditionTransparencyExport::STATUS_COMPLETED);
    expect($export->file_path)->not->toBeNull();
    expect(Storage::disk('local')->exists($export->file_path))->toBeTrue();

    Notification::assertSentTo($manager, ConditionTransparencyExportReady::class, function (ConditionTransparencyExportReady $notification) use ($export, $manager) {
        $mail = $notification->toMail($manager);

        return str_contains($mail->actionUrl ?? '', (string) $export->id)
            && $notification->via($manager) === ['mail'];
    });

    Http::assertSent(function (Request $request) use ($webhook, $export) {
        return $request->url() === $webhook->url
            && $request->method() === 'POST'
            && $request->header('X-Condition-Transparency-Signature') !== null
            && $request['export_id'] === $export->id;
    });

    expect($webhook->call_count)->toBe(1);
    expect($webhook->last_triggered_at)->not->toBeNull();
});

it('marks exports as failed and notifies the requester on errors', function () {
    Notification::fake();

    $manager = User::factory()->create();
    $group = Group::factory()->create([
        'created_by' => $manager->id,
    ]);

    GroupMembership::create([
        'group_id' => $group->id,
        'user_id' => $manager->id,
        'role' => GroupMembership::ROLE_OWNER,
    ]);

    /** @var ConditionTransparencyExport $export */
    $export = ConditionTransparencyExport::factory()->create([
        'group_id' => $group->id,
        'requested_by' => $manager->id,
        'status' => ConditionTransparencyExport::STATUS_PENDING,
    ]);

    $service = \Mockery::mock(ConditionTransparencyExportService::class);
    $service->shouldReceive('buildDataset')->once()->andThrow(new \RuntimeException('failed to build dataset'));

    $job = new ProcessConditionTransparencyExportJob($export->id);

    expect(fn () => $job->handle($service))->toThrow(\RuntimeException::class);

    $export->refresh();

    expect($export->status)->toBe(ConditionTransparencyExport::STATUS_FAILED);
    expect($export->retry_attempts)->toBe(1);
    expect($export->failure_reason)->toContain('failed to build dataset');

    Notification::assertSentTo($manager, ConditionTransparencyExportFailed::class);
});
