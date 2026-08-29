<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use App\Support\SettingsSections;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Tag\DataObjects\DuplicateCandidate;
use Modules\Tag\DataObjects\ImplicationCandidate;
use Modules\Tag\Models\Tag;
use Modules\Tag\Models\TagCategory;
use Modules\Tag\Services\TagHealthReport;

final class ShowTagSettingsController
{
    public function __construct(
        private readonly TagHealthReport $health,
    ) {}

    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->can('tag.admin'), 403);

        return Inertia::render('settings/Tags', [
            'sections' => SettingsSections::for($request->user()),
            'current' => 'tags',
            'categories' => TagCategory::query()->withCount('tags')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (TagCategory $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'color' => $category->color,
                    'sort_order' => $category->sort_order,
                    'is_default' => $category->is_default,
                    'tags_count' => $category->tags_count,
                ]),
            'health' => [
                'unused' => $this->health->unused()->map(
                    fn (Tag $tag): array => ['name' => $tag->name],
                )->values(),
                'uncategorized' => $this->health->uncategorized()->map(
                    fn (Tag $tag): array => ['name' => $tag->name],
                )->values(),
                'duplicates' => array_map(static fn (DuplicateCandidate $pair): array => [
                    'left' => $pair->left,
                    'right' => $pair->right,
                    'distance' => $pair->distance,
                ], $this->health->nearDuplicates()),
                'implications' => array_map(static fn (ImplicationCandidate $candidate): array => [
                    'from' => $candidate->fromName,
                    'to' => $candidate->toName,
                    'confidence' => round($candidate->confidence, 2),
                ], $this->health->implicationCandidates()),
            ],
        ]);
    }
}
