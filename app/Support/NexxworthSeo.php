<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

class NexxworthSeo
{
    private const PAGES = [
        'home' => [
            'it' => ['route' => 'home', 'title' => 'Informazioni, consulenza e soluzioni', 'description' => 'Nexxworth Consulting a Mantova: assistenza per stranieri, visti, ricongiungimento familiare, traduzioni, legalizzazioni e servizi CAF.'],
            'en' => ['route' => 'nexxworth.en.home', 'title' => 'Information, advice and solutions', 'description' => 'Nexxworth Consulting in Mantua: immigration support, visas, family reunification, translations, document legalization and tax assistance.'],
        ],
        'about' => [
            'it' => ['route' => 'nexxworth.about', 'title' => 'Chi siamo', 'description' => 'La storia e la missione di Nexxworth Consulting: supporto concreto per immigrazione, trasferimento e integrazione in Italia.'],
            'en' => ['route' => 'nexxworth.en.about', 'title' => 'About us', 'description' => 'Discover Nexxworth Consulting and our personal approach to immigration, relocation and settling in Italy.'],
        ],
        'services' => [
            'it' => ['route' => 'nexxworth.services', 'title' => 'Servizi', 'description' => 'Scopri i servizi Nexxworth Consulting: pratiche amministrative, visti, traduzioni, ricongiungimento familiare e dichiarazioni fiscali.'],
            'en' => ['route' => 'nexxworth.en.services', 'title' => 'Services', 'description' => 'Explore Nexxworth Consulting services: administrative support, visas, translations, family reunification and tax assistance in Italy.'],
        ],
        'partners' => [
            'it' => ['route' => 'nexxworth.partners', 'title' => 'Collaborazioni', 'description' => 'Servizi offerti attraverso le collaborazioni Nexxworth: formazione, sicurezza sul lavoro, lingue, assistenza legale, assicurazioni e investimenti.'],
            'en' => ['route' => 'nexxworth.en.partners', 'title' => 'Our network', 'description' => 'Nexxworth partners offer training, workplace safety, language courses, legal assistance, insurance and property opportunities.'],
        ],
        'contact' => [
            'it' => ['route' => 'nexxworth.contact', 'title' => 'Contatti', 'description' => 'Contatta Nexxworth Consulting a Mantova. Via Cremona 29A/int. 3, telefono e WhatsApp +39 366 430 0443, email ksd.servizi@gmail.com.'],
            'en' => ['route' => 'nexxworth.en.contact', 'title' => 'Contact', 'description' => 'Contact Nexxworth Consulting in Mantua, Italy. Visit Via Cremona 29A/int. 3, call or WhatsApp +39 366 430 0443.'],
        ],
        'privacy' => [
            'it' => ['route' => 'nexxworth.privacy', 'title' => 'Informativa privacy', 'description' => 'Informazioni sul trattamento dei dati personali da parte di Nexxworth Consulting di Singh Kamaljeet.'],
            'en' => ['route' => 'nexxworth.en.privacy', 'title' => 'Privacy notice', 'description' => 'How Nexxworth Consulting di Singh Kamaljeet processes personal data under the GDPR.'],
        ],
        'status' => [
            'it' => ['route' => 'nexxworth.status', 'title' => 'Controlla la tua pratica', 'description' => 'Inserisci il codice di 10 caratteri per controllare lo stato della tua pratica Nexxworth in modo semplice e sicuro.'],
            'en' => ['route' => 'nexxworth.en.status', 'title' => 'Check your case status', 'description' => 'Enter your 10-character tracking code to check your Nexxworth case status quickly and securely.'],
        ],
    ];

    /** @return array{key: string, locale: string, title: string, description: string, canonical: string, alternate_it: string, alternate_en: string}|null */
    public static function current(): ?array
    {
        if (config('branding.customer_code') !== 'nexxworth') {
            return null;
        }

        $routeName = Route::currentRouteName();

        foreach (self::PAGES as $key => $translations) {
            foreach ($translations as $locale => $translation) {
                if ($translation['route'] === $routeName) {
                    return [
                        'key' => $key,
                        'locale' => $locale,
                        'title' => $translation['title'].' | Nexxworth Consulting',
                        'description' => $translation['description'],
                        'canonical' => route($translation['route']),
                        'alternate_it' => route($translations['it']['route']),
                        'alternate_en' => route($translations['en']['route']),
                    ];
                }
            }
        }

        return null;
    }

    /** @return array<int, array{it: string, en: string}> */
    public static function sitemap(): array
    {
        return array_map(fn (array $page): array => [
            'it' => route($page['it']['route']),
            'en' => route($page['en']['route']),
        ], array_values(self::PAGES));
    }
}
