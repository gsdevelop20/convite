<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GuestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Guests\ImportGuestsRequest;
use App\Http\Requests\Admin\Guests\StoreGuestRequest;
use App\Models\Event;
use App\Models\Guest;
use App\Services\PhoneNormalizer;
use Illuminate\Validation\ValidationException;

class GuestController extends Controller
{
    public function store(StoreGuestRequest $request, Event $event, PhoneNormalizer $phoneNormalizer)
    {
        $normalized = $phoneNormalizer->normalize($request->string('phone')->toString());

        if ($event->guests()->where('normalized_phone', $normalized)->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'Ja existe um convidado com esse telefone neste evento.',
            ]);
        }

        $event->guests()->create([
            'name' => $request->string('name')->toString(),
            'phone_e164' => '+'.$normalized,
            'normalized_phone' => $normalized,
            'current_status' => GuestStatus::Pending,
        ]);

        return back()->with('success', 'Convidado salvo com sucesso.');
    }

    public function update(StoreGuestRequest $request, Event $event, Guest $guest, PhoneNormalizer $phoneNormalizer)
    {
        abort_unless($guest->event_id === $event->id, 404);

        $normalized = $phoneNormalizer->normalize($request->string('phone')->toString());

        if ($event->guests()->where('normalized_phone', $normalized)->whereKeyNot($guest->id)->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'Ja existe um convidado com esse telefone neste evento.',
            ]);
        }

        $guest->update([
            'name' => $request->string('name')->toString(),
            'phone_e164' => '+'.$normalized,
            'normalized_phone' => $normalized,
        ]);

        return back()->with('success', 'Convidado atualizado com sucesso.');
    }

    public function destroy(Event $event, Guest $guest)
    {
        abort_unless($guest->event_id === $event->id, 404);

        $guest->delete();

        return back()->with('success', 'Convidado removido com sucesso.');
    }

    public function import(ImportGuestsRequest $request, Event $event, PhoneNormalizer $phoneNormalizer)
    {
        $duplicates = 0;

        collect(preg_split('/\r\n|\r|\n/', $request->string('rows')->toString()))
            ->filter()
            ->each(function (string $line) use ($event, $phoneNormalizer, &$duplicates): void {
                [$name, $phone] = array_pad(str_getcsv($line, ';'), 2, null);
                [$fallbackName, $fallbackPhone] = array_pad(str_getcsv($line, ','), 2, null);

                $name = $name ?: $fallbackName;
                $phone = $phone ?: $fallbackPhone;

                if (! $name || ! $phone) {
                    return;
                }

                $normalized = $phoneNormalizer->normalize($phone);

                if ($event->guests()->where('normalized_phone', $normalized)->exists()) {
                    $duplicates++;

                    return;
                }

                $event->guests()->create([
                    'name' => trim($name),
                    'phone_e164' => '+'.$normalized,
                    'normalized_phone' => $normalized,
                    'current_status' => GuestStatus::Pending,
                ]);
            });

        $message = $duplicates > 0
            ? "Importacao concluida. {$duplicates} telefone(s) duplicado(s) foram ignorados."
            : 'Importacao concluida.';

        return back()->with('success', $message);
    }
}
