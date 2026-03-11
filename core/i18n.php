<?php
declare(strict_types=1);

/**
 * Minimal i18n runtime for dashboard and setup screens.
 */
function kr_i18n_init(string $root): void
{
    $supported = ['ja', 'en'];
    $lang = 'ja';

    $requested = strtolower((string) ($_GET['lang'] ?? ''));
    if (in_array($requested, $supported, true)) {
        $_SESSION['kr_lang'] = $requested;
        $lang = $requested;
    } elseif (!empty($_SESSION['kr_lang']) && in_array((string) $_SESSION['kr_lang'], $supported, true)) {
        $lang = (string) $_SESSION['kr_lang'];
    }

    $file = $root . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $lang . '.php';
    $translations = file_exists($file) ? require $file : [];
    if (!is_array($translations)) {
        $translations = [];
    }

    $GLOBALS['kr_lang'] = $lang;
    $GLOBALS['kr_translations'] = $translations;
}

function kr_lang(): string
{
    return (string) ($GLOBALS['kr_lang'] ?? 'ja');
}

function kr_t(string $key, ?string $fallback = null): string
{
    $dict = $GLOBALS['kr_translations'] ?? [];
    if (!is_array($dict)) {
        return $fallback ?? $key;
    }

    $value = $dict;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $fallback ?? $key;
        }
        $value = $value[$part];
    }
    return is_string($value) ? $value : ($fallback ?? $key);
}

function kr_tf(string $key, array $vars = [], ?string $fallback = null): string
{
    $text = kr_t($key, $fallback);
    foreach ($vars as $name => $value) {
        $text = str_replace('{' . $name . '}', (string) $value, $text);
    }
    return $text;
}

function kr_url(string $path, array $query = []): string
{
    $url = kr_base_url() . $path;
    $query = array_merge(['lang' => kr_lang()], $query);
    $qs = http_build_query($query);
    return $qs === '' ? $url : ($url . '?' . $qs);
}

function kr_lang_switcher_html(): string
{
    $lang = kr_lang();
    $jaSelected = $lang === 'ja' ? ' selected' : '';
    $enSelected = $lang === 'en' ? ' selected' : '';

    return
        '<form class="lang-switch" method="get" action="">' .
        '<label for="lang" class="lang-label">' . htmlspecialchars(kr_t('common.language', 'Language'), ENT_QUOTES, 'UTF-8') . '</label>' .
        '<select id="lang" name="lang" onchange="this.form.submit()">' .
        '<option value="ja"' . $jaSelected . '>日本語</option>' .
        '<option value="en"' . $enSelected . '>English</option>' .
        '</select>' .
        '<noscript><button type="submit">' . htmlspecialchars(kr_t('common.apply', 'Apply'), ENT_QUOTES, 'UTF-8') . '</button></noscript>' .
        '</form>';
}
