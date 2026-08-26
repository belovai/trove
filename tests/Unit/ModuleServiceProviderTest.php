<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Providers\ModuleServiceProvider;
use PHPUnit\Framework\TestCase;

final class ModuleServiceProviderTest extends TestCase
{
    public function test_it_derives_a_snake_case_key_from_the_class_name(): void
    {
        $provider = new MediaLibraryModuleServiceProvider(app());

        $this->assertSame('media_library', $provider->key());
    }

    public function test_it_derives_a_single_word_key(): void
    {
        $provider = new UserModuleServiceProvider(app());

        $this->assertSame('user', $provider->key());
    }
}

final class MediaLibraryModuleServiceProvider extends ModuleServiceProvider {}

final class UserModuleServiceProvider extends ModuleServiceProvider {}
