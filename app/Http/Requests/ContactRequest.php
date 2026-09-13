<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Validation des données du formulaire de contact public. */
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
}
