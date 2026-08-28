<?php

declare(strict_types=1);

namespace Modules\Media\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Media\Actions\StoreUploadedMedia;
use Modules\Media\Enums\DuplicatePolicy;
use Modules\Media\Enums\SafetyRating;
use Modules\Media\Enums\Visibility;
use Modules\Tag\Support\ResolvesTagInput;
use Modules\User\Models\User;

final class StoreMediaRequest extends FormRequest
{
    use ResolvesTagInput;

    public function authorize(): bool
    {
        return $this->user()?->can('media.upload') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:'.config('trove.media.max_filesize'),
                // mimetypes: inspects the file's contents. 'mimes:' would trust
                // the extension, which the client controls.
                'mimetypes:'.implode(',', config('trove.media.allowed_mimes')),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'source' => ['nullable', 'string', 'max:255'],
            'visibility' => ['required', Rule::enum(Visibility::class)],
            'safety_rating' => ['required', Rule::enum(SafetyRating::class)],
            'is_anonymous' => ['boolean'],
            'confirm_duplicate' => ['boolean'],
            'tags' => ['array'],
            'tags.*' => ['string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->rejectAnonymousPrivate($validator);
            $this->rejectDuplicate($validator);
            $this->validateTagInput($validator);
        });
    }

    /**
     * A private item is visible only to its uploader and to admins, both of
     * whom already know the author. The combination is meaningless, so it is
     * refused rather than silently ignored.
     */
    private function rejectAnonymousPrivate(Validator $validator): void
    {
        if ($this->boolean('is_anonymous') && $this->input('visibility') === Visibility::Private->value) {
            $validator->errors()->add('is_anonymous', __('media::validation.anonymous_private'));
        }
    }

    private function rejectDuplicate(Validator $validator): void
    {
        if (config('trove.media.duplicate_policy') !== DuplicatePolicy::Reject->value) {
            return;
        }

        if (!$this->hasFile('file') || $validator->errors()->has('file')) {
            return;
        }

        /** @var User $user */
        $user = $this->user();

        $duplicate = app(StoreUploadedMedia::class)->findDuplicateFor(
            hash_file('sha256', $this->file('file')->getRealPath()),
            $user,
        );

        if ($duplicate !== null) {
            $validator->errors()->add('file', __('media::validation.duplicate'));
        }
    }
}
