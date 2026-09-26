<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Language;
use App\Models\Timezone;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AdminSettingController extends Controller
{
    protected $settingModel;
    protected $languageModel;
    protected $timezoneModel;
    protected $themeModel;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->settingModel  = new Setting();
        $this->languageModel = new Language();
        $this->timezoneModel = new Timezone();
        $this->themeModel    = new Theme();
    }

    // ==================== SETTINGS ====================

    public function index()
{
    $settings = $this->getAllSettings();
    $sections = $this->getSettingSections();

    foreach ($sections as &$section) {
        foreach ($section['fields'] as $key => &$field) {
            $value = $settings[$key] ?? ($field['default'] ?? '');

            // ✅ Convert file paths to full browser URLs
            if (($field['type'] ?? '') === 'file' && !empty($value)) {
                if (!preg_match('#^https?://#i', $value)) {
                    $value = Storage::url($value);   // → '/storage/settings/favicon.ico'
                }
            }

            $field['value'] = $value;
        }
    }
    unset($section, $field);

    return response()->json([
        'success' => true,
        'data' => [
            'settings' => $settings,
            'sections' => $sections,
        ],
    ]);
}

    /**
     * Update settings.
     * Accepts multipart/form-data so file uploads (favicon, logo) work.
     */
    public function update(Request $request)
    {
        Log::debug('=== SETTINGS UPDATE DEBUG ===');
        Log::debug('POST keys: ' . json_encode(array_keys($request->all())));

        try {
            // File fields are handled separately — exclude them from the text loop.
            $settings = $request->except(['_token', 'favicon', 'logo']);

            // Ensure unchecked checkboxes get persisted as '0'
            $checkboxFields = $this->getCheckboxFields();
            foreach ($checkboxFields as $fieldKey) {
                if (!array_key_exists($fieldKey, $settings)) {
                    $settings[$fieldKey] = '0';
                }
            }

            // ── Persist every text/number/checkbox field ──
            foreach ($settings as $key => $value) {
                // Skip UploadedFile instances just in case
                if ($value instanceof UploadedFile) {
                    continue;
                }

                // ✅ Coerce null → '' so NOT NULL columns don't blow up
                if ($value === null) {
                    $value = '';
                }

                // 'on' comes from raw HTML checkboxes without JS
                if ($value === 'on') {
                    $value = '1';
                }

                Log::debug('Saving setting: ' . $key . ' => ' . var_export($value, true));
                $this->settingModel->setSetting($key, $value);
            }

            // ── Handle favicon upload ──
            if ($request->hasFile('favicon')) {
                $result = $this->handleFaviconUpload($request->file('favicon'));
                if ($result !== true) {
                    return $result; // error response
                }
            }

            // ── Handle logo upload ──
            if ($request->hasFile('logo')) {
                $result = $this->handleLogoUpload($request->file('logo'));
                if ($result !== true) {
                    return $result; // error response
                }
            }

            Log::info('Settings updated by admin');

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Settings update error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload / replace favicon.
     *
     * @return true|\Illuminate\Http\JsonResponse
     */
    private function handleFaviconUpload(UploadedFile $favicon)
    {
        Log::debug('Favicon received: ' . $favicon->getClientOriginalName());

        $allowedTypes = [
            'image/x-icon',
            'image/vnd.microsoft.icon',
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/gif',
        ];

        if (!in_array($favicon->getMimeType(), $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid favicon type. Please upload ICO, PNG, JPG or GIF.',
            ], 422);
        }

        if ($favicon->getSize() > 2 * 1024 * 1024) {
            return response()->json([
                'success' => false,
                'message' => 'Favicon must be smaller than 2MB.',
            ], 422);
        }

        // Delete old favicon
        $oldFavicon = $this->settingModel->getSetting('favicon');
        if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
            Storage::disk('public')->delete($oldFavicon);
        }

        $extension = strtolower($favicon->getClientOriginalExtension() ?: 'png');
        $newName   = 'favicon.' . $extension;
        $path      = $favicon->storeAs('settings', $newName, 'public');

        Log::debug('Favicon saved: ' . $path);

        $this->settingModel->setSetting('favicon', $path);
        $this->optimizeFavicon($path);

        return true;
    }

    /**
     * Upload / replace logo.
     *
     * @return true|\Illuminate\Http\JsonResponse
     */
    private function handleLogoUpload(UploadedFile $logo)
    {
        Log::debug('Logo received: ' . $logo->getClientOriginalName());

        $allowedTypes = [
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/gif',
            'image/webp',
            'image/svg+xml',
        ];

        if (!in_array($logo->getMimeType(), $allowedTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid logo type. Please upload PNG, JPG, GIF, WEBP or SVG.',
            ], 422);
        }

        if ($logo->getSize() > 5 * 1024 * 1024) {
            return response()->json([
                'success' => false,
                'message' => 'Logo must be smaller than 5MB.',
            ], 422);
        }

        // Delete old logo
        $oldLogo = $this->settingModel->getSetting('logo');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        // Keep original extension — forcing .png would break SVG/WEBP
        $extension = strtolower($logo->getClientOriginalExtension() ?: 'png');
        $newName   = 'logo.' . $extension;
        $path      = $logo->storeAs('settings', $newName, 'public');

        Log::debug('Logo saved: ' . $path);

        $this->settingModel->setSetting('logo', $path);

        return true;
    }

    /**
     * Optimize favicon to 32x32.
     */
    private function optimizeFavicon(string $filePath): void
    {
        $fullPath = Storage::disk('public')->path($filePath);

        if (!file_exists($fullPath)) {
            return;
        }

        $info = @getimagesize($fullPath);
        if (!$info || ($info[0] <= 32 && $info[1] <= 32)) {
            return;
        }

        $mimeType = mime_content_type($fullPath);
        $image    = null;

        switch ($mimeType) {
            case 'image/png':
                $image = imagecreatefrompng($fullPath);
                break;
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($fullPath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($fullPath);
                break;
            default:
                return;
        }

        if (!$image) {
            return;
        }

        $resized = imagecreatetruecolor(32, 32);

        if ($mimeType === 'image/png') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, 32, 32, $info[0], $info[1]);

        switch ($mimeType) {
            case 'image/png':
                imagepng($resized, $fullPath, 9);
                break;
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($resized, $fullPath, 80);
                break;
            case 'image/gif':
                imagegif($resized, $fullPath);
                break;
        }

        imagedestroy($image);
        imagedestroy($resized);
    }

    private function getCheckboxFields(): array
    {
        $sections = $this->getSettingSections();
        $checkboxFields = [];

        foreach ($sections as $section) {
            foreach ($section['fields'] as $key => $field) {
                if ($field['type'] === 'checkbox') {
                    $checkboxFields[] = $key;
                }
            }
        }

        return $checkboxFields;
    }

    private function getAllSettings(): array
    {
        $settings = Setting::all();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->value;
        }

        return $result;
    }

    private function getSettingSections(): array
    {
        return [
            'general' => [
                'title' => 'General Settings',
                'icon'  => 'bi bi-gear',
                'fields' => [
                    'site_name'        => ['label' => 'Site Name',        'type' => 'text',     'default' => 'BSSShop'],
                    'site_description' => ['label' => 'Site Description', 'type' => 'textarea', 'default' => 'Your one-stop shop for everything'],
                    'site_email'       => ['label' => 'Site Email',       'type' => 'email',    'default' => 'info@bssshop.com'],
                    'site_phone'       => ['label' => 'Site Phone',       'type' => 'text',     'default' => '+1 (555) 123-4567'],
                    'site_address'     => ['label' => 'Site Address',     'type' => 'textarea', 'default' => '123 Main Street, New York, NY 10001'],
                ],
            ],
            'branding' => [
                'title' => 'Branding',
                'icon'  => 'bi bi-image',
                'fields' => [
                    'favicon' => [
                        'label'       => 'Favicon',
                        'type'        => 'file',
                        'default'     => '',
                        'description' => 'Square PNG/ICO recommended, 32x32 or 64x64.',
                    ],
                    'logo' => [
                        'label'   => 'Logo',
                        'type'    => 'file',
                        'default' => '',
                    ],
                ],
            ],
            'store' => [
                'title' => 'Store Settings',
                'icon'  => 'bi bi-shop',
                'fields' => [
                    'store_currency' => [
                        'label'   => 'Currency',
                        'type'    => 'select',
                        'options' => ['USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP', 'INR' => 'INR'],
                        'default' => 'USD',
                    ],
                    'store_tax'           => ['label' => 'Tax Rate (%)',            'type' => 'number', 'default' => '10'],
                    'store_shipping'      => ['label' => 'Shipping Cost',           'type' => 'number', 'default' => '5.99'],
                    'store_free_shipping' => ['label' => 'Free Shipping Threshold', 'type' => 'number', 'default' => '50'],
                ],
            ],
            'payment' => [
                'title' => 'Payment Settings',
                'icon'  => 'bi bi-credit-card',
                'fields' => [
                    'payment_stripe_enabled'   => ['label' => 'Enable Stripe',       'type' => 'checkbox', 'default' => '0'],
                    'payment_stripe_key'       => ['label' => 'Stripe Public Key',   'type' => 'text',     'default' => ''],
                    'payment_stripe_secret'    => ['label' => 'Stripe Secret Key',   'type' => 'password', 'default' => ''],
                    'payment_ideal_enabled'    => ['label' => 'Enable iDEAL',        'type' => 'checkbox', 'default' => '0'],
                    'payment_razorpay_enabled' => [
                        'label' => 'Enable Razorpay', 'type' => 'checkbox', 'default' => '0',
                        'description' => 'Enable Razorpay payment gateway for Indian customers',
                    ],
                    'payment_razorpay_key' => [
                        'label' => 'Razorpay Key ID', 'type' => 'text', 'default' => '',
                        'description' => 'Your Razorpay Key ID from Razorpay Dashboard',
                    ],
                    'payment_razorpay_secret' => [
                        'label' => 'Razorpay Key Secret', 'type' => 'password', 'default' => '',
                        'description' => 'Your Razorpay Key Secret from Razorpay Dashboard',
                    ],
                ],
            ],
            'social' => [
                'title' => 'Social Media',
                'icon'  => 'bi bi-share',
                'fields' => [
                    'social_facebook'  => ['label' => 'Facebook URL',  'type' => 'url', 'default' => ''],
                    'social_twitter'   => ['label' => 'Twitter URL',   'type' => 'url', 'default' => ''],
                    'social_instagram' => ['label' => 'Instagram URL', 'type' => 'url', 'default' => ''],
                    'social_youtube'   => ['label' => 'YouTube URL',   'type' => 'url', 'default' => ''],
                    'social_linkedin'  => ['label' => 'LinkedIn URL',  'type' => 'url', 'default' => ''],
                ],
            ],
            'email' => [
                'title' => 'Email Settings',
                'icon'  => 'bi bi-envelope',
                'fields' => [
                    'email_protocol'  => [
                        'label' => 'Protocol', 'type' => 'select',
                        'options' => ['mail' => 'Mail', 'smtp' => 'SMTP'],
                        'default' => 'mail',
                    ],
                    'email_smtp_host' => ['label' => 'SMTP Host',     'type' => 'text',     'default' => ''],
                    'email_smtp_port' => ['label' => 'SMTP Port',     'type' => 'number',   'default' => '587'],
                    'email_smtp_user' => ['label' => 'SMTP Username', 'type' => 'text',     'default' => ''],
                    'email_smtp_pass' => ['label' => 'SMTP Password', 'type' => 'password', 'default' => ''],
                ],
            ],
            'notifications' => [
                'title' => 'Notifications',
                'icon'  => 'bi bi-bell',
                'fields' => [
                    'order_notifications' => ['label' => 'Order Notifications', 'type' => 'checkbox', 'default' => '1'],
                    'newsletter_enabled'  => ['label' => 'Newsletter',          'type' => 'checkbox', 'default' => '1'],
                ],
            ],
            'seo' => [
                'title' => 'SEO Settings',
                'icon'  => 'bi bi-search',
                'fields' => [
                    'seo_meta_title'       => ['label' => 'Default Meta Title',       'type' => 'text',     'default' => 'BSSShop - Online Store'],
                    'seo_meta_description' => ['label' => 'Default Meta Description', 'type' => 'textarea', 'default' => 'Shop the best products at BSSShop'],
                    'seo_meta_keywords'    => ['label' => 'Default Meta Keywords',    'type' => 'text',     'default' => 'shop, ecommerce, products'],
                ],
            ],
        ];
    }

    public function getSetting($key)
    {
        $value = $this->settingModel->getSetting($key);

        return response()->json([
            'success' => true,
            'data' => ['key' => $key, 'value' => $value],
        ]);
    }

    // ==================== LANGUAGE MANAGEMENT ====================

    public function languages()
    {
        return response()->json([
            'success' => true,
            'data'    => $this->languageModel->all(),
        ]);
    }

    public function addLanguage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'        => 'required|string|min:2|max:10|unique:languages,code',
            'name'        => 'required|string|max:50',
            'native_name' => 'nullable|string|max:50',
            'flag'        => 'nullable|string|max:10',
            'is_active'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $language = $this->languageModel->create([
            'code'        => $request->code,
            'name'        => $request->name,
            'native_name' => $request->native_name ?? $request->name,
            'flag'        => $request->flag,
            'is_active'   => $request->is_active ?? 1,
        ]);

        Log::info('Language added: ' . $language->code . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Language added successfully',
            'data'    => $language,
        ]);
    }

    public function editLanguage(Request $request, $id)
    {
        $language = $this->languageModel->find($id);

        if (!$language) {
            return response()->json(['success' => false, 'message' => 'Language not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'code'        => ['required', 'string', 'min:2', 'max:10', Rule::unique('languages', 'code')->ignore($id)],
            'name'        => 'required|string|max:50',
            'native_name' => 'nullable|string|max:50',
            'flag'        => 'nullable|string|max:10',
            'is_active'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $language->update([
            'code'        => $request->code,
            'name'        => $request->name,
            'native_name' => $request->native_name ?? $request->name,
            'flag'        => $request->flag,
            'is_active'   => $request->is_active ?? 1,
        ]);

        Log::info('Language updated: ' . $language->code . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Language updated successfully',
            'data'    => $language,
        ]);
    }

    public function deleteLanguage($id)
    {
        $language = $this->languageModel->find($id);

        if (!$language) {
            return response()->json(['success' => false, 'message' => 'Language not found'], 404);
        }

        if ($language->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the default language',
            ], 422);
        }

        $language->delete();

        Log::info('Language deleted: ' . $language->code . ' by admin');

        return response()->json(['success' => true, 'message' => 'Language deleted successfully']);
    }

    public function setDefaultLanguage($id)
    {
        $language = $this->languageModel->find($id);

        if (!$language) {
            return response()->json(['success' => false, 'message' => 'Language not found'], 404);
        }

        DB::transaction(function () use ($id) {
            $this->languageModel->where('is_default', 1)->update(['is_default' => 0]);
            $this->languageModel->where('id', $id)->update(['is_default' => 1]);
        });

        Log::info('Default language set to: ' . $language->code . ' by admin');

        return response()->json(['success' => true, 'message' => 'Default language set successfully']);
    }

    public function toggleLanguage($id)
    {
        $language = $this->languageModel->find($id);

        if (!$language) {
            return response()->json(['success' => false, 'message' => 'Language not found'], 404);
        }

        if ($language->is_default && $language->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate the default language',
            ], 422);
        }

        $newStatus = $language->is_active ? 0 : 1;
        $language->update(['is_active' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'Language status updated successfully',
            'status'  => $newStatus,
        ]);
    }

    // ==================== TIMEZONE MANAGEMENT ====================

    public function timezones()
    {
        return response()->json(['success' => true, 'data' => $this->timezoneModel->all()]);
    }

    public function addTimezone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:100|unique:timezones,name',
            'offset'       => 'nullable|string|max:10',
            'abbreviation' => 'nullable|string|max:10',
            'is_active'    => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $timezone = $this->timezoneModel->create([
            'name'         => $request->name,
            'offset'       => $request->offset ?? '',
            'abbreviation' => $request->abbreviation ?? '',
            'is_active'    => $request->is_active ?? 1,
        ]);

        Log::info('Timezone added: ' . $timezone->name . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Timezone added successfully',
            'data'    => $timezone,
        ]);
    }

    public function editTimezone(Request $request, $id)
    {
        $timezone = $this->timezoneModel->find($id);

        if (!$timezone) {
            return response()->json(['success' => false, 'message' => 'Timezone not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'         => ['required', 'string', 'max:100', Rule::unique('timezones', 'name')->ignore($id)],
            'offset'       => 'nullable|string|max:10',
            'abbreviation' => 'nullable|string|max:10',
            'is_active'    => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $timezone->update([
            'name'         => $request->name,
            'offset'       => $request->offset ?? '',
            'abbreviation' => $request->abbreviation ?? '',
            'is_active'    => $request->is_active ?? 1,
        ]);

        Log::info('Timezone updated: ' . $timezone->name . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Timezone updated successfully',
            'data'    => $timezone,
        ]);
    }

    public function deleteTimezone($id)
    {
        $timezone = $this->timezoneModel->find($id);

        if (!$timezone) {
            return response()->json(['success' => false, 'message' => 'Timezone not found'], 404);
        }

        if ($timezone->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the default timezone',
            ], 422);
        }

        $timezone->delete();

        Log::info('Timezone deleted: ' . $timezone->name . ' by admin');

        return response()->json(['success' => true, 'message' => 'Timezone deleted successfully']);
    }

    public function setDefaultTimezone($id)
    {
        $timezone = $this->timezoneModel->find($id);

        if (!$timezone) {
            return response()->json(['success' => false, 'message' => 'Timezone not found'], 404);
        }

        DB::transaction(function () use ($id) {
            $this->timezoneModel->where('is_default', 1)->update(['is_default' => 0]);
            $this->timezoneModel->where('id', $id)->update(['is_default' => 1]);
        });

        Log::info('Default timezone set to: ' . $timezone->name . ' by admin');

        return response()->json(['success' => true, 'message' => 'Default timezone set successfully']);
    }

    public function toggleTimezone($id)
    {
        $timezone = $this->timezoneModel->find($id);

        if (!$timezone) {
            return response()->json(['success' => false, 'message' => 'Timezone not found'], 404);
        }

        if ($timezone->is_default && $timezone->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate the default timezone',
            ], 422);
        }

        $newStatus = $timezone->is_active ? 0 : 1;
        $timezone->update(['is_active' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'Timezone status updated successfully',
            'status'  => $newStatus,
        ]);
    }

    // ==================== THEME MANAGEMENT ====================

    public function themes()
    {
        return response()->json(['success' => true, 'data' => $this->themeModel->all()]);
    }

    public function addTheme(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:50|unique:themes,name',
            'display_name'  => 'required|string|max:100',
            'description'   => 'nullable|string',
            'preview_image' => 'nullable|string|max:255',
            'is_active'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $theme = $this->themeModel->create([
            'name'          => $request->name,
            'display_name'  => $request->display_name,
            'description'   => $request->description ?? '',
            'preview_image' => $request->preview_image ?? '',
            'is_active'     => $request->is_active ?? 1,
        ]);

        Log::info('Theme added: ' . $theme->name . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Theme added successfully',
            'data'    => $theme,
        ]);
    }

    public function editTheme(Request $request, $id)
    {
        $theme = $this->themeModel->find($id);

        if (!$theme) {
            return response()->json(['success' => false, 'message' => 'Theme not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'          => ['required', 'string', 'max:50', Rule::unique('themes', 'name')->ignore($id)],
            'display_name'  => 'required|string|max:100',
            'description'   => 'nullable|string',
            'preview_image' => 'nullable|string|max:255',
            'is_active'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $theme->update([
            'name'          => $request->name,
            'display_name'  => $request->display_name,
            'description'   => $request->description ?? '',
            'preview_image' => $request->preview_image ?? '',
            'is_active'     => $request->is_active ?? 1,
        ]);

        Log::info('Theme updated: ' . $theme->name . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Theme updated successfully',
            'data'    => $theme,
        ]);
    }

    public function deleteTheme($id)
    {
        $theme = $this->themeModel->find($id);

        if (!$theme) {
            return response()->json(['success' => false, 'message' => 'Theme not found'], 404);
        }

        if ($theme->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the default theme',
            ], 422);
        }

        $theme->delete();

        Log::info('Theme deleted: ' . $theme->name . ' by admin');

        return response()->json(['success' => true, 'message' => 'Theme deleted successfully']);
    }

    public function setDefaultTheme($id)
    {
        $theme = $this->themeModel->find($id);

        if (!$theme) {
            return response()->json(['success' => false, 'message' => 'Theme not found'], 404);
        }

        DB::transaction(function () use ($id) {
            $this->themeModel->where('is_default', 1)->update(['is_default' => 0]);
            $this->themeModel->where('id', $id)->update(['is_default' => 1]);
        });

        Log::info('Default theme set to: ' . $theme->name . ' by admin');

        return response()->json(['success' => true, 'message' => 'Default theme set successfully']);
    }

    public function toggleTheme($id)
    {
        $theme = $this->themeModel->find($id);

        if (!$theme) {
            return response()->json(['success' => false, 'message' => 'Theme not found'], 404);
        }

        if ($theme->is_default && $theme->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot deactivate the default theme',
            ], 422);
        }

        $newStatus = $theme->is_active ? 0 : 1;
        $theme->update(['is_active' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => 'Theme status updated successfully',
            'status'  => $newStatus,
        ]);
    }

    public function preferences()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'languages' => $this->languageModel->all(),
                'timezones' => $this->timezoneModel->all(),
                'themes'    => $this->themeModel->all(),
            ],
        ]);
    }

    // ==================== FAVICON (STANDALONE) ====================

    public function uploadFavicon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'favicon' => 'required|image|mimes:ico,png,jpg,jpeg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $result = $this->handleFaviconUpload($request->file('favicon'));
        if ($result !== true) {
            return $result;
        }

        $path = $this->settingModel->getSetting('favicon');

        return response()->json([
            'success' => true,
            'message' => 'Favicon uploaded successfully',
            'data' => [
                'path' => $path,
                'url'  => $path ? Storage::url($path) : null,
            ],
        ]);
    }

    public function removeFavicon()
    {
        $oldFavicon = $this->settingModel->getSetting('favicon');

        if ($oldFavicon && Storage::disk('public')->exists($oldFavicon)) {
            Storage::disk('public')->delete($oldFavicon);
        }

        $this->settingModel->setSetting('favicon', '');

        Log::info('Favicon removed by admin');

        return response()->json(['success' => true, 'message' => 'Favicon removed successfully']);
    }

    public function getFavicon()
    {
        $favicon = $this->settingModel->getSetting('favicon');

        return response()->json([
            'success' => true,
            'data' => [
                'path' => $favicon,
                'url'  => $favicon ? Storage::url($favicon) : null,
            ],
        ]);
    }
}