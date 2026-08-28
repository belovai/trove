<?php

declare(strict_types=1);

return [
    'tag_name_empty' => 'A tag name cannot be empty.',
    'tag_name_reserved_character' => 'A tag name cannot contain ":character".',
    'tag_name_reserved_prefix' => 'A tag name cannot start with ":character".',
    'tag_name_reserved_word' => '":word" is reserved and cannot be used as a tag name.',
    'alias_collides_with_tag' => '":name" already exists as a tag.',
    'alias_already_taken' => '":name" is already an alias of another tag.',
    'self_implication' => '":name" cannot imply itself.',
    'implication_cycle' => '":from" implying ":to" would create a loop.',
    'implication_exists' => '":from" already implies ":to".',
    'unknown_category' => 'There is no tag category called ":category".',
    'category_prefix_ignored' => '":name" already exists in :category; its category was left unchanged.',
    'taxonomy_not_json' => 'That file is not a valid taxonomy document.',
];
