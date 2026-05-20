<?php
/**
 * i18n Helper — Bilingual Translation Support
 * Loads translation strings from JSON files and provides __() global accessor.
 */

function loadTranslations(): array {
    // Priority: session → cookie → default (bn)
    $lang = $_SESSION['lang'] ?? $_COOKIE['bb_lang'] ?? 'bn';
    $lang = in_array($lang, ['en', 'bn']) ? $lang : 'bn';

    $file = ROOT_PATH . "/lang/{$lang}.json";
    if (!file_exists($file)) {
        $file = ROOT_PATH . '/lang/en.json';
    }

    $GLOBALS['__translations'] = json_decode(file_get_contents($file), true) ?? [];
    $GLOBALS['__current_lang'] = $lang;

    return $GLOBALS['__translations'];
}

/**
 * Translate a key. Falls back to the key itself if not found.
 */
function __($key, $fallback = null): string {
    if (!isset($GLOBALS['__translations'])) {
        loadTranslations();
    }
    return $GLOBALS['__translations'][$key] ?? $fallback ?? $key;
}

/**
 * Get current language code
 */
function currentLang(): string {
    return $GLOBALS['__current_lang'] ?? 'bn';
}

/**
 * Echo translated string (shorthand)
 */
function _e($key, $fallback = null): void {
    echo __($key, $fallback);
}
