<?php

declare(strict_types=1);

namespace Modules\Media\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Media\Actions\StoreUploadedMedia;
use Modules\Media\DataObjects\UploadedMediaData;
use Modules\Media\Enums\DuplicatePolicy;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Media\Requests\StoreMediaRequest;
use Modules\User\Models\User;

final class StoreMediaController
{
    public function __construct(
        private readonly StoreUploadedMedia $storeUploadedMedia,
    ) {}

    /**
     * One file per request. The batch uploader posts each queued file
     * separately over XHR, so this answers in JSON: 201 on success, 409 when
     * the warn policy wants a confirmation, 422 for validation failures.
     */
    public function __invoke(StoreMediaRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (config('trove.media.duplicate_policy') === DuplicatePolicy::Warn->value
            && !$request->boolean('confirm_duplicate')) {
            $duplicate = $this->storeUploadedMedia->findDuplicateFor(
                hash_file('sha256', $request->file('file')->getRealPath()),
                $user,
            );

            if ($duplicate !== null) {
                // Only ever the item the uploader is already allowed to see —
                // findDuplicateFor() queries through their visibility scope.
                return response()->json([
                    'message' => __('media::validation.duplicate_warning'),
                    'duplicate' => [
                        'hash_id' => $duplicate->hash_id,
                        'title' => $duplicate->title,
                    ],
                ], 409);
            }
        }

        $media = $this->storeUploadedMedia->handle(new UploadedMediaData(
            file: $request->file('file'),
            uploader: $user,
            title: $request->input('title'),
            description: $request->input('description'),
            source: $request->input('source'),
            visibility: Visibility::from($request->string('visibility')->value()),
            safetyRating: SafetyRating::from($request->string('safety_rating')->value()),
            isAnonymous: $request->boolean('is_anonymous'),
        ));

        return response()->json([
            'hash_id' => $media->hash_id,
            'title' => $media->title,
        ], 201);
    }
}
