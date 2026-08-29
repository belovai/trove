<?php

declare(strict_types=1);

namespace Modules\Setting\Tests\Unit;

use Modules\Setting\Support\SettingDefinition;
use PHPUnit\Framework\TestCase;

enum Colour: string
{
    case Red = 'red';
    case Blue = 'blue';
}

final class SettingDefinitionTest extends TestCase
{
    public function test_a_missing_row_yields_the_default(): void
    {
        $this->assertSame('Trove', SettingDefinition::string('Trove')->cast(null));
        $this->assertTrue(SettingDefinition::bool(true)->cast(null));
        $this->assertSame(10, SettingDefinition::int(10)->cast(null));
        $this->assertSame([], SettingDefinition::array()->cast(null));
    }

    public function test_scalars_round_trip(): void
    {
        $string = SettingDefinition::string();
        $this->assertSame('hello', $string->cast($string->serialize('hello')));

        $bool = SettingDefinition::bool();
        $this->assertFalse($bool->cast($bool->serialize(false)));

        $int = SettingDefinition::int();
        $this->assertSame(42, $int->cast($int->serialize(42)));

        $array = SettingDefinition::array();
        $this->assertSame(['a', 'b'], $array->cast($array->serialize(['a', 'b'])));
    }

    public function test_an_enum_round_trips_as_an_instance(): void
    {
        $definition = SettingDefinition::enum(Colour::class, 'red');

        $this->assertSame(Colour::Red, $definition->cast(null));
        $this->assertSame(Colour::Blue, $definition->cast($definition->serialize(Colour::Blue)));
        // The raw form is the backing value, not the case name.
        $this->assertSame('"blue"', $definition->serialize(Colour::Blue));
    }

    public function test_an_enum_accepts_its_backing_value_on_write(): void
    {
        $definition = SettingDefinition::enum(Colour::class, 'red');

        $this->assertSame(Colour::Blue, $definition->cast($definition->serialize('blue')));
    }

    public function test_an_unreadable_row_falls_back_to_the_default(): void
    {
        $definition = SettingDefinition::enum(Colour::class, 'red');

        // A value written before an enum case was removed.
        $this->assertSame(Colour::Red, $definition->cast('"green"'));
        // Malformed JSON.
        $this->assertSame(Colour::Red, $definition->cast('not json'));
    }

    public function test_the_builder_methods_are_immutable(): void
    {
        $base = SettingDefinition::string();
        $encrypted = $base->encrypted()->rules(['required']);

        $this->assertFalse($base->isEncrypted);
        $this->assertSame([], $base->validationRules);
        $this->assertTrue($encrypted->isEncrypted);
        $this->assertSame(['required'], $encrypted->validationRules);
    }
}
