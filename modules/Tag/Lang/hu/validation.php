<?php

declare(strict_types=1);

return [
    'tag_name_empty' => 'A címke neve nem lehet üres.',
    'tag_name_reserved_character' => 'A címke neve nem tartalmazhat ":character" karaktert.',
    'tag_name_reserved_prefix' => 'A címke neve nem kezdődhet ":character" karakterrel.',
    'tag_name_reserved_word' => 'A ":word" foglalt, nem használható címkenévként.',
    'alias_collides_with_tag' => 'A ":name" már létezik címkeként.',
    'alias_already_taken' => 'A ":name" már egy másik címke szinonimája.',
    'self_implication' => 'A ":name" nem vonhatja maga után önmagát.',
    'implication_cycle' => 'A ":from" → ":to" kapcsolat kört zárna be.',
    'implication_exists' => 'A ":from" már maga után vonja a ":to" címkét.',
    'unknown_category' => 'Nincs ":category" nevű címkekategória.',
    'category_prefix_ignored' => 'A ":name" már létezik itt: :category; a kategóriája változatlan maradt.',
    'taxonomy_not_json' => 'Ez a fájl nem érvényes taxonómia dokumentum.',
];
