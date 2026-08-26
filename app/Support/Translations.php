<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Translation\Translator;

final class Translations
{
    public function __construct(
        private readonly Translator $translator,
        private readonly Application $app,
    ) {}

    /**
     * Every message for one locale, flattened to "namespace::group.key".
     *
     * @return array<string, string>
     */
    public function forLocale(string $locale): array
    {
        if (!$this->app->isProduction()) {
            return $this->build($locale);
        }

        return Cache::rememberForever(
            "trove.translations.{$locale}",
            fn (): array => $this->build($locale),
        );
    }

    /**
     * @return array<string, string>
     */
    private function build(string $locale): array
    {
        $messages = [];

        /** @var array<string, string> $namespaces */
        $namespaces = $this->translator->getLoader()->namespaces();

        foreach ($namespaces as $namespace => $path) {
            foreach (glob("{$path}/{$locale}/*.php") ?: [] as $file) {
                $group = basename($file, '.php');

                /** @var array<string, mixed> $lines */
                $lines = require $file;

                $messages += Arr::dot($lines, "{$namespace}::{$group}.");
            }
        }

        return $messages;
    }
}
