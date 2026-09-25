<?php

use App\Models\Setting;

if (!function_exists('getFavicon')) {
    /**
     * Get favicon URL (with cache).
     */
    function getFavicon(): string
    {
        $favicon = Setting::getSetting('favicon');

        if (!empty($favicon)) {
            return url($favicon);
        }

        return url('assets/images/favicon.ico');
    }
}

if (!function_exists('getSetting')) {
    /**
     * Get a setting value by key.
     */
    function getSetting(string $key, $default = null)
    {
        $value = Setting::getSetting($key);

        return !empty($value) ? $value : $default;
    }
}