<?php

declare(strict_types=1);

return [
    'section_mail' => 'E-mail',
    'title' => 'E-mail beállítások',
    'saved' => 'Az e-mail beállítások mentve.',
    'save' => 'Mentés',

    'block_delivery' => 'Küldés',
    'block_delivery_hint' => 'Hogyan küld levelet ez a telepítés. Kikapcsolt küldés mellett semmi nem megy ki.',
    'enabled' => 'Levélküldés',
    'enabled_hint' => 'Kikapcsolva egyetlen üzenet sem hagyja el az alkalmazást: a megerősítő, a jelszó-visszaállító és az értesítő levelek is elmaradnak.',
    'transport' => 'Küldési mód',
    'transport_log' => 'Napló — az üzenetek az alkalmazás naplójába kerülnek',
    'transport_smtp' => 'SMTP szerver',

    'block_sender' => 'Feladó',
    'block_sender_hint' => 'Amit a címzett a From és Reply-To fejlécben lát.',
    'from_address' => 'Feladó cím',
    'from_address_hint' => 'Üresen hagyva a noreply@ és az oldal domainje a feladó.',
    'from_name' => 'Feladó név',
    'from_name_hint' => 'Üresen hagyva az oldal neve.',
    'reply_to' => 'Válaszcím',
    'admin_address' => 'Adminisztrátor cím',
    'admin_address_hint' => 'Ide megy az értesítés a jóváhagyásra váró fiókokról. Üresen hagyva nem megy értesítés.',

    'block_smtp' => 'SMTP szerver',
    'smtp_host' => 'Kiszolgáló',
    'smtp_port' => 'Port',
    'smtp_encryption' => 'Titkosítás',
    'smtp_encryption_none' => 'Nincs',
    'smtp_encryption_tls' => 'STARTTLS',
    'smtp_encryption_ssl' => 'Implicit TLS (SMTPS)',
    'smtp_username' => 'Felhasználónév',
    'smtp_password' => 'Jelszó',
    'smtp_password_set' => 'Van eltárolt jelszó. Hagyd üresen, ha nem változtatod.',
    'smtp_timeout' => 'Időtúllépés (másodperc)',

    'block_test' => 'Teszt üzenet',
    'block_test_hint' => 'Azonnal, sor nélkül küld, és visszaadja, amit a kiszolgáló válaszolt.',
    'test_email' => 'Teszt üzenet küldése erre a címre',
    'test_send' => 'Teszt üzenet küldése',
    'test_subject' => ':app teszt üzenet',
    'test_body' => 'Ez egy teszt üzenet a(z) :app rendszerből. Ha olvasod, a küldés működik.',
    'test_sent' => 'A teszt üzenet elküldve ide: :email.',
    'test_failed' => 'A küldés nem sikerült: :error',
    'not_deliverable' => 'A levélküldés ki van kapcsolva vagy a küldési mód nincs beállítva, így semmi nem megy ki.',
];
