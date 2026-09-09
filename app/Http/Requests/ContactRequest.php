<?php

namespace App\Http\Requests;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Validation et protections dédiées au formulaire de contact public. */
class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $regles = [
            'nom'       => 'required|string|max:100',
            'email'     => 'required|email',
            'indicatif' => 'nullable|string|max:6',
            'telephone' => 'nullable|string|max:30',
            'sujet'     => 'required|string|max:150',
            'message'   => 'required|string|min:10',
        ];

        if (config('services.turnstile.enabled')) {
            $regles['cf-turnstile-response'] = ['bail', 'required', 'string', new Turnstile($this->ip())];
        }

        return $regles;
    }

    public function messages(): array
    {
        return [
            'nom.required'     => 'Le nom est obligatoire.',
            'nom.max'          => 'Le nom ne doit pas dépasser 100 caractères.',
            'email.required'   => "L'adresse email est obligatoire.",
            'email.email'      => "L'adresse email n'est pas valide.",
            'sujet.required'   => 'Le sujet est obligatoire.',
            'sujet.max'        => 'Le sujet ne doit pas dépasser 150 caractères.',
            'message.required' => 'Le message est obligatoire.',
            'message.min'      => 'Le message doit contenir au moins 10 caractères.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $debut = $this->session()->get('contact_form_started_at');
            $delaiMinimum = (int) config('mja.contact_min_fill_seconds', 3);

            if (! is_numeric($debut) || now()->getTimestamp() - (int) $debut < $delaiMinimum) {
                $validator->errors()->add('formulaire', 'Veuillez prendre un instant pour vérifier le formulaire avant de l’envoyer.');
            }
        });
    }

    protected function passedValidation(): void
    {
        $this->session()->forget('contact_form_started_at');
    }
}
