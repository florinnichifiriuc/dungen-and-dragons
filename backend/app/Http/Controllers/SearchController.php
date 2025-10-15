<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchIndexRequest;
use App\Models\User;
use App\Services\GlobalSearchService;
use Illuminate\Contracts\Auth\Authenticatable;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function index(SearchIndexRequest $request, GlobalSearchService $search): Response
    {
        $validated = $request->validated();

        $query = isset($validated['q']) ? trim((string) $validated['q']) : '';
        $scopes = $search->normalizeScopes($validated['scopes'] ?? []);

        /** @var Authenticatable&User $user */
        $user = $request->user();

        $results = $search->search($user, $query, $scopes);

        return Inertia::render('Search/Index', [
            'query' => $query,
            'results' => $results,
            'active_scopes' => $scopes,
            'available_scopes' => $search->availableScopes(),
        ]);
    }
}
