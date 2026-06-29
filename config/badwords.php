<?php

/**
 * Bad word list configuration.
 *
 * You can:
 *   - Add words directly to the array below.
 *   - Load from a database model by replacing the array with a query result.
 *   - Store an extensive list in a JSON file and decode it here.
 *
 * Words are matched case-insensitively.
 */
return [
    'words' => [
        // Insert your word list here.
        // Example entries (replace with real words for your use case):
        'example_bad_word_1',
        'example_bad_word_2',
        // tip: you can also do: ...json_decode(file_get_contents(base_path('bad-words.json')), true)
    ],
];
