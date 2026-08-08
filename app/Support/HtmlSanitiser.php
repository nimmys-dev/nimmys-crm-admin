<?php

namespace App\Support;

/**
 * Strips rich text down to a safe subset.
 *
 * TinyMCE posts HTML, which becomes stored XSS the moment a view renders it
 * unescaped. Sanitising on the way in means the database only ever holds
 * safe markup, so every read path is safe by default rather than depending
 * on each template remembering to escape.
 *
 * Deliberately an allow-list: anything not named here is removed, so a tag
 * or attribute nobody anticipated cannot slip through.
 */
class HtmlSanitiser
{
    /**
     * Tags the editor is configured to produce.
     */
    public const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><s><ul><ol><li><h3><h4><h5><blockquote><a><span><table><thead><tbody><tr><th><td>';

    public static function clean(?string $html): ?string
    {
        if (blank($html)) {
            return null;
        }

        $clean = strip_tags($html, self::ALLOWED_TAGS);

        /*
         * The attribute value alternation is ordered quoted-first so a quoted
         * value containing spaces is consumed whole. `\s+` matters: an
         * earlier version used `\s ` (whitespace then a literal space),
         * which required two spaces and let `<p onerror="…">` through.
         */
        $value = '("[^"]*"|\'[^\']*\'|[^\s>]+)';

        // Event handlers (onclick, onerror, …) survive strip_tags because
        // they are attributes, not tags.
        $clean = preg_replace('/\s+on[a-z-]+\s*=\s*'.$value.'/i', '', (string) $clean);

        // javascript:, data: and vbscript: URLs in href/src.
        $clean = preg_replace(
            '/\s+(href|src|xlink:href|formaction)\s*=\s*("|\')?\s*(javascript|data|vbscript)\s*:[^"\'>]*("|\')?/i',
            '',
            (string) $clean
        );

        // style can carry expression() and url(javascript:) in older engines.
        $clean = preg_replace('/\s+style\s*=\s*'.$value.'/i', '', (string) $clean);

        $clean = trim((string) $clean);

        // An editor left untouched still posts an empty paragraph.
        return in_array($clean, ['', '<p></p>', '<p><br></p>'], true) ? null : $clean;
    }
}
