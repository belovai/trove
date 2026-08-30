<?php

declare(strict_types=1);

return [
    'section_system' => 'Rendszer',
    'title' => 'Rendszerbeállítások',
    'saved' => 'A beállítások elmentve.',
    'save' => 'Mentés',

    'block_general' => 'Általános',
    'block_general_hint' => 'A telepítés alapvető adatai.',
    'app_name' => 'Oldal neve',
    'app_name_hint' => 'A böngésző fülén, a fejlécben és a nyitóoldalon jelenik meg.',

    'block_media' => 'Média',
    'block_media_hint' => 'Az újonnan feltöltött médiákra vonatkozó alapértelmezések.',
    'media_default_visibility' => 'Alapértelmezett láthatóság',
    'media_default_visibility_hint' => 'Ez van kiválasztva feltöltéskor annak, aki nem állított be saját alapértelmezést.',

    'block_registration' => 'Regisztráció',
    'block_registration_hint' => 'Ki hozhat létre fiókot, és mit kérünk tőle.',
    'registration_mode' => 'Regisztráció',
    'registration_mode_hint' => 'Zárt módban a regisztrációs űrlap nem érhető el, az útvonalai 404-et adnak. A meglévő fiókokat nem érinti.',
    'registration_mode_open' => 'Nyitott — bárki regisztrálhat',
    'registration_mode_closed' => 'Zárt — senki nem regisztrálhat',
    'registration_email' => 'E-mail cím',
    'registration_email_hint' => 'Kérünk-e e-mail címet az új fiókoktól.',
    'registration_email_optional' => 'Opcionális — a mező látszik, üresen hagyható',
    'registration_email_required' => 'Kötelező — a mezőt ki kell tölteni',
    'registration_email_off' => 'Kikapcsolva — a mező nem jelenik meg',
    'registration_approval' => 'Jóváhagyás szükséges',
    'registration_approval_hint' => 'Az új fiókok Korlátozott ranggal jönnek létre, amit egy adminisztrátor léptet elő, a Normál helyett.',
    'no_recovery_warning' => 'Kikapcsolt e-mail és jóváhagyás nélkül az elfelejtett jelszó nem állítható vissza: a folyamat e-mail alapú. Adminisztrátor tud új jelszót beállítani.',

    'registration_verify' => 'E-mail megerősítés',
    'registration_verify_hint' => 'Kell-e megerősíteni az új címet, és korlátozza-e a fiókot, ha nincs megerősítve.',
    'registration_verify_off' => 'Ki — nem megy megerősítő levél',
    'registration_verify_soft' => 'Puha — kérjük a megerősítést, de semmi nincs tiltva',
    'registration_verify_required' => 'Kötelező — a feltöltéshez és a címkézéshez megerősített cím kell',

    'registration_blocked_names' => 'Tiltott nevek',
    'registration_blocked_names_hint' => 'Soronként egy. A pontosan (kis-nagybetűtől függetlenül) egyező felhasználónév vagy megjelenítendő név elutasításra kerül, regisztrációkor és mindenhol, ahol módosítható.',
];
