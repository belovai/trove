<?php

declare(strict_types=1);

namespace Modules\Media\Contracts;

use Modules\Media\DataObjects\ExtractedMetadata;

interface MetadataExtractor
{
    /**
     * @param  string  $storagePath  a path returned by MediaStorage
     */
    public function extract(string $storagePath, string $mimeType): ExtractedMetadata;
}
