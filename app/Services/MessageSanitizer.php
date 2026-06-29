<?php

namespace App\Services;

class MessageSanitizer
{
    /**
     * Allowed HTML tags in chat messages — none by default.
     * Set to an empty string to strip ALL tags.
     */
    private string $allowedTags = '';

    /**
     * Sanitize a chat message body:
     *   1. Strip all HTML tags (prevents XSS / HTML injection)
     *   2. Trim whitespace
     *   3. Collapse repeated whitespace / newlines
     *   4. Enforce a maximum length
     */
    public function sanitize(string $input, int $maxLength = 2000): string
    {
        // 1. Decode HTML entities first (prevents double-encoded attacks)
        $text = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 2. Strip ALL HTML/XML tags
        $text = strip_tags($text, $this->allowedTags);

        // 3. Remove null bytes and non-printable characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        // 4. Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // 5. Collapse more than 2 consecutive newlines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // 6. Trim and enforce length
        $text = trim($text);
        $text = mb_substr($text, 0, $maxLength);

        return $text;
    }

    /**
     * Re-encode for safe HTML output (use in Blade when rendering messages).
     * Always pass message body through this before echoing in HTML context.
     */
    public static function escape(string $text): string
    {
        return e($text);      // Laravel's e() calls htmlspecialchars with ENT_QUOTES
    }

    /**
     * Check that the sanitized text is not empty.
     */
    public function isEmpty(string $sanitized): bool
    {
        return $sanitized === '';
    }
}
