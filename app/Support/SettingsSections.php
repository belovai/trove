<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Which settings sections a viewer may see. App-level because the list spans
 * modules: two sections belong to User, one to Tag.
 */
final class SettingsSections
{
    /**
     * @return array<int, array{key: string, label: string, href: string}>
     */
    public static function for(?Authenticatable $user): array
    {
        $sections = [
            ['key' => 'account', 'label' => __('user::account.section_account'), 'href' => '/settings/account'],
            ['key' => 'profile', 'label' => __('user::account.section_profile'), 'href' => '/settings/profile'],
        ];

        if ($user?->can('tag.admin')) {
            $sections[] = ['key' => 'tags', 'label' => __('tag::tag.section_tags'), 'href' => '/settings/tags'];
        }

        if ($user?->can('user.manage')) {
            $sections[] = ['key' => 'users', 'label' => __('user::account.section_users'), 'href' => '/settings/users'];
        }

        return $sections;
    }
}
