<?php

return [
    'sections' => [
        'appointments' => [
            'label' => 'Appuntamenti',
            'description' => 'Assegnazioni, cambi di stato, riprogrammazioni e promemoria.',
            'defaults' => ['enabled' => true, 'mail' => true, 'realtime' => true],
            'supports_reminders' => true,
        ],
        'practices' => [
            'label' => 'Pratiche',
            'description' => 'Assegnazioni e cambi di stato delle pratiche.',
            'defaults' => ['enabled' => true, 'mail' => false, 'realtime' => true],
            'supports_reminders' => false,
        ],
        'deadlines' => [
            'label' => 'Scadenze',
            'description' => 'Assegnazioni, modifiche, stati e promemoria delle scadenze.',
            'defaults' => ['enabled' => true, 'mail' => true, 'realtime' => true],
            'supports_reminders' => true,
        ],
    ],
    'reminder_options' => [
        10080 => '7 giorni prima',
        1440 => '24 ore prima',
        60 => '1 ora prima',
        0 => 'All’orario',
    ],
    'default_reminder_offsets' => [1440, 60],
    'business_timezone' => env('BUSINESS_TIMEZONE', 'Europe/Rome'),
];
