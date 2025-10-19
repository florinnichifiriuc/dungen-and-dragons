<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\AnalyticsEventController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\BugReportController as AdminBugReportController;
use App\Http\Controllers\Admin\UserRoleController;
use App\Http\Controllers\BugReportController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignDigestPreviewController;
use App\Http\Controllers\CampaignInvitationAcceptController;
use App\Http\Controllers\CampaignInsightsController;
use App\Http\Controllers\CampaignInvitationController;
use App\Http\Controllers\CampaignRoleAssignmentController;
use App\Http\Controllers\CampaignTaskController;
use App\Http\Controllers\CampaignEntityController;
use App\Http\Controllers\CampaignQuestController;
use App\Http\Controllers\CampaignQuestUpdateController;
use App\Http\Controllers\ConditionMentorBriefingModerationController;
use App\Http\Controllers\ConditionTimerAcknowledgementController;
use App\Http\Controllers\ConditionTimerSummaryController;
use App\Http\Controllers\ConditionTimerShareBugReportController;
use App\Http\Controllers\ConditionTimerSummaryShareController;
use App\Http\Controllers\ConditionTimerShareConsentController;
use App\Http\Controllers\ConditionTimerShareMaintenanceController;
use App\Http\Controllers\ConditionTransparencyExportController;
use App\Http\Controllers\ConditionTransparencyWebhookController;
use App\Http\Controllers\GroupMentorBriefingPreferenceController;
use App\Http\Controllers\DiceRollController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupJoinController;
use App\Http\Controllers\GroupMembershipController;
use App\Http\Controllers\InitiativeEntryController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\RegionAiDelegationController;
use App\Http\Controllers\RegionTurnController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MapTileController;
use App\Http\Controllers\MapTokenConditionTimerBatchController;
use App\Http\Controllers\MapTokenController;
use App\Http\Controllers\TileTemplateController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SessionNoteController;
use App\Http\Controllers\SessionAttendanceController;
use App\Http\Controllers\SessionRecapController;
use App\Http\Controllers\SessionRewardController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\NotificationCenterController;
use App\Http\Controllers\WorldController;
use App\Http\Controllers\AiCreativeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('welcome');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');
    Route::post('analytics/events', [AnalyticsEventController::class, 'store'])->name('analytics.events.store');
    Route::post('ai/assist', AiCreativeController::class)->name('ai.assist');
    Route::get('/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [NotificationCenterController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationCenterController::class, 'markAll'])->name('notifications.read-all');
    Route::get('/settings/preferences', [UserPreferenceController::class, 'edit'])->name('settings.preferences.edit');
    Route::put('/settings/preferences', [UserPreferenceController::class, 'update'])->name('settings.preferences.update');
    Route::get('groups/join', [GroupJoinController::class, 'create'])->name('groups.join');
    Route::post('groups/join', [GroupJoinController::class, 'store'])->name('groups.join.store');
    Route::resource('groups', GroupController::class);
    Route::get(
        'groups/{group}/condition-timers/player-summary',
        [ConditionTimerSummaryController::class, 'show']
    )->name('groups.condition-timers.player-summary');
    Route::post(
        'groups/{group}/condition-timers/acknowledgements',
        [ConditionTimerAcknowledgementController::class, 'store']
    )->name('groups.condition-timers.acknowledgements.store');
    Route::post(
        'groups/{group}/condition-timers/player-summary/share-links',
        [ConditionTimerSummaryShareController::class, 'store']
    )->name('groups.condition-timers.player-summary.share-links.store');
    Route::delete(
        'groups/{group}/condition-timers/player-summary/share-links/{share}',
        [ConditionTimerSummaryShareController::class, 'destroy']
    )->name('groups.condition-timers.player-summary.share-links.destroy');
    Route::patch(
        'groups/{group}/condition-timers/player-summary/share-links/{share}/extend',
        [ConditionTimerSummaryShareController::class, 'extend']
    )->name('groups.condition-timers.player-summary.share-links.extend');
    Route::get(
        'groups/{group}/condition-timers/player-summary/share-links/insights',
        [ConditionTimerSummaryShareController::class, 'insights']
    )->name('groups.condition-timers.player-summary.share-links.insights');
    Route::get(
        'groups/{group}/condition-transparency/maintenance',
        [ConditionTimerShareMaintenanceController::class, 'show']
    )->name('groups.condition-transparency.maintenance');
    Route::post(
        'groups/{group}/condition-timers/share-consents',
        [ConditionTimerShareConsentController::class, 'store']
    )->name('groups.condition-timers.share-consents.store');
    Route::post(
        'groups/{group}/condition-transparency/exports',
        [ConditionTransparencyExportController::class, 'store']
    )->name('groups.condition-transparency.exports.store');
    Route::get(
        'groups/{group}/condition-transparency/exports/{export}/download',
        [ConditionTransparencyExportController::class, 'download']
    )->name('groups.condition-transparency.exports.download');
    Route::post(
        'groups/{group}/condition-transparency/webhooks',
        [ConditionTransparencyWebhookController::class, 'store']
    )->name('groups.condition-transparency.webhooks.store');
    Route::post(
        'groups/{group}/condition-transparency/webhooks/{webhook}/rotate',
        [ConditionTransparencyWebhookController::class, 'rotate']
    )->name('groups.condition-transparency.webhooks.rotate');
    Route::delete(
        'groups/{group}/condition-transparency/webhooks/{webhook}',
        [ConditionTransparencyWebhookController::class, 'destroy']
    )->name('groups.condition-transparency.webhooks.destroy');
    Route::patch(
        'groups/{group}/condition-transparency/mentor-briefings',
        [GroupMentorBriefingPreferenceController::class, 'update']
    )->name('groups.condition-transparency.mentor-briefings.update');
    Route::patch(
        'groups/{group}/condition-transparency/mentor-briefings/moderation',
        [ConditionMentorBriefingModerationController::class, 'update']
    )->name('groups.condition-transparency.mentor-briefings.moderation');
    Route::resource('groups.memberships', GroupMembershipController::class)
        ->only(['store', 'update', 'destroy'])
        ->scoped();
    Route::resource('groups.worlds', WorldController::class)
        ->except(['index', 'show'])
        ->scoped();
    Route::resource('groups.regions', RegionController::class)->except(['index'])->scoped();
    Route::resource('groups.tile-templates', TileTemplateController::class)
        ->except(['index', 'show'])
        ->scoped();
    Route::resource('groups.maps', MapController::class)
        ->except(['index'])
        ->scoped();
    Route::put('groups/{group}/maps/{map}/fog', [MapController::class, 'updateFog'])->name('groups.maps.fog.update');
    Route::resource('groups.maps.tiles', MapTileController::class)
        ->only(['store', 'update', 'destroy'])
        ->scoped();
    Route::resource('groups.maps.tokens', MapTokenController::class)
        ->only(['store', 'update', 'destroy'])
        ->scoped();
    Route::post(
        'groups/{group}/maps/{map}/tokens/condition-timers/batch',
        MapTokenConditionTimerBatchController::class
    )->name('groups.maps.tokens.condition-timers.batch');
    Route::get('groups/{group}/regions/{region}/turns/create', [RegionTurnController::class, 'create'])->name('groups.regions.turns.create');
    Route::post('groups/{group}/regions/{region}/turns', [RegionTurnController::class, 'store'])->name('groups.regions.turns.store');
    Route::post('groups/{group}/regions/{region}/ai-delegate', [RegionAiDelegationController::class, 'store'])->name('groups.regions.ai-delegate.store');
    Route::resource('campaigns', CampaignController::class);
    Route::get('campaigns/{campaign}/insights', [CampaignInsightsController::class, 'show'])->name('campaigns.insights.show');
    Route::get('campaigns/{campaign}/digests/preview', [CampaignDigestPreviewController::class, 'show'])->name('campaigns.digests.preview');
    Route::post('campaigns/{campaign}/invitations', [CampaignInvitationController::class, 'store'])->name('campaigns.invitations.store');
    Route::delete('campaigns/{campaign}/invitations/{invitation}', [CampaignInvitationController::class, 'destroy'])->name('campaigns.invitations.destroy');
    Route::get('invitations/{invitation:token}', [CampaignInvitationAcceptController::class, 'show'])->name('campaigns.invitations.accept.show');
    Route::post('invitations/{invitation:token}/accept', [CampaignInvitationAcceptController::class, 'store'])->name('campaigns.invitations.accept.store');
    Route::post('campaigns/{campaign}/assignments', [CampaignRoleAssignmentController::class, 'store'])->name('campaigns.assignments.store');
    Route::delete('campaigns/{campaign}/assignments/{assignment}', [CampaignRoleAssignmentController::class, 'destroy'])->name('campaigns.assignments.destroy');
    Route::get('campaigns/{campaign}/tasks', [CampaignTaskController::class, 'index'])->name('campaigns.tasks.index');
    Route::post('campaigns/{campaign}/tasks', [CampaignTaskController::class, 'store'])->name('campaigns.tasks.store');
    Route::patch('campaigns/{campaign}/tasks/{task}', [CampaignTaskController::class, 'update'])->name('campaigns.tasks.update');
    Route::post('campaigns/{campaign}/tasks/reorder', [CampaignTaskController::class, 'reorder'])->name('campaigns.tasks.reorder');
    Route::delete('campaigns/{campaign}/tasks/{task}', [CampaignTaskController::class, 'destroy'])->name('campaigns.tasks.destroy');
    Route::resource('campaigns.entities', CampaignEntityController::class);
    Route::resource('campaigns.quests', CampaignQuestController::class)->scoped();
    Route::post('campaigns/{campaign}/quests/{quest}/updates', [CampaignQuestUpdateController::class, 'store'])->name('campaigns.quests.updates.store');
    Route::delete('campaigns/{campaign}/quests/{quest}/updates/{update}', [CampaignQuestUpdateController::class, 'destroy'])->name('campaigns.quests.updates.destroy');
    Route::resource('campaigns.sessions', SessionController::class);
    Route::get('campaigns/{campaign}/sessions/{session}/exports/markdown', [SessionController::class, 'exportMarkdown'])->name('campaigns.sessions.exports.markdown');
    Route::get('campaigns/{campaign}/sessions/{session}/exports/pdf', [SessionController::class, 'exportPdf'])->name('campaigns.sessions.exports.pdf');
    Route::post('campaigns/{campaign}/sessions/{session}/recording', [SessionController::class, 'storeRecording'])->name('campaigns.sessions.recording.store');
    Route::delete('campaigns/{campaign}/sessions/{session}/recording', [SessionController::class, 'destroyRecording'])->name('campaigns.sessions.recording.destroy');
    Route::post('campaigns/{campaign}/sessions/{session}/notes', [SessionNoteController::class, 'store'])->name('campaigns.sessions.notes.store');
    Route::patch('campaigns/{campaign}/sessions/{session}/notes/{note}', [SessionNoteController::class, 'update'])->name('campaigns.sessions.notes.update');
    Route::delete('campaigns/{campaign}/sessions/{session}/notes/{note}', [SessionNoteController::class, 'destroy'])->name('campaigns.sessions.notes.destroy');
    Route::post('campaigns/{campaign}/sessions/{session}/attendance', [SessionAttendanceController::class, 'store'])->name('campaigns.sessions.attendance.store');
    Route::delete('campaigns/{campaign}/sessions/{session}/attendance', [SessionAttendanceController::class, 'destroy'])->name('campaigns.sessions.attendance.destroy');
    Route::post('campaigns/{campaign}/sessions/{session}/recaps', [SessionRecapController::class, 'store'])->name('campaigns.sessions.recaps.store');
    Route::delete('campaigns/{campaign}/sessions/{session}/recaps/{recap}', [SessionRecapController::class, 'destroy'])->name('campaigns.sessions.recaps.destroy');
    Route::post('campaigns/{campaign}/sessions/{session}/rewards', [SessionRewardController::class, 'store'])->name('campaigns.sessions.rewards.store');
    Route::delete('campaigns/{campaign}/sessions/{session}/rewards/{reward}', [SessionRewardController::class, 'destroy'])->name('campaigns.sessions.rewards.destroy');
    Route::get('search', [SearchController::class, 'index'])->name('search.index');
    Route::post('campaigns/{campaign}/sessions/{session}/dice-rolls', [DiceRollController::class, 'store'])->name('campaigns.sessions.dice-rolls.store');
    Route::delete('campaigns/{campaign}/sessions/{session}/dice-rolls/{roll}', [DiceRollController::class, 'destroy'])->name('campaigns.sessions.dice-rolls.destroy');
    Route::post('campaigns/{campaign}/sessions/{session}/initiative', [InitiativeEntryController::class, 'store'])->name('campaigns.sessions.initiative.store');
    Route::patch('campaigns/{campaign}/sessions/{session}/initiative/{entry}', [InitiativeEntryController::class, 'update'])->name('campaigns.sessions.initiative.update');
    Route::delete('campaigns/{campaign}/sessions/{session}/initiative/{entry}', [InitiativeEntryController::class, 'destroy'])->name('campaigns.sessions.initiative.destroy');
    Route::get('bug-reports/create', [BugReportController::class, 'create'])->name('bug-reports.create');
    Route::post('bug-reports', [BugReportController::class, 'store'])->name('bug-reports.store');
    Route::get('bug-reports/{bugReport}', [BugReportController::class, 'show'])->name('bug-reports.show');

    Route::prefix('admin')->name('admin.')->middleware('can:viewAny',\App\Models\BugReport::class)->group(function (): void {
        Route::get('bug-reports', [AdminBugReportController::class, 'index'])->name('bug-reports.index');
        Route::get('bug-reports/export', [AdminBugReportController::class, 'export'])->name('bug-reports.export');
        Route::get('bug-reports/{bugReport}', [AdminBugReportController::class, 'show'])->name('bug-reports.show');
        Route::patch('bug-reports/{bugReport}', [AdminBugReportController::class, 'update'])->name('bug-reports.update');
        Route::post('bug-reports/{bugReport}/comments', [AdminBugReportController::class, 'comment'])->name('bug-reports.comment');
        Route::get('users', [UserRoleController::class, 'index'])->name('users.index');
        Route::patch('users/{user}', [UserRoleController::class, 'update'])->name('users.update');
    });
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::get(
    'share/condition-timers/{token}',
    [ConditionTimerSummaryShareController::class, 'showPublic']
)->name('shares.condition-timers.player-summary.show');
Route::get(
    'share/condition-timers/{token}/report',
    [ConditionTimerShareBugReportController::class, 'create']
)->name('shares.condition-timers.bug-report.create');
Route::post(
    'share/condition-timers/{token}/report',
    [ConditionTimerShareBugReportController::class, 'store']
)->name('shares.condition-timers.bug-report.store');
