<?php

declare(strict_types=1);

namespace Modules\User\Enums;

enum AvatarSource: string
{
    case Letter = 'letter';
    case Upload = 'upload';
    case Gravatar = 'gravatar';
}
