<?php

declare(strict_types=1);

namespace Modules\User\Console;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Auth\Rules\NotBlockedName;
use Modules\User\Actions\CreateUser;
use Modules\User\Enums\UserRank;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * The bootstrapping path: creates the first administrator before any account
 * exists to log in with, and any account afterwards without the web UI.
 *
 * Every field can come from the command line or from a prompt, per field: what
 * was passed is taken, what was not is asked for. With --no-interaction (or no
 * TTY, i.e. a script or a Dockerfile) nothing is asked and the declared
 * defaults apply, so the same command works unattended.
 *
 * There is no policy check here — the console operator already has the
 * database and the application key. UserPolicy guards the HTTP path only.
 */
final class CreateUserCommand extends Command
{
    protected $signature = 'user:create
        {username? : The username to create}
        {--rank= : One of restricted, regular, power, moderator, administrator (default: regular)}
        {--password= : Leave out to generate a 16-character password}
        {--display-name= : Shown instead of the username}
        {--email= : Optional; required for password resets and notifications}';

    protected $description = 'Create a user account.';

    /**
     * Whether any value came from a prompt rather than from the command line.
     */
    private bool $prompted = false;

    public function handle(CreateUser $createUser): int
    {
        $username = $this->resolve($this->argument('username'), fn (): string => text(
            label: 'Username',
            required: true,
            validate: $this->validator('username'),
        ));

        $displayName = $this->resolve($this->option('display-name'), fn (): string => text(
            label: 'Display name',
            placeholder: (string) $username,
            hint: 'Shown instead of the username. Leave empty to use the username.',
            validate: $this->validator('display_name'),
        ));

        $email = $this->resolve($this->option('email'), fn (): string => text(
            label: 'Email address',
            hint: 'Optional. Without one the account cannot reset its password or receive notices.',
            validate: $this->validator('email'),
        ));

        $rank = $this->resolve($this->option('rank'), fn (): string => select(
            label: 'Rank',
            options: array_column(UserRank::cases(), 'value', 'value'),
            default: UserRank::Regular->value,
        )) ?? UserRank::Regular->value;

        $password = $this->resolve($this->option('password'), $this->askForPassword(...));
        $generated = $password === null;
        $password ??= Str::password(16);

        $attributes = $this->validate([
            'username' => $username,
            'display_name' => $displayName,
            'email' => $email,
            'password' => $password,
            'rank' => $rank,
        ]);

        // Only worth confirming what the operator has just been walked
        // through; a fully flagged invocation is its own confirmation.
        if ($this->prompted && !confirm(label: "Create {$username} as {$rank}?")) {
            $this->components->warn('Nothing was created.');

            return self::FAILURE;
        }

        $user = $createUser->handle(
            username: $attributes['username'],
            password: $password,
            rank: UserRank::from($attributes['rank']),
            displayName: $attributes['display_name'],
            email: $attributes['email'],
        );

        $this->components->info("Created {$user->username} ({$user->rank->value}).");

        if ($generated) {
            $this->components->twoColumnDetail('Generated password', $password);
            $this->components->warn('Shown once. Store it now.');
        }

        return self::SUCCESS;
    }

    /**
     * A value the operator passed wins; a missing one is asked for, and stays
     * missing when there is nobody to ask.
     */
    private function resolve(mixed $given, Closure $prompt): ?string
    {
        $given = $this->stringOrNull($given);

        if ($given !== null || !$this->input->isInteractive()) {
            return $given;
        }

        $this->prompted = true;

        return $this->stringOrNull($prompt());
    }

    /**
     * Empty means "generate one". A typed one is confirmed, since nothing on
     * screen would reveal a typo before the account is unusable.
     */
    private function askForPassword(): ?string
    {
        $password = $this->stringOrNull(password(
            label: 'Password',
            hint: 'Leave empty to generate a 16-character password.',
            validate: $this->validator('password'),
        ));

        if ($password !== null && $password !== password(label: 'Confirm password')) {
            $this->fail('The passwords do not match.');
        }

        return $password;
    }

    /**
     * @param  array<string, string|null>  $attributes
     * @return array<string, string|null>
     */
    private function validate(array $attributes): array
    {
        $validator = Validator::make($attributes, $this->rules());

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            $this->fail();
        }

        /** @var array<string, string|null> */
        return $validator->validated();
    }

    /**
     * Validates one field with the same rules the final pass uses, so a typo is
     * caught in the prompt rather than after the whole wizard.
     */
    private function validator(string $field): Closure
    {
        return function (string $value) use ($field): ?string {
            if ($this->stringOrNull($value) === null) {
                return null;
            }

            $validator = Validator::make([$field => $value], [$field => $this->rules()[$field]]);

            return $validator->fails() ? (string) $validator->errors()->first($field) : null;
        };
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:32', 'regex:/^[a-z0-9_]+$/i', Rule::unique('users', 'username'), new NotBlockedName],
            'display_name' => ['nullable', 'string', 'max:64', new NotBlockedName],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', Password::min(8)],
            'rank' => ['required', Rule::enum(UserRank::class)],
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
