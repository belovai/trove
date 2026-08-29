<?php

declare(strict_types=1);

namespace Modules\Setting;

use App\Contracts\SettingRegistry;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Modules\Setting\Repositories\SettingRepository;

final class SettingManager
{
    /** @var array<string, array{value: ?string, is_encrypted: bool}>|null */
    private ?array $rows = null;

    public function __construct(
        private readonly SettingRegistry $registry,
        private readonly SettingRepository $repository,
    ) {}

    public function get(string $key): mixed
    {
        $definition = $this->registry->get($key);
        $row = $this->rows()[$key] ?? null;

        if ($row === null) {
            return $definition->cast(null);
        }

        return $definition->cast($this->decrypt($key, $row['value'], $row['is_encrypted']));
    }

    public function set(string $key, mixed $value): void
    {
        $definition = $this->registry->get($key);
        $serialized = $definition->serialize($value);

        if ($definition->isEncrypted) {
            $serialized = Crypt::encryptString($serialized);
        }

        $this->repository->set($key, $serialized, $definition->isEncrypted);
        $this->flush();
    }

    public function forget(string $key): void
    {
        $this->registry->get($key);

        $this->repository->forget($key);
        $this->flush();
    }

    /**
     * @return array<string, mixed>
     */
    public function namespace(string $prefix): array
    {
        $values = [];

        foreach (array_keys($this->registry->namespace($prefix)) as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    /**
     * Drop the per-request memo. Written for tests; also correct after a write.
     */
    public function flush(): void
    {
        $this->rows = null;
    }

    /**
     * @return array<string, array{value: ?string, is_encrypted: bool}>
     */
    private function rows(): array
    {
        return $this->rows ??= $this->repository->all();
    }

    private function decrypt(string $key, ?string $value, bool $isEncrypted): ?string
    {
        if (!$isEncrypted || $value === null) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            // Almost always an APP_KEY rotation. Falling back to the default
            // keeps the settings page reachable; throwing would not.
            Log::warning('Could not decrypt the stored setting value.', ['key' => $key]);

            return null;
        }
    }
}
