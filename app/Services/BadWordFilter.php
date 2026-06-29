<?php

namespace App\Services;

class BadWordFilter
{
    /**
     * Configurable word list — extend via config/badwords.php or your database.
     * Words are matched case-insensitively and as whole words or substrings
     * depending on the strictness setting.
     */
    private array $words;

    public function __construct()
    {
        // Load from config; fall back to a small default list for bootstrap.
        $this->words = config('badwords.words', [
            'badword1',
            'badword2',
            // Add your own — or load from DB:
            // ...BadWord::pluck('word')->toArray()
        ]);
    }

    /**
     * Returns true if the text contains any bad words.
     */
    public function contains(string $text): bool
    {
        $lower = mb_strtolower($text);

        foreach ($this->words as $word) {
            if (str_contains($lower, mb_strtolower($word))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replaces bad words with asterisks, preserving word length.
     *
     * e.g. "You badword1 fool" → "You ******** fool"
     */
    public function mask(string $text): string
    {
        foreach ($this->words as $word) {
            $pattern = '/' . preg_quote($word, '/') . '/iu';
            $text = preg_replace_callback($pattern, function ($matches) {
                return str_repeat('*', mb_strlen($matches[0]));
            }, $text);
        }

        return $text;
    }

    /**
     * Returns both the masked text and whether bad words were found.
     *
     * @return array{text: string, has_bad_words: bool}
     */
    public function process(string $text): array
    {
        $hasBadWords = $this->contains($text);

        return [
            'text'          => $hasBadWords ? $this->mask($text) : $text,
            'has_bad_words' => $hasBadWords,
        ];
    }
}
