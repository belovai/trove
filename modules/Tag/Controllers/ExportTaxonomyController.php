<?php

declare(strict_types=1);

namespace Modules\Tag\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tag\Services\TaxonomyExporter;

final class ExportTaxonomyController
{
    public function __construct(
        private readonly TaxonomyExporter $exporter,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('tag.admin'), 403);

        return response()
            ->json($this->exporter->export()->toArray(), 200, [
                'Content-Disposition' => 'attachment; filename="trove-taxonomy.json"',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
