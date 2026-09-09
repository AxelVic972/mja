<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactConfirmation;
use App\Mail\ContactNotification;
use App\Models\Article;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Partenaire;
use App\Models\Project;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class HomeController extends Controller
{
    public function index()
    {
        $articles    = Article::publie()->limit(3)->get();
        $events      = Event::avenir()->limit(3)->get();
        $projects    = Project::actif()->limit(3)->get();
        $partenaires = Partenaire::actif()->orderBy('ordre')->orderBy('nom')->get();

        return view('home', [
            'articles'    => $articles,
            'events'      => $events,
            'projects'    => $projects,
            'partenaires' => $partenaires,
            'carrousel'   => $this->photosCarrousel(),
        ]);
    }

    /**
     * Photos du carrousel d'accueil.
     *
     * Elles sont lues directement dans public/images/carrousel : déposer ou
     * retirer un fichier suffit à modifier le carrousel, sans passer par le
     * back-office ni par une migration. À défaut, on retombe sur les photos de
     * groupe du kit de communication pour que la page ne soit jamais vide.
     *
     * @return array<int, string> URLs prêtes à l'emploi
     */
    private function photosCarrousel(): array
    {
        $dossier = public_path('images/carrousel');

        $fichiers = is_dir($dossier)
            ? glob($dossier . '/*.{jpg,JPG,jpeg,JPEG,png,PNG,webp,WEBP}', GLOB_BRACE) ?: []
            : [];

        if ($fichiers) {
            sort($fichiers);

            return array_map(
                fn ($chemin) => asset('images/carrousel/' . rawurlencode(basename($chemin))),
                $fichiers,
            );
        }

        // Repli : trois visuels du kit, choisis pour être réellement différents.
        // Le dossier kit/ contient surtout une même soirée photographiée en
        // rafale (equipe-01/03/04/05/06, IMG_3366 à 3369, Groupe Pic 2026 sont
        // le même selfie) : n'en garder qu'un évite un carrousel qui semble
        // figé. equipe-02.jpg est par ailleurs un doublon de MJA Beach Party 2.
        $repli = ['Groupe Pic 2026.JPG', 'MJA Beach Party 2.jpg', 'membres-01.jpg'];

        return collect($repli)
            ->filter(fn ($nom) => is_file(public_path('images/kit/' . $nom)))
            ->map(fn ($nom) => asset('images/kit/' . rawurlencode($nom)))
            ->values()
            ->all();
    }

    public function about()
    {
        $members = TeamMember::actif()->get();
        return view('about', compact('members'));
    }

    public function sns()
    {
        $snsEvents = Event::publie()->where(function ($q) {
            $q->where('titre', 'like', '%Ti Dèj%')
              ->orWhere('titre', 'like', '%Santé%')
              ->orWhere('titre', 'like', '%Sport%')
              ->orWhere('titre', 'like', '%SNS%')
              ->orWhere('titre', 'like', '%Madin%Santé%');
        })->get();
        $prochains = Event::avenir()->limit(4)->get();
        return view('sns', compact('snsEvents', 'prochains'));
    }

    public function mentionsLegales()
    {
        return view('legal.mentions-legales');
    }

    public function confidentialite()
    {
        return view('legal.confidentialite');
    }

    public function contact(Request $request)
    {
        $request->session()->put('contact_form_started_at', now()->getTimestamp());

        return view('contact');
    }

    public function contactStore(ContactRequest $request)
    {
        $validated = $request->validated();
        unset($validated['cf-turnstile-response']);

        $indicatif = $validated['indicatif'] ?? null;
        unset($validated['indicatif']);
        if (! empty($validated['telephone'])) {
            $validated['telephone'] = trim(($indicatif ? $indicatif . ' ' : '') . $validated['telephone']);
        }

        $validated['source_id'] = $request->session()->get('mja_source_id');

        $contact = Contact::create($validated);

        try {
            Mail::to($contact->email)->send(new ContactConfirmation($contact));
            Mail::to(\App\Models\Setting::notificationEmails())->send(new ContactNotification($contact));
        } catch (\Exception $e) {
            \Log::error('Mail contact failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.');
    }
}
