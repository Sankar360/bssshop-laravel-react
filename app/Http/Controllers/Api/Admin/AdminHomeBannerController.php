<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeBanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AdminHomeBannerController extends Controller
{
    /**
     * @var HomeBanner
     */
    protected $homeBannerModel;

    /**
     * AdminHomeBannerController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->homeBannerModel = new HomeBanner();
    }

    /**
     * Get home banner settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $banner = $this->homeBannerModel->first();

        // Return default structure if no banner exists
        if (!$banner) {
            $banner = [
                'id' => null,
                'badge_text' => '',
                'title_line1' => '',
                'title_line2' => '',
                'subtitle' => '',
                'button_text' => '',
                'button_link' => '#',
                'button_icon' => '',
                'is_active' => 1,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $banner,
        ]);
    }

    /**
     * Update or create home banner settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'badge_text' => 'nullable|string|max:100',
            'title_line1' => 'nullable|string|max:200',
            'title_line2' => 'nullable|string|max:200',
            'subtitle' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:255',
            'button_icon' => 'nullable|string|max:50',
            'is_active' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'badge_text' => $request->badge_text,
            'title_line1' => $request->title_line1,
            'title_line2' => $request->title_line2,
            'subtitle' => $request->subtitle,
            'button_text' => $request->button_text,
            'button_link' => $request->button_link ?? '#',
            'button_icon' => $request->button_icon,
            'is_active' => $request->is_active ?? 1,
        ];

        $existing = $this->homeBannerModel->first();

        if ($existing) {
            $existing->update($data);
            $banner = $existing;
            $message = 'Home banner settings updated successfully!';
        } else {
            $banner = $this->homeBannerModel->create($data);
            $message = 'Home banner settings created successfully!';
        }

        Log::info('Home banner ' . ($existing ? 'updated' : 'created') . ' by admin');

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $banner,
        ]);
    }

    /**
     * Toggle banner active status.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus()
    {
        $banner = $this->homeBannerModel->first();

        if (!$banner) {
            // Create default banner if none exists
            $banner = $this->homeBannerModel->create([
                'badge_text' => '',
                'title_line1' => '',
                'title_line2' => '',
                'subtitle' => '',
                'button_text' => '',
                'button_link' => '#',
                'button_icon' => '',
                'is_active' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Banner created and activated',
                'data' => $banner,
            ]);
        }

        $newStatus = $banner->is_active ? 0 : 1;
        $banner->update(['is_active' => $newStatus]);

        Log::info('Home banner status toggled to ' . ($newStatus ? 'active' : 'inactive') . ' by admin');

        return response()->json([
            'success' => true,
            'message' => $newStatus ? 'Banner activated' : 'Banner deactivated',
            'data' => $banner,
        ]);
    }

    /**
     * Get active banner for frontend.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActiveBanner()
    {
        $banner = $this->homeBannerModel->active()->first();

        if (!$banner) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No active banner found',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $banner,
        ]);
    }

    /**
     * Get banner statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $total = $this->homeBannerModel->count();
        $active = $this->homeBannerModel->active()->count();
        $inactive = $total - $active;
        $exists = $total > 0;

        return response()->json([
            'success' => true,
            'data' => [
                'exists' => $exists,
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
            ],
        ]);
    }

    /**
     * Reset banner to default settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetDefault()
    {
        $defaults = [
            'badge_text' => 'Special Offer',
            'title_line1' => 'Welcome to Our Store',
            'title_line2' => 'Shop the Best Products',
            'subtitle' => 'Discover amazing deals on quality products',
            'button_text' => 'Shop Now',
            'button_link' => '/category',
            'button_icon' => 'bi-arrow-right',
            'is_active' => 1,
        ];

        $existing = $this->homeBannerModel->first();

        if ($existing) {
            $existing->update($defaults);
            $banner = $existing;
        } else {
            $banner = $this->homeBannerModel->create($defaults);
        }

        Log::info('Home banner reset to default by admin');

        return response()->json([
            'success' => true,
            'message' => 'Banner reset to default settings successfully!',
            'data' => $banner,
        ]);
    }

    /**
     * Preview banner (return banner data for preview).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'badge_text' => 'nullable|string|max:100',
            'title_line1' => 'nullable|string|max:200',
            'title_line2' => 'nullable|string|max:200',
            'subtitle' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'button_link' => 'nullable|string|max:255',
            'button_icon' => 'nullable|string|max:50',
            'is_active' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'badge_text' => $request->badge_text,
            'title_line1' => $request->title_line1,
            'title_line2' => $request->title_line2,
            'subtitle' => $request->subtitle,
            'button_text' => $request->button_text,
            'button_link' => $request->button_link ?? '#',
            'button_icon' => $request->button_icon,
            'is_active' => $request->is_active ?? 1,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Upload banner background image (optional feature).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $path = $request->file('image')->store('banners', 'public');

        // Update banner with image if exists
        $banner = $this->homeBannerModel->first();
        if ($banner) {
            // Delete old image if exists
            if ($banner->image && \Storage::disk('public')->exists($banner->image)) {
                \Storage::disk('public')->delete($banner->image);
            }
            $banner->update(['image' => $path]);
        } else {
            // Create banner with image if none exists
            $banner = $this->homeBannerModel->create([
                'image' => $path,
                'badge_text' => '',
                'title_line1' => '',
                'title_line2' => '',
                'subtitle' => '',
                'button_text' => '',
                'button_link' => '#',
                'button_icon' => '',
                'is_active' => 1,
            ]);
        }

        Log::info('Banner image uploaded by admin');

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully!',
            'data' => [
                'path' => $path,
                'url' => \Storage::url($path),
            ],
        ]);
    }

    /**
     * Remove banner image.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeImage()
    {
        $banner = $this->homeBannerModel->first();

        if (!$banner || !$banner->image) {
            return response()->json([
                'success' => false,
                'message' => 'No image to remove',
            ], 404);
        }

        if (\Storage::disk('public')->exists($banner->image)) {
            \Storage::disk('public')->delete($banner->image);
        }

        $banner->update(['image' => null]);

        Log::info('Banner image removed by admin');

        return response()->json([
            'success' => true,
            'message' => 'Image removed successfully!',
        ]);
    }

    /**
     * Get banner image URL.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getImage()
    {
        $banner = $this->homeBannerModel->first();

        if (!$banner || !$banner->image) {
            return response()->json([
                'success' => false,
                'message' => 'No image found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'path' => $banner->image,
                'url' => \Storage::url($banner->image),
            ],
        ]);
    }
}