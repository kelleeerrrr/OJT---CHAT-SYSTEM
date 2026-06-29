<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class BadWordFilter
{
    /**
     * English profanity dictionary.
     */
    private array $englishWords = [];

    /**
     * Tagalog profanity dictionary.
     */
    private array $tagalogWords = [];

    /**
     * Regex patterns for obfuscated profanity.
     */
    private array $patterns = [];

    /**
     * Cache key.
     */
    private const CACHE_KEY = 'badwords.dictionary';

    public function __construct()
    {
        $dictionary = Cache::rememberForever(self::CACHE_KEY, function () {

            return [

                'english' => $this->loadJson(
                    storage_path('app/profanity/english.json')
                ),

                'tagalog' => $this->loadJson(
                    storage_path('app/profanity/tagalog.json')
                ),

                'patterns' => $this->loadJson(
                    storage_path('app/profanity/patterns.json')
                ),

            ];
        });

        $this->englishWords = $dictionary['english'];
        $this->tagalogWords = $dictionary['tagalog'];
        $this->patterns = $dictionary['patterns'];
    }

    /**
     * Load a JSON file safely.
     */
    private function loadJson(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $json = json_decode(file_get_contents($path), true);

        return is_array($json)
            ? $json
            : [];
    }

    /**
     * Normalize text for detection.
     */
    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);

        // Convert common leetspeak characters.
        $text = str_replace(
            [
                '@',
                '4',
                '0',
                '1',
                '3',
                '$',
                '5',
                '7'
            ],
            [
                'a',
                'a',
                'o',
                'i',
                'e',
                's',
                's',
                't'
            ],
            $text
        );

        return $text;
    }

    /**
     * Returns true if profanity exists.
     */
    public function contains(string $text): bool
    {
        $normalized = $this->normalize($text);

        /*
        |--------------------------------------------------------------------------
        | Exact Dictionary Match
        |--------------------------------------------------------------------------
        */

        foreach (array_merge($this->englishWords, $this->tagalogWords) as $word) {

            $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';

            if (preg_match($pattern, $normalized)) {
                return true;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Regex Pattern Match
        |--------------------------------------------------------------------------
        */

        foreach ($this->patterns as $pattern) {

            if (
                isset($pattern['regex']) &&
                preg_match('/' . $pattern['regex'] . '/iu', $normalized)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace profanity with asterisks.
     */
    public function mask(string $text): string
    {
        /*
        |--------------------------------------------------------------------------
        | Exact Dictionary Words
        |--------------------------------------------------------------------------
        */

        foreach (array_merge($this->englishWords, $this->tagalogWords) as $word) {

            $pattern = '/\b' . preg_quote($word, '/') . '\b/iu';

            $text = preg_replace_callback(
                $pattern,
                function ($matches) {

                    return str_repeat(
                        '*',
                        mb_strlen($matches[0])
                    );

                },
                $text
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Regex Patterns
        |--------------------------------------------------------------------------
        */

        foreach ($this->patterns as $pattern) {

            if (!isset($pattern['regex'])) {
                continue;
            }

            $text = preg_replace_callback(
                '/' . $pattern['regex'] . '/iu',
                function ($matches) {

                    return str_repeat(
                        '*',
                        mb_strlen($matches[0])
                    );

                },
                $text
            );
        }

        return $text;
    }

    /**
     * Process the message.
     *
     * Returns:
     * [
     *      'text' => 'filtered message',
     *      'has_bad_words' => true|false
     * ]
     */
    public function process(string $text): array
    {
        $hasBadWords = $this->contains($text);

        return [

            'text' => $hasBadWords
                ? $this->mask($text)
                : $text,

            'has_bad_words' => $hasBadWords,

        ];
    }

    /**
     * Clears only the profanity dictionary cache.
     *
     * Call this after editing english.json,
     * tagalog.json or patterns.json.
     */
    public static function clearDictionaryCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}