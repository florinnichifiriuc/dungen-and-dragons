<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampaignEntityStoreRequest;
use App\Http\Requests\CampaignEntityUpdateRequest;
use App\Models\Campaign;
use App\Models\CampaignEntity;
use App\Models\GroupMembership;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CampaignEntityController extends Controller
{
    public function index(Request $request, Campaign $campaign): Response
    {
        $campaign->load('group');
        $this->authorize('viewAny', [CampaignEntity::class, $campaign]);

        $entities = CampaignEntity::query()
            ->forCampaign($campaign)
            ->with(['group:id,name', 'owner:id,name', 'tags:id,label,slug,color'])
            ->when($request->string('search')->isNotEmpty(), function (Builder $builder) use ($request): void {
                $term = $request->string('search')->toString();
                $builder->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('alias', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%");
                });
            })
            ->when($request->string('type')->isNotEmpty(), function (Builder $builder) use ($request): void {
                $builder->where('entity_type', $request->string('type')->toString());
            })
            ->when($request->string('tag')->isNotEmpty(), function (Builder $builder) use ($request): void {
                $builder->whereHas('tags', function (Builder $tagQuery) use ($request): void {
                    $tagQuery->where('slug', $request->string('tag')->toString());
                });
            })
            ->orderBy('name')
            ->get()
            ->map(fn (CampaignEntity $entity) => [
                'id' => $entity->id,
                'name' => $entity->name,
                'entity_type' => $entity->entity_type,
                'alias' => $entity->alias,
                'visibility' => $entity->visibility,
                'ai_controlled' => $entity->ai_controlled,
                'tags' => $entity->tags->map(fn (Tag $tag) => [
                    'id' => $tag->id,
                    'label' => $tag->label,
                    'slug' => $tag->slug,
                    'color' => $tag->color,
                ])->values(),
                'owner' => $entity->owner?->only(['id', 'name']),
            ])
            ->values();

        $filters = [
            'search' => $request->string('search')->toString(),
            'type' => $request->string('type')->toString(),
            'tag' => $request->string('tag')->toString(),
        ];

        $availableTags = $campaign->tags()
            ->orderBy('label')
            ->get()
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'label' => $tag->label,
                'slug' => $tag->slug,
                'color' => $tag->color,
            ]);

        return Inertia::render('CampaignEntities/Index', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
                'group' => [
                    'id' => $campaign->group->id,
                    'name' => $campaign->group->name,
                ],
            ],
            'entities' => $entities,
            'filters' => $filters,
            'available_types' => CampaignEntity::types(),
            'available_tags' => $availableTags,
        ]);
    }

    public function create(Campaign $campaign): Response
    {
        $campaign->load('group.memberships.user');
        $this->authorize('create', [CampaignEntity::class, $campaign]);

        return Inertia::render('CampaignEntities/Create', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ],
            'available_types' => CampaignEntity::types(),
            'visibility_options' => CampaignEntity::visibilities(),
            'available_tags' => $campaign->tags()
                ->orderBy('label')
                ->get()
                ->map(fn (Tag $tag) => [
                    'id' => $tag->id,
                    'label' => $tag->label,
                    'slug' => $tag->slug,
                ]),
            'group' => [
                'id' => $campaign->group->id,
                'name' => $campaign->group->name,
            ],
            'members' => $campaign->group->memberships
                ->map(fn (GroupMembership $membership) => [
                    'id' => $membership->user->id,
                    'name' => $membership->user->name,
                ])
                ->values(),
        ]);
    }

    public function store(CampaignEntityStoreRequest $request, Campaign $campaign): RedirectResponse
    {
        $campaign->load('group.memberships');
        $this->authorize('create', [CampaignEntity::class, $campaign]);

        $data = $request->validatedEntityData();
        $data['campaign_id'] = $campaign->id;
        $data['group_id'] = $this->resolveGroupId($campaign, Arr::get($data, 'group_id'));
        $data['owner_id'] = $this->resolveOwnerId($campaign, Arr::get($data, 'owner_id'));
        $data['initiative_default'] = $this->normalizeNullableInteger(Arr::get($data, 'initiative_default'));
        $data['visibility'] = $data['visibility'] ?? CampaignEntity::VISIBILITY_PLAYERS;
        $data['slug'] = $this->generateUniqueSlug($campaign, $data['name']);
        $data['stats'] = $this->filterStats($data['stats'] ?? []);

        $entity = CampaignEntity::create($data);

        $this->syncTags($campaign, $entity, $request->tagNames());

        return redirect()
            ->route('campaigns.entities.show', [$campaign, $entity])
            ->with('success', 'Lore entry recorded.');
    }

    public function show(Campaign $campaign, CampaignEntity $entity): Response
    {
        $campaign->load('group');
        $this->ensureCampaignContext($campaign, $entity);
        $this->authorize('view', $entity);

        $entity->load(['group:id,name', 'owner:id,name', 'tags:id,label,slug,color']);

        return Inertia::render('CampaignEntities/Show', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ],
            'entity' => [
                'id' => $entity->id,
                'name' => $entity->name,
                'entity_type' => $entity->entity_type,
                'alias' => $entity->alias,
                'pronunciation' => $entity->pronunciation,
                'visibility' => $entity->visibility,
                'ai_controlled' => $entity->ai_controlled,
                'initiative_default' => $entity->initiative_default,
                'description' => $entity->description,
                'stats' => $entity->stats,
                'group' => $entity->group?->only(['id', 'name']),
                'owner' => $entity->owner?->only(['id', 'name']),
                'tags' => $entity->tags->map(fn (Tag $tag) => [
                    'id' => $tag->id,
                    'label' => $tag->label,
                    'slug' => $tag->slug,
                    'color' => $tag->color,
                ])->values(),
            ],
        ]);
    }

    public function edit(Campaign $campaign, CampaignEntity $entity): Response
    {
        $campaign->load('group.memberships.user');
        $this->ensureCampaignContext($campaign, $entity);
        $this->authorize('update', $entity);

        $entity->load(['tags:id,label,slug']);

        return Inertia::render('CampaignEntities/Edit', [
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ],
            'entity' => [
                'id' => $entity->id,
                'entity_type' => $entity->entity_type,
                'name' => $entity->name,
                'alias' => $entity->alias,
                'pronunciation' => $entity->pronunciation,
                'visibility' => $entity->visibility,
                'group_id' => $entity->group_id,
                'owner_id' => $entity->owner_id,
                'ai_controlled' => $entity->ai_controlled,
                'initiative_default' => $entity->initiative_default,
                'description' => $entity->description,
                'stats' => $entity->stats,
                'tags' => $entity->tags->pluck('label')->values(),
            ],
            'available_types' => CampaignEntity::types(),
            'visibility_options' => CampaignEntity::visibilities(),
            'available_tags' => $campaign->tags()
                ->orderBy('label')
                ->get()
                ->map(fn (Tag $tag) => [
                    'id' => $tag->id,
                    'label' => $tag->label,
                    'slug' => $tag->slug,
                ]),
            'group' => [
                'id' => $campaign->group->id,
                'name' => $campaign->group->name,
            ],
            'members' => $campaign->group->memberships
                ->map(fn (GroupMembership $membership) => [
                    'id' => $membership->user->id,
                    'name' => $membership->user->name,
                ])
                ->values(),
        ]);
    }

    public function update(CampaignEntityUpdateRequest $request, Campaign $campaign, CampaignEntity $entity): RedirectResponse
    {
        $campaign->load('group.memberships');
        $this->ensureCampaignContext($campaign, $entity);
        $this->authorize('update', $entity);

        $data = $request->validatedEntityData();
        $data['group_id'] = $this->resolveGroupId($campaign, Arr::get($data, 'group_id'));
        $data['owner_id'] = $this->resolveOwnerId($campaign, Arr::get($data, 'owner_id'));
        $data['initiative_default'] = $this->normalizeNullableInteger(Arr::get($data, 'initiative_default'));
        $data['stats'] = $this->filterStats($data['stats'] ?? []);

        if ($entity->name !== $data['name']) {
            $entity->slug = $this->generateUniqueSlug($campaign, $data['name'], $entity);
        }

        $entity->fill($data);
        $entity->save();

        $this->syncTags($campaign, $entity, $request->tagNames());

        return redirect()
            ->route('campaigns.entities.show', [$campaign, $entity])
            ->with('success', 'Lore entry updated.');
    }

    public function destroy(Campaign $campaign, CampaignEntity $entity): RedirectResponse
    {
        $this->ensureCampaignContext($campaign, $entity);
        $this->authorize('delete', $entity);

        $entity->delete();

        return redirect()
            ->route('campaigns.entities.index', $campaign)
            ->with('success', 'Lore entry archived.');
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function filterStats(array $stats): array
    {
        return collect($stats)
            ->filter(fn ($entry) => filled(Arr::get($entry, 'label')) || filled(Arr::get($entry, 'value')))
            ->map(fn ($entry) => [
                'label' => (string) Arr::get($entry, 'label'),
                'value' => Arr::get($entry, 'value') !== null ? (string) Arr::get($entry, 'value') : null,
            ])
            ->values()
            ->toArray();
    }

    private function generateUniqueSlug(Campaign $campaign, string $name, ?CampaignEntity $entity = null): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = Str::slug(Str::random(8));
        }

        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($campaign, $slug, $entity)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(Campaign $campaign, string $slug, ?CampaignEntity $ignore = null): bool
    {
        return CampaignEntity::query()
            ->where('campaign_id', $campaign->id)
            ->when($ignore !== null, fn (Builder $builder) => $builder->whereKeyNot($ignore->getKey()))
            ->where('slug', $slug)
            ->exists();
    }

    private function resolveGroupId(Campaign $campaign, $groupId): ?int
    {
        if ($groupId === null || $groupId === '') {
            return null;
        }

        $groupId = (int) $groupId;

        if ($groupId !== $campaign->group_id) {
            throw ValidationException::withMessages([
                'group_id' => 'Lore entries may only reference the owning group.',
            ]);
        }

        return $groupId;
    }

    private function resolveOwnerId(Campaign $campaign, $ownerId): ?int
    {
        if ($ownerId === null || $ownerId === '') {
            return null;
        }

        $ownerId = (int) $ownerId;

        $isMember = $campaign->group
            ->memberships()
            ->where('user_id', $ownerId)
            ->exists();

        if (! $isMember) {
            throw ValidationException::withMessages([
                'owner_id' => 'Owner must belong to the campaign group roster.',
            ]);
        }

        return $ownerId;
    }

    private function syncTags(Campaign $campaign, CampaignEntity $entity, array $tagNames): void
    {
        if ($tagNames === []) {
            $entity->tags()->sync([]);

            return;
        }

        $tagIds = collect($tagNames)
            ->map(function (string $label) use ($campaign) {
                $slug = Tag::slugFor($label);

                $existing = Tag::query()
                    ->where('campaign_id', $campaign->id)
                    ->where('slug', $slug)
                    ->first();

                if ($existing !== null) {
                    if ($existing->label !== $label) {
                        $existing->label = $label;
                        $existing->save();
                    }

                    return $existing->id;
                }

                $tag = Tag::create([
                    'campaign_id' => $campaign->id,
                    'label' => $label,
                    'slug' => $slug,
                    'color' => $this->deriveColor($label),
                ]);

                return $tag->id;
            })
            ->values();

        $entity->tags()->sync($tagIds);
    }

    private function deriveColor(string $label): ?string
    {
        $palette = collect([
            '#f97316',
            '#10b981',
            '#6366f1',
            '#ec4899',
            '#facc15',
            '#38bdf8',
        ]);

        $hash = crc32(Str::lower($label));

        return $palette[$hash % $palette->count()];
    }

    private function ensureCampaignContext(Campaign $campaign, CampaignEntity $entity): void
    {
        if ($entity->campaign_id !== $campaign->id) {
            abort(404);
        }
    }

    private function normalizeNullableInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
