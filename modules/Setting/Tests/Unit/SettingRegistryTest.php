<?php

declare(strict_types=1);

namespace Modules\Setting\Tests\Unit;

use Modules\Setting\Exceptions\DuplicateSettingException;
use Modules\Setting\Exceptions\UnknownSettingException;
use Modules\Setting\Support\ModuleSettingRegistry;
use Modules\Setting\Support\SettingDefinition;
use PHPUnit\Framework\TestCase;

final class SettingRegistryTest extends TestCase
{
    public function test_definitions_from_several_modules_merge(): void
    {
        $registry = new ModuleSettingRegistry;
        $registry->register('setting', ['app.name' => SettingDefinition::string('Trove')]);
        $registry->register('auth', ['registration.approval' => SettingDefinition::bool()]);

        $this->assertTrue($registry->has('app.name'));
        $this->assertTrue($registry->has('registration.approval'));
        $this->assertCount(2, $registry->all());
    }

    public function test_a_key_declared_twice_throws(): void
    {
        $registry = new ModuleSettingRegistry;
        $registry->register('setting', ['app.name' => SettingDefinition::string()]);

        $this->expectException(DuplicateSettingException::class);
        $this->expectExceptionMessage('app.name');

        $registry->register('auth', ['app.name' => SettingDefinition::string()]);
    }

    public function test_an_unknown_key_throws_on_read(): void
    {
        $registry = new ModuleSettingRegistry;

        $this->expectException(UnknownSettingException::class);
        $this->expectExceptionMessage('nope.missing');

        $registry->get('nope.missing');
    }

    public function test_a_namespace_returns_only_its_own_keys(): void
    {
        $registry = new ModuleSettingRegistry;
        $registry->register('auth', [
            'registration.mode' => SettingDefinition::string('open'),
            'registration.email' => SettingDefinition::string('optional'),
        ]);
        $registry->register('setting', ['app.name' => SettingDefinition::string()]);

        $this->assertSame(
            ['registration.mode', 'registration.email'],
            array_keys($registry->namespace('registration')),
        );
    }
}
