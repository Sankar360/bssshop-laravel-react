<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

if (!function_exists('app_lang')) {
    /**
     * Translate a key using the active language file.
     * Falls back to the key itself if missing.
     */
    function app_lang(string $key, array $params = []): string
    {
        $language = Session::get('user_language', config('app.locale', 'en'));
        $langFile = lang_path($language . '/app.php');

        if (file_exists($langFile)) {
            $translations = require $langFile;
            $text = $translations[$key] ?? $key;
        } else {
            $text = $key;
        }

        foreach ($params as $paramKey => $paramValue) {
            $text = str_replace('{' . $paramKey . '}', $paramValue, $text);
        }

        return $text;
    }
}

if (!function_exists('lang_js')) {
    /**
     * Return all translations as JSON — handy for JS bootstrapping.
     */
    function lang_js(): string
    {
        $language = Session::get('user_language', config('app.locale', 'en'));
        $langFile = lang_path($language . '/app.php');

        if (file_exists($langFile)) {
            return json_encode(require $langFile);
        }

        return '{}';
    }
}