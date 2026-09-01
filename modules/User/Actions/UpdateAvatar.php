<?php

declare(strict_types=1);

namespace Modules\User\Actions;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use Modules\User\Contracts\AvatarStorage;
use Modules\User\Enums\AvatarSource;
use Modules\User\Models\User;

final class UpdateAvatar
{
    public function __construct(
        private readonly AvatarStorage $storage,
        private readonly ImageManager $images,
    ) {}

    public function handle(User $user, AvatarSource $source, ?UploadedFile $file = null): User
    {
        if ($source === AvatarSource::Upload) {
            // The callers validate the upload; a missing file here is a
            // programming error, not a user one.
            if ($file === null) {
                throw new InvalidArgumentException('An uploaded avatar requires a file.');
            }

            $image = $this->images->read($file->getRealPath());

            $size = (int) config('trove.avatar.size');

            $path = $this->storage->store(
                $user->id,
                $image->cover($size, $size)->toWebp(quality: 85)->toString(),
            );

            $user->avatar_source = AvatarSource::Upload;
            $user->avatar_path = $path;
        } else {
            if ($user->avatar_path !== null) {
                $this->storage->delete($user->avatar_path);
            }

            $user->avatar_source = $source;
            $user->avatar_path = null;
        }

        $user->save();

        return $user;
    }
}
