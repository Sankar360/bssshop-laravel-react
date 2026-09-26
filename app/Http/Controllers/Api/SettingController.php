<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * Return public settings needed by the front-end.
     */
    public function publicSettings()
    {
        $keys = [
            'site_name',
            'site_description',
            'site_email',
            'site_phone',
            'site_address',
            'social_facebook',
            'social_twitter',
            'social_instagram',
            'social_youtube',
            'social_linkedin',
            'favicon',   // ✅ added

        ];

        $settings = Setting::whereIn('key', $keys)->pluck('value', 'key');

        // Normalize favicon to a browser-usable URL
        if (!empty($settings['favicon'])) {
            $settings['favicon'] = $this->normalizeFavicon($settings['favicon']);
        }

        return $this->successResponse('Settings loaded', $settings->toArray());
    }

    private function normalizeFavicon(string $value): string
    {
        $value = trim($value);

        // Already an absolute URL (https://...)
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        // Already an absolute path (/storage/... or /favicon.ico)
        if (str_starts_with($value, '/')) {
            return $value;
        }

        // Strip any accidental leading "storage/" so we don't double it
        $value = preg_replace('#^storage/#i', '', $value);

        return '/storage/' . ltrim($value, '/');
    }
}
