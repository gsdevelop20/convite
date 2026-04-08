<?php

namespace App\Http\Requests\Admin\Events;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $startTime = $this->input('start_time');

        if (is_string($startTime) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $startTime) === 1) {
            $this->merge([
                'start_time' => substr($startTime, 0, 5),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'host_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'location_name' => ['required', 'string', 'max:255'],
            'location_address' => ['required', 'string', 'max:255'],
            'invitation_message_template' => ['required', 'string'],
            'invitation_asset_type' => ['required', Rule::in(['text', 'image', 'pdf'])],
            'invitation_asset_file' => [
                Rule::requiredIf(function () {
                    if (! in_array($this->input('invitation_asset_type'), ['image', 'pdf'], true)) {
                        return false;
                    }

                    /** @var Event|null $event */
                    $event = $this->route('event');

                    return ! $event || blank($event->invitation_asset_url);
                }),
                'nullable',
                'file',
                'max:10240',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value) {
                        return;
                    }

                    $assetType = $this->input('invitation_asset_type');
                    $mimeType = $value->getMimeType();

                    if ($assetType === 'image' && ! str_starts_with((string) $mimeType, 'image/')) {
                        $fail('Envie um arquivo de imagem valido para convites do tipo imagem.');
                    }

                    if ($assetType === 'pdf' && $mimeType !== 'application/pdf') {
                        $fail('Envie um arquivo PDF valido para convites do tipo PDF.');
                    }
                },
            ],
            'reminder_days_before' => ['required', 'integer', 'min:0', 'max:365'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'active', 'finished', 'cancelled'])],
        ];
    }
}
