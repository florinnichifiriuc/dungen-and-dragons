<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignRoleAssignment;
use App\Models\CampaignSession;
use App\Models\CampaignTask;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\SessionNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class GlobalSearchService
{
    /**
     * @var array<int, string>
     */
    public const SCOPES = ['campaigns', 'sessions', 'notes', 'tasks'];

    /**
     * @return array<int, string>
     */
    public function availableScopes(): array
    {
        return self::SCOPES;
    }

    /**
     * @param array<int, string> $scopes
     * @return array<int, string>
     */
    public function normalizeScopes(array $scopes): array
    {
        $normalized = array_values(array_intersect(self::SCOPES, $scopes));

        return $normalized === [] ? self::SCOPES : $normalized;
    }

    /**
     * @param array<int, string> $scopes
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function search(User $user, ?string $term, array $scopes = []): array
    {
        $term = trim((string) $term);

        $results = [
            'campaigns' => [],
            'sessions' => [],
            'notes' => [],
            'tasks' => [],
        ];

        if ($term === '') {
            return $results;
        }

        $scopes = $this->normalizeScopes($scopes);

        $groupMemberships = $user->groupMemberships()->get(['group_id', 'role']);
        $groupIds = $groupMemberships->pluck('group_id');
        $managedGroupIds = $groupMemberships
            ->whereIn('role', [GroupMembership::ROLE_OWNER, GroupMembership::ROLE_DUNGEON_MASTER])
            ->pluck('group_id')
            ->values();

        $campaignVisibility = function (Builder $query) use ($user, $groupIds): void {
            $query->where(function (Builder $inner) use ($user, $groupIds): void {
                $inner->whereIn('group_id', $groupIds)
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('roleAssignments', function (Builder $assignments) use ($user, $groupIds): void {
                        $assignments->where(function (Builder $assignmentQuery) use ($user, $groupIds): void {
                            $assignmentQuery->where(function (Builder $userAssignments) use ($user): void {
                                $userAssignments->where('assignee_type', User::class)
                                    ->where('assignee_id', $user->id);
                            });

                            if ($groupIds->isNotEmpty()) {
                                $assignmentQuery->orWhere(function (Builder $groupAssignments) use ($groupIds): void {
                                    $groupAssignments->where('assignee_type', Group::class)
                                        ->whereIn('assignee_id', $groupIds);
                                });
                            }
                        });
                    });
            });
        };

        $managedCampaignIds = Campaign::query()
            ->where(function (Builder $query) use ($user, $managedGroupIds): void {
                $query->where('created_by', $user->id);

                if ($managedGroupIds->isNotEmpty()) {
                    $query->orWhereIn('group_id', $managedGroupIds);
                }

                $query->orWhereHas('roleAssignments', function (Builder $assignments) use ($user): void {
                    $assignments->where('assignee_type', User::class)
                        ->where('assignee_id', $user->id)
                        ->where('role', CampaignRoleAssignment::ROLE_GM);
                });
            })
            ->pluck('id');

        $like = $this->makeLikePattern($term);

        if (in_array('campaigns', $scopes, true)) {
            $results['campaigns'] = Campaign::query()
                ->with(['group:id,name', 'region:id,name'])
                ->where($campaignVisibility)
                ->where(function (Builder $query) use ($like): void {
                    $query->where('title', 'like', $like)
                        ->orWhere('synopsis', 'like', $like);
                })
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get()
                ->map(fn (Campaign $campaign) => [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'status' => $campaign->status,
                    'group' => [
                        'id' => $campaign->group->id,
                        'name' => $campaign->group->name,
                    ],
                    'region' => $campaign->region ? [
                        'id' => $campaign->region->id,
                        'name' => $campaign->region->name,
                    ] : null,
                    'updated_at' => $campaign->updated_at?->toIso8601String(),
                ])
                ->values()
                ->all();
        }

        if (in_array('sessions', $scopes, true)) {
            $results['sessions'] = CampaignSession::query()
                ->with(['campaign:id,title'])
                ->whereHas('campaign', $campaignVisibility)
                ->where(function (Builder $query) use ($like): void {
                    $query->where('title', 'like', $like)
                        ->orWhere('agenda', 'like', $like)
                        ->orWhere('summary', 'like', $like);
                })
                ->orderByDesc('session_date')
                ->limit(10)
                ->get()
                ->map(fn (CampaignSession $session) => [
                    'id' => $session->id,
                    'title' => $session->title,
                    'session_date' => $session->session_date?->toIso8601String(),
                    'campaign' => [
                        'id' => $session->campaign->id,
                        'title' => $session->campaign->title,
                    ],
                ])
                ->values()
                ->all();
        }

        if (in_array('notes', $scopes, true)) {
            $notesQuery = SessionNote::query()
                ->with(['campaign:id,title', 'session:id,title,campaign_id', 'author:id,name'])
                ->whereHas('campaign', $campaignVisibility)
                ->where('content', 'like', $like)
                ->orderByDesc('updated_at')
                ->limit(10);

            if ($managedCampaignIds->isEmpty()) {
                $notesQuery->where('visibility', '!=', SessionNote::VISIBILITY_GM);
            } else {
                $notesQuery->where(function (Builder $query) use ($managedCampaignIds): void {
                    $query->where('visibility', '!=', SessionNote::VISIBILITY_GM)
                        ->orWhereIn('campaign_id', $managedCampaignIds);
                });
            }

            $results['notes'] = $notesQuery
                ->get()
                ->map(fn (SessionNote $note) => [
                    'id' => $note->id,
                    'visibility' => $note->visibility,
                    'content_preview' => Str::limit(trim(strip_tags($note->content)), 160),
                    'campaign' => [
                        'id' => $note->campaign->id,
                        'title' => $note->campaign->title,
                    ],
                    'session' => $note->session ? [
                        'id' => $note->session->id,
                        'title' => $note->session->title,
                    ] : null,
                    'author' => [
                        'id' => $note->author?->id,
                        'name' => $note->author?->name,
                    ],
                    'updated_at' => $note->updated_at?->toIso8601String(),
                ])
                ->values()
                ->all();
        }

        if (in_array('tasks', $scopes, true)) {
            $results['tasks'] = CampaignTask::query()
                ->with('campaign:id,title')
                ->whereHas('campaign', $campaignVisibility)
                ->where(function (Builder $query) use ($like): void {
                    $query->where('title', 'like', $like)
                        ->orWhere('description', 'like', $like);
                })
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get()
                ->map(fn (CampaignTask $task) => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'due_turn_number' => $task->due_turn_number,
                    'due_at' => $task->due_at?->toIso8601String(),
                    'campaign' => [
                        'id' => $task->campaign->id,
                        'title' => $task->campaign->title,
                    ],
                ])
                ->values()
                ->all();
        }

        return $results;
    }

    protected function makeLikePattern(string $term): string
    {
        $escaped = addcslashes($term, '%_');

        return '%'.$escaped.'%';
    }
}
