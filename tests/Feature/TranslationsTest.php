<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Translations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class TranslationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_flattens_module_translations_into_namespaced_keys(): void
    {
        $messages = app(Translations::class)->forLocale('en');

        $this->assertSame('Regular user', $messages['user::rank.regular']);
    }

    public function test_it_returns_the_requested_locale(): void
    {
        $messages = app(Translations::class)->forLocale('hu');

        $this->assertSame('Felhasználó', $messages['user::rank.regular']);
    }

    public function test_every_locale_defines_the_same_keys(): void
    {
        $locales = config('trove.locales');
        $reference = array_keys(app(Translations::class)->forLocale($locales[0]));
        sort($reference);

        foreach (array_slice($locales, 1) as $locale) {
            $keys = array_keys(app(Translations::class)->forLocale($locale));
            sort($keys);

            $this->assertSame(
                $reference,
                $keys,
                "Locale [{$locale}] does not define the same keys as [{$locales[0]}].",
            );
        }
    }

    public function test_the_translations_reach_the_page_as_a_shared_prop(): void
    {
        $this->get('/')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('locale', 'en')
                ->where('translations', fn ($translations) => $translations->get('user::rank.regular') === 'Regular user')
        );
    }
}
