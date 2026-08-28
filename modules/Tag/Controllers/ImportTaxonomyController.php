<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Http\RedirectResponse;
use Modules\Tag\Actions\ImportTaxonomy;
use Modules\Tag\DataObjects\TaxonomyDocument;
use Modules\Tag\Requests\ImportTaxonomyRequest;

final class ImportTaxonomyController
{
    public function __construct(
        private readonly ImportTaxonomy $importTaxonomy,
    ) {}

    public function __invoke(ImportTaxonomyRequest $request): RedirectResponse
    {
        $raw = json_decode((string) file_get_contents($request->file('file')->getRealPath()), true);

        if (!is_array($raw)) {
            return back()->withErrors(['file' => __('tag::validation.taxonomy_not_json')]);
        }

        $conflicts = $this->importTaxonomy->handle(
            TaxonomyDocument::fromArray($raw),
            $request->boolean('replace'),
        );

        return back()
            ->with('success', __('tag::tag.taxonomy_imported'))
            ->with('taxonomy_conflicts', $conflicts);
    }
}
