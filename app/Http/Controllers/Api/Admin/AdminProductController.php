<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\Feature;
use App\Models\FeatureCategoryMapping;
use App\Models\ProductFeatureValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\ProductVariantImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class AdminProductController extends Controller
{
    /**
     * @var Product
     */
    protected $productModel;

    /**
     * @var ProductCategory
     */
    protected $categoryModel;

    /**
     * @var ProductImage
     */
    protected $productImageModel;

    /**
     * @var Feature
     */
    protected $featureModel;

    /**
     * @var FeatureCategoryMapping
     */
    protected $mappingModel;

    /**
     * @var ProductFeatureValue
     */
    protected $productFeatureValueModel;

    /**
     * @var ProductVariant
     */
    protected $variantModel;

    /**
     * @var ProductVariantValue
     */
    protected $variantValueModel;

    /**
     * @var ProductVariantImage
     */
    protected $variantImageModel;

    /**
     * AdminProductController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');

        $this->productModel = new Product();
        $this->categoryModel = new ProductCategory();
        $this->productImageModel = new ProductImage();
        $this->featureModel = new Feature();
        $this->mappingModel = new FeatureCategoryMapping();
        $this->productFeatureValueModel = new ProductFeatureValue();
        $this->variantModel = new ProductVariant();
        $this->variantValueModel = new ProductVariantValue();
        $this->variantImageModel = new ProductVariantImage();
    }

    /**
     * List products with categories.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
{
    $search   = $request->get('search');
    $category = $request->get('category');
    $status   = $request->get('status', 'all');
    $perPage  = (int) $request->get('per_page', 20);

    $paginator = Product::query()
        ->with('category')
        ->when($search, function ($q) use ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%")
                    ->orWhere('sku', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        })
        ->when($category, fn ($q) => $q->where('category_id', $category))
        ->when($status !== 'all', fn ($q) => $q->where('status', $status))
        ->orderBy('created_at', 'DESC')
        ->paginate($perPage);

    // ✅ Transform images to full URLs
    $items = collect($paginator->items())->map(function ($product) {
        if ($product->image && !preg_match('#^https?://#i', $product->image)) {
            $product->image = Storage::url($product->image);
        }
        return $product;
    })->all();

    $stats = [
        'total'    => Product::query()->count(),
        'active'   => Product::query()->where('status', 'active')->count(),
        'inactive' => Product::query()->where('status', 'inactive')->count(),
        'draft'    => Product::query()->where('status', 'draft')->count(),
    ];

    return response()->json([
        'success' => true,
        'data' => [
            'products'   => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'stats' => $stats,
        ],
        'stats'       => $stats,
        'total_count' => $paginator->total(),
    ]);
}

    /**
     * Get data for product creation.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        $categories = $this->categoryModel->where('is_active', 1)
            ->whereNull('parent_id')
            ->orderBy('name', 'ASC')
            ->get();

        return response()->json([
            'success' => true,
            'categories' => $categories,
            'subcategories' => [],
        ]);
    }

    /**
     * Get subcategories for a category (AJAX).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSubcategories(Request $request)
    {
        $categoryId = $request->get('category_id');

        if (empty($categoryId)) {
            return response()->json([]);
        }

        $subcategories = $this->categoryModel->getActiveSubcategories((int) $categoryId);

        return response()->json($subcategories);
    }

    /**
     * Store a new product.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['active', 'inactive', 'draft'])],
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'subcategory_id' => 'nullable|integer|exists:product_categories,id',
            'sku' => 'nullable|string|unique:products,sku',
            'sale_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|integer|min:0|max:100',
            'rating' => 'nullable|numeric|min:0|max:5',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Generate slug
        $slug = Str::slug($request->name);
        $existing = $this->productModel->where('slug', $slug)->first();
        if ($existing) {
            $slug = $slug . '-' . uniqid();
        }

        $data = [
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $request->price,
            'sale_price' => $request->sale_price,
            'stock' => $request->stock,
            'sku' => $request->sku,
            'status' => $request->status,
            'category_id' => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'rating' => $request->rating ?? 0,
            'discount' => $request->discount ?? 0,
            'is_featured' => $request->is_featured ? 1 : 0,
            'is_home' => $request->is_home ? 1 : 0,
            'tags' => $request->tags,
            'weight' => $request->weight,
            'length' => $request->length,
            'width' => $request->width,
            'height' => $request->height,
        ];

        // Handle featured image
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image'] = $path;
        }

        $product = $this->productModel->create($data);

        // Handle temp images
        $tempImages = $request->temp_images;
        if ($tempImages && is_array($tempImages)) {
            foreach ($tempImages as $index => $tempImage) {
                $tempPath = $tempImage['path'] ?? '';
                if (empty($tempPath)) continue;

                // Check if temp file exists in storage
                $tempStoragePath = str_replace('uploads/temp/', '', $tempPath);
                if (Storage::disk('public')->exists('temp/' . $tempStoragePath)) {
                    $newPath = 'products/' . $tempStoragePath;
                    Storage::disk('public')->move('temp/' . $tempStoragePath, $newPath);
                } elseif (file_exists($tempPath)) {
                    // Fallback to filesystem
                    $newPath = str_replace('uploads/temp/', 'uploads/products/', $tempPath);
                    rename($tempPath, $newPath);
                } else {
                    continue;
                }

                $this->productImageModel->create([
                    'product_id' => $product->id,
                    'image' => $newPath ?? $tempPath,
                    'is_primary' => 0,
                    'sort_order' => $index,
                ]);
            }
        }

        Log::info('Product created: ' . $product->name . ' (ID: ' . $product->id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully!',
            'data' => ['product_id' => $product->id],
        ], 201);
    }

    /**
     * Get product for editing.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id)
    {
        $product = $this->productModel->with('category')->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Get categories
        $categories = $this->categoryModel->where('is_active', 1)
            ->whereNull('parent_id')
            ->orderBy('name', 'ASC')
            ->get();

        // Get subcategories
        $subcategories = [];
        if ($product->category_id) {
            $subcategories = $this->categoryModel->getActiveSubcategories($product->category_id);
        }

        // Get product images
        $images = $this->productImageModel->getImagesByProduct($id);

        // Get features
        $features = $this->getProductFeatures($id);

        // Get variants
        $variants = $this->variantModel->getVariantsWithValues($id);

        // Get specifications
        $specifications = DB::table('product_specifications')
            ->where('product_id', $id)
            ->orderBy('sort_order', 'ASC')
            ->get();

        $productData = $product->toArray();
        $productData['images'] = $images;

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $productData,
                'categories' => $categories,
                'subcategories' => $subcategories,
                'features' => $features,
                'variants' => $variants,
                'specifications' => $specifications,
            ],
        ]);
    }

    /**
     * Get product features with saved values.
     *
     * @param int $productId
     * @return array
     */
    private function getProductFeatures(int $productId): array
    {
        $product = $this->productModel->find($productId);

        if (!$product || empty($product->subcategory_id)) {
            return [];
        }

        // Get features assigned to this subcategory
        $features = $this->mappingModel->getFeaturesForCategory($product->subcategory_id);

        // Get saved values
        $savedValues = $this->productFeatureValueModel->getValuesForProduct($productId);

        // Merge with saved values and options
        foreach ($features as &$feature) {
            if ($feature['input_type'] === 'checkbox') {
                $feature['saved_value'] = $savedValues[$feature['id']] ?? '';
            } else {
                $feature['saved_value'] = $savedValues[$feature['id']] ?? '';
            }
            $feature['options_array'] = $this->featureModel->getOptionsArray($feature);
        }

        return $features;
    }

    /**
     * Update a product.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'status' => ['required', Rule::in(['active', 'inactive', 'draft'])],
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'subcategory_id' => 'nullable|integer|exists:product_categories,id',
            'sku' => ['nullable', 'string', Rule::unique('products', 'sku')->ignore($id)],
            'sale_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|integer|min:0|max:100',
            'rating' => 'nullable|numeric|min:0|max:5',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'price' => $request->price,
            'sale_price' => $request->sale_price,
            'stock' => $request->stock,
            'sku' => $request->sku,
            'status' => $request->status,
            'category_id' => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'rating' => $request->rating ?? 0,
            'discount' => $request->discount ?? 0,
            'is_featured' => $request->is_featured ? 1 : 0,
            'is_home' => $request->is_home ? 1 : 0,
            'tags' => $request->tags,
            'weight' => $request->weight,
            'length' => $request->length,
            'width' => $request->width,
            'height' => $request->height,
        ];

        // Handle slug
        if ($request->slug) {
            $slug = Str::slug($request->slug);
            $existing = $this->productModel->where('slug', $slug)->where('id', '!=', $id)->first();
            if ($existing) {
                $slug = $slug . '-' . uniqid();
            }
            $data['slug'] = $slug;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $path = $request->file('image')->store('products', 'public');
            $data['image'] = $path;
        }

        $product->update($data);

        Log::info('Product updated: ' . $product->name . ' (ID: ' . $product->id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    }

    /**
     * Delete a product.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Delete product image
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        // Delete product images
        $images = $this->productImageModel->getImagesByProduct($id);
        foreach ($images as $image) {
            if (Storage::disk('public')->exists($image['image'])) {
                Storage::disk('public')->delete($image['image']);
            }
        }
        $this->productImageModel->where('product_id', $id)->delete();

        // Delete product variants
        $variants = $this->variantModel->where('product_id', $id)->get();
        foreach ($variants as $variant) {
            $this->variantValueModel->where('variant_id', $variant->id)->delete();
            $this->variantImageModel->where('variant_id', $variant->id)->delete();
        }
        $this->variantModel->where('product_id', $id)->delete();

        // Delete product feature values
        $this->productFeatureValueModel->where('product_id', $id)->delete();

        // Delete product
        $product->delete();

        Log::info('Product deleted: ' . $product->name . ' (ID: ' . $id . ') by admin');

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Toggle product status.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus($id)
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $newStatus = $product->status === 'active' ? 'inactive' : 'active';
        $product->update(['status' => $newStatus]);

        Log::info('Product status toggled: ' . $product->name . ' (ID: ' . $product->id . ') to ' . $newStatus . ' by admin');

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => $newStatus === 'active' ? 'Product activated' : 'Product deactivated',
            'data' => $product,
        ]);
    }

    // ==================== IMAGE MANAGEMENT ====================

    /**
     * Upload product main image.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request, $id)
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Delete old image
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $path = $request->file('image')->store('products', 'public');
        $product->update(['image' => $path]);

        Log::info('Product image uploaded for product ID: ' . $id . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'data' => ['image' => $path],
        ]);
    }

    /**
     * Remove product main image.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeImage($id)
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->update(['image' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Image removed successfully',
        ]);
    }

    /**
     * Upload multiple product images.
     *
     * @param Request $request
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadMultipleImages(Request $request, $productId)
    {
        $product = $this->productModel->find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid files',
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedImages = [];
        $errors = [];

        foreach ($request->file('images') as $file) {
            $path = $file->store('products', 'public');

            $imageData = [
                'product_id' => $productId,
                'image' => $path,
                'is_primary' => 0,
                'sort_order' => $this->getNextSortOrder($productId),
            ];

            if ($this->productImageModel->create($imageData)) {
                $uploadedImages[] = $path;
            } else {
                $errors[] = 'Failed to save: ' . $file->getClientOriginalName();
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($uploadedImages) . ' images uploaded successfully',
            'data' => [
                'images' => $uploadedImages,
                'errors' => $errors,
            ],
        ]);
    }

    /**
     * Get next sort order for product images.
     *
     * @param int $productId
     * @return int
     */
    private function getNextSortOrder(int $productId): int
    {
        $lastImage = $this->productImageModel->where('product_id', $productId)
            ->orderBy('sort_order', 'DESC')
            ->first();

        return $lastImage ? $lastImage->sort_order + 1 : 0;
    }

    /**
     * Set primary product image.
     *
     * @param int $productId
     * @param int $imageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function setPrimaryImage($productId, $imageId)
    {
        $image = $this->productImageModel->find($imageId);

        if (!$image || $image->product_id != $productId) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found',
            ], 404);
        }

        $this->productImageModel->setPrimaryImage($productId, $imageId);

        return response()->json([
            'success' => true,
            'message' => 'Primary image updated',
        ]);
    }

    /**
     * Remove multiple product image.
     *
     * @param int $productId
     * @param int $imageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMultipleImage($productId, $imageId)
    {
        $image = $this->productImageModel->find($imageId);

        if (!$image || $image->product_id != $productId) {
            return response()->json([
                'success' => false,
                'message' => 'Image not found',
            ], 404);
        }

        if (Storage::disk('public')->exists($image->image)) {
            Storage::disk('public')->delete($image->image);
        }

        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'Image removed successfully',
        ]);
    }

    /**
     * Get product images.
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductImages($productId)
    {
        $images = $this->productImageModel->getImagesByProduct($productId);

        return response()->json([
            'success' => true,
            'data' => $images,
        ]);
    }

    /**
     * Upload temp images for product.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadTempImages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid files',
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedImages = [];
        $errors = [];

        foreach ($request->file('images') as $file) {
            $path = $file->store('temp', 'public');

            $uploadedImages[] = [
                'url' => Storage::url($path),
                'path' => $path,
                'name' => $file->getClientOriginalName(),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => count($uploadedImages) . ' images uploaded',
            'data' => [
                'images' => $uploadedImages,
                'errors' => $errors,
            ],
        ]);
    }

    // ==================== FEATURE MANAGEMENT ====================

    /**
     * Save product features.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveFeatures(Request $request, $id)
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $features = $request->features ?? [];

        // Get valid feature IDs for this product's subcategory
        $validFeatureIds = [];
        if (!empty($product->subcategory_id)) {
            $validFeatures = $this->mappingModel->getFeaturesForCategory($product->subcategory_id);
            $validFeatureIds = array_column($validFeatures, 'id');
        }

        // Filter features to only valid ones
        $filteredFeatures = [];
        $saved = 0;
        foreach ($features as $featureId => $value) {
            if (in_array((int) $featureId, $validFeatureIds)) {
                $filteredFeatures[$featureId] = $value;
            }
            $saved++;
        }

        $this->productFeatureValueModel->saveProductFeatures($id, $filteredFeatures);

        Log::info('Product features saved for product ID: ' . $id . ' by admin');

        return response()->json([
            'success' => true,
            'message' => 'Features saved successfully',
            'saved_count' => $saved,
        ]);
    }

    /**
     * Get features for product.
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    /**
 * Get features for product.
 *
 * @param int $productId
 * @return \Illuminate\Http\JsonResponse
 */
public function getFeaturesForProduct($productId)
{
    $product = $this->productModel->find($productId);

    if (!$product) {
        return response()->json([
            'success' => false,
            'message' => 'Product not found',
        ], 404);
    }

    if (empty($product->subcategory_id)) {
        return response()->json([
            'success' => false,
            'message' => 'No subcategory selected for this product',
        ], 422);
    }

    // Features assigned to the subcategory
    $features = $this->mappingModel->getFeaturesForCategory($product->subcategory_id);

    // Saved values for this product (map of feature_id => [value, ...])
    $savedValues = $this->productFeatureValueModel->getValuesForProduct($productId);

    foreach ($features as &$feature) {
        $featureId = $feature['id'];

        // Normalize saved values to a flat array of strings
        $saved = $savedValues[$featureId] ?? [];
        if (!is_array($saved)) {
            $saved = $saved === '' || $saved === null ? [] : [$saved];
        }
        $saved = array_values(array_map('strval', $saved));

        // Checkbox → array of strings; others → single string
        if ($feature['input_type'] === 'checkbox') {
            $feature['saved_value'] = $saved;
        } else {
            $feature['saved_value'] = $saved[0] ?? '';
        }

        // Ensure options_array is a list of { id, value } objects (stringified)
        $rawOptions = $this->featureModel->getOptionsArray($feature);
        $normalizedOptions = [];
        foreach ($rawOptions as $opt) {
            if (is_array($opt)) {
                $normalizedOptions[] = [
                    'id'    => isset($opt['id']) ? (string) $opt['id'] : (string) ($opt['value'] ?? ''),
                    'value' => (string) ($opt['value'] ?? ''),
                ];
            } else {
                // Plain string option (legacy `options` column)
                $normalizedOptions[] = [
                    'id'    => (string) $opt,
                    'value' => (string) $opt,
                ];
            }
        }
        $feature['options_array'] = $normalizedOptions;
    }
    unset($feature);

    return response()->json([
        'success' => true,
        'data' => [
            'features'       => array_values($features),
            'subcategory_id' => $product->subcategory_id,
        ],
    ]);
}

    // ==================== VARIANT MANAGEMENT ====================

    /**
     * Get product variants.
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductVariants($productId)
    {
        try {
            $variants = $this->variantModel->getVariantsWithValues($productId);

            return response()->json([
                'success' => true,
                'data' => $variants,
            ]);
        } catch (\Exception $e) {
            Log::error('Get product variants error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
public function getVariantFeatures($productId)
{
    try {
        $product = $this->productModel->find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        if (empty($product->subcategory_id)) {
            return response()->json([
                'success' => false,
                'message' => 'No subcategory selected for this product',
            ], 422);
        }

        // ---- 1. Get ONLY feature_ids the product has picked values for ----
        $featureIds = DB::table('product_feature_values')
            ->where('product_id', $productId)
            ->distinct()
            ->pluck('feature_id')
            ->toArray();

        if (empty($featureIds)) {
            return response()->json([
                'success' => true,
                'data' => [
                    'features' => [],
                    'variants' => $this->variantModel->getVariantsWithValues($productId),
                ],
                'message' => 'No features selected yet. Save some features on the Features tab first.',
            ]);
        }

        // ---- 2. Load features ----
        $features = Feature::query()
            ->whereIn('id', $featureIds)
            ->where('status', 1)
            ->orderBy('name', 'ASC')
            ->get();

        $groupedFeatures = [];

        foreach ($features as $feature) {
            $savedRows = DB::table('product_feature_values')
                ->where('product_id', $productId)
                ->where('feature_id', $feature->id)
                ->pluck('value')
                ->toArray();

            // Flatten comma-separated strings into individual tokens
            $tokens = [];
            foreach ($savedRows as $raw) {
                $raw = (string) $raw;
                foreach (explode(',', $raw) as $part) {
                    $part = trim($part);
                    if ($part !== '') {
                        $tokens[] = $part;
                    }
                }
            }
            $tokens = array_values(array_unique($tokens));

            if (empty($tokens)) {
                continue;
            }

            // ---- 3. Resolve each token to its display text ----
            $values = $this->resolveFeatureValues($feature, $tokens);

            if (empty($values)) {
                continue;
            }

            $groupedFeatures[] = [
                'feature_id' => (int) $feature->id,
                'name'       => $feature->name,
                'input_type' => $feature->input_type,
                'values'     => $values,
            ];
        }

        // ---- 4. Existing variants ----
        $variants = $this->variantModel->getVariantsWithValues($productId);

        return response()->json([
            'success' => true,
            'data' => [
                'features' => $groupedFeatures,
                'variants' => $variants,
            ],
        ]);
    } catch (\Exception $e) {
        Log::error('Get variant features error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

/**
 * Convert saved tokens (which may be IDs or raw text) into
 * { id, value } pairs where `value` is the human-readable text.
 *
 * @param  Feature  $feature
 * @param  array    $tokens   e.g. ["5", "8"] or ["Blue", "Red"]
 * @return array              e.g. [["id"=>"5","value"=>"Blue"], ...]
 */
private function resolveFeatureValues(Feature $feature, array $tokens): array
{
    // Split tokens into pure-numeric IDs vs text
    $numericIds = [];
    $textTokens = [];

    foreach ($tokens as $t) {
        if (ctype_digit((string) $t)) {
            $numericIds[] = (int) $t;
        } else {
            $textTokens[] = $t;
        }
    }

    // Look up display values for numeric IDs from feature_values
    $idToText = [];
    if (!empty($numericIds)) {
        $rows = DB::table('feature_values')
            ->where('feature_id', $feature->id)
            ->whereIn('id', $numericIds)
            ->get(['id', 'value']);

        foreach ($rows as $row) {
            $idToText[(string) $row->id] = (string) $row->value;
        }
    }

    $result = [];

    // Numeric tokens → use looked-up text as the display value
    foreach ($numericIds as $id) {
        $text = $idToText[(string) $id] ?? (string) $id; // fallback to ID if not found
        $result[] = [
            'id'    => (string) $id,   // keep the ID
            'value' => $text,          // display text
        ];
    }

    // Non-numeric tokens are already plain text
    foreach ($textTokens as $text) {
        $result[] = [
            'id'    => $text,          // use text as id
            'value' => $text,
        ];
    }

    // Deduplicate by value
    return collect($result)->unique('value')->values()->all();
}

    /**
     * Save a single variant.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveVariant(Request $request)
    {
        try {
            $productId = $request->product_id;
            $variantId = $request->variant_id;

            // Get feature values
            $featureValues = $request->feature_values ?? [];
            if (is_string($featureValues)) {
                $featureValues = json_decode($featureValues, true);
            }

            $sku = $request->sku;
            $price = $request->price;
            $salePrice = $request->sale_price ?? 0;
            $discount = $request->discount ?? 0;
            $rating = $request->rating ?? 0;
            $stock = $request->stock;
            $slug = $request->slug;
            $tempImages = $request->temp_images ?? [];

            if (is_string($tempImages)) {
                $tempImages = json_decode($tempImages, true);
            }

            $product = $this->productModel->find($productId);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            if (empty($sku) || empty($featureValues)) {
                return response()->json([
                    'success' => false,
                    'message' => 'SKU and feature values are required',
                ], 422);
            }

            // Auto-generate slug
            if (empty($slug)) {
                $valueString = implode('-', array_values($featureValues));
                $slug = Str::slug($product->name . '-' . $valueString . '-' . $sku);
            } else {
                $slug = Str::slug($slug);
            }

            // Check if slug exists
            $existing = $this->variantModel->where('slug', $slug)
                ->where('product_id', $productId);
            if ($variantId) {
                $existing->where('id', '!=', $variantId);
            }
            if ($existing->first()) {
                $slug = $slug . '-' . uniqid();
            }

            $data = [
                'product_id' => $productId,
                'sku' => $sku,
                'slug' => $slug,
                'price' => $price,
                'sale_price' => $salePrice,
                'discount' => $discount,
                'rating' => $rating,
                'stock' => $stock,
                'status' => 1,
            ];

            if ($variantId) {
                $this->variantModel->where('id', $variantId)->update($data);
                $savedVariantId = $variantId;
            } else {
                $savedVariantId = $this->variantModel->create($data)->id;
            }

            // Save variant values
            if ($savedVariantId) {
                $this->variantValueModel->saveVariantValues($savedVariantId, $featureValues);
            }

            // Handle temp images
            if ($savedVariantId && !empty($tempImages)) {
                $existingImages = $this->variantImageModel->where('variant_id', $savedVariantId)->count();

                foreach ($tempImages as $index => $tempImage) {
                    $tempPath = $tempImage['path'] ?? '';
                    if (empty($tempPath)) continue;

                    $newPath = str_replace('temp/', 'variants/', $tempPath);

                    if (Storage::disk('public')->exists($tempPath)) {
                        Storage::disk('public')->move($tempPath, $newPath);

                        $this->variantImageModel->create([
                            'variant_id' => $savedVariantId,
                            'product_id' => $productId,
                            'image' => $newPath,
                            'is_primary' => ($existingImages + $index) === 0 ? 1 : 0,
                            'sort_order' => $existingImages + $index,
                        ]);
                    }
                }
            }

            $variant = $this->variantModel->getVariantWithValues($savedVariantId);
            $variant['images'] = $this->variantImageModel->getImagesByVariant($savedVariantId);

            Log::info('Variant ' . ($variantId ? 'updated' : 'created') . ' for product ID: ' . $productId . ' by admin');

            return response()->json([
                'success' => true,
                'message' => $variantId ? 'Variant updated successfully' : 'Variant created successfully',
                'data' => $variant,
            ]);
        } catch (\Exception $e) {
            Log::error('Save variant error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save multiple variants.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveVariants(Request $request)
    {
        try {
            $productId = $request->product_id;
            $variantData = $request->variants ?? [];

            // If no variants array, check for single variant data
            if (empty($variantData)) {
                $featureValues = $request->feature_values ?? [];
                if (is_string($featureValues)) {
                    $featureValues = json_decode($featureValues, true);
                }

                $sku = $request->sku ?? '';
                $price = $request->price ?? 0;
                $salePrice = $request->sale_price ?? 0;
                $discount = $request->discount ?? 0;
                $rating = $request->rating ?? 0;
                $stock = $request->stock ?? 0;
                $slug = $request->slug ?? '';
                $tempImages = $request->temp_images ?? [];

                if (is_string($tempImages)) {
                    $tempImages = json_decode($tempImages, true);
                }

                if (!empty($sku) && !empty($featureValues)) {
                    $variantData = [[
                        'feature_values' => $featureValues,
                        'sku' => $sku,
                        'price' => $price,
                        'sale_price' => $salePrice,
                        'discount' => $discount,
                        'rating' => $rating,
                        'stock' => $stock,
                        'slug' => $slug,
                        'temp_images' => $tempImages,
                    ]];
                }
            } else {
                foreach ($variantData as &$data) {
                    if (isset($data['feature_values']) && is_string($data['feature_values'])) {
                        $data['feature_values'] = json_decode($data['feature_values'], true);
                    }
                    if (isset($data['temp_images']) && is_string($data['temp_images'])) {
                        $data['temp_images'] = json_decode($data['temp_images'], true);
                    }
                }
            }

            $product = $this->productModel->find($productId);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found',
                ], 404);
            }

            $savedCount = 0;
            $errors = [];
            $savedVariants = [];

            foreach ($variantData as $data) {
                $featureValues = $data['feature_values'] ?? [];
                $sku = $data['sku'] ?? '';
                $price = $data['price'] ?? 0;
                $salePrice = $data['sale_price'] ?? 0;
                $discount = $data['discount'] ?? 0;
                $rating = $data['rating'] ?? 0;
                $stock = $data['stock'] ?? 0;
                $slug = $data['slug'] ?? '';
                $tempImages = $data['temp_images'] ?? [];

                if (empty($sku) || empty($featureValues)) {
                    $errors[] = 'SKU and feature values are required';
                    continue;
                }

                // Auto-generate slug
                if (empty($slug)) {
                    $valueString = implode('-', array_values($featureValues));
                    $slug = Str::slug($product->name . '-' . $valueString . '-' . $sku);
                } else {
                    $slug = Str::slug($slug);
                }

                $existing = $this->variantModel->where('slug', $slug)
                    ->where('product_id', $productId)
                    ->first();

                if ($existing) {
                    $slug = $slug . '-' . uniqid();
                }

                $variantInsertData = [
                    'product_id' => $productId,
                    'sku' => $sku,
                    'slug' => $slug,
                    'price' => $price,
                    'sale_price' => $salePrice,
                    'discount' => $discount,
                    'rating' => $rating,
                    'stock' => $stock,
                    'status' => 1,
                ];

                $variant = $this->variantModel->create($variantInsertData);

                if ($variant) {
                    $this->variantValueModel->saveVariantValues($variant->id, $featureValues);

                    // Handle temp images
                    if (!empty($tempImages)) {
                        $existingImages = $this->variantImageModel->where('variant_id', $variant->id)->count();

                        foreach ($tempImages as $index => $tempImage) {
                            $tempPath = $tempImage['path'] ?? '';
                            if (empty($tempPath)) continue;

                            $newPath = str_replace('temp/', 'variants/', $tempPath);

                            if (Storage::disk('public')->exists($tempPath)) {
                                Storage::disk('public')->move($tempPath, $newPath);

                                $this->variantImageModel->create([
                                    'variant_id' => $variant->id,
                                    'product_id' => $productId,
                                    'image' => $newPath,
                                    'is_primary' => ($existingImages + $index) === 0 ? 1 : 0,
                                    'sort_order' => $existingImages + $index,
                                ]);
                            }
                        }
                    }

                    $savedCount++;
                    $savedVariants[] = [
                        'id' => $variant->id,
                        'sku' => $sku,
                        'slug' => $slug,
                    ];
                } else {
                    $errors[] = 'Failed to save variant with SKU: ' . $sku;
                }
            }

            Log::info($savedCount . ' variants saved for product ID: ' . $productId . ' by admin');

            return response()->json([
                'success' => true,
                'message' => $savedCount . ' variants saved successfully',
                'data' => [
                    'saved_count' => $savedCount,
                    'saved_variants' => $savedVariants,
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Save variants error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a variant.
     *
     * @param int $variantId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteVariant($variantId)
    {
        try {
            $variant = $this->variantModel->find($variantId);

            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant not found',
                ], 404);
            }

            // Delete variant values
            $this->variantValueModel->where('variant_id', $variantId)->delete();

            // Delete variant images
            $images = $this->variantImageModel->where('variant_id', $variantId)->get();
            foreach ($images as $image) {
                if (Storage::disk('public')->exists($image->image)) {
                    Storage::disk('public')->delete($image->image);
                }
                $image->delete();
            }

            $variant->delete();

            Log::info('Variant deleted: ID ' . $variantId . ' by admin');

            return response()->json([
                'success' => true,
                'message' => 'Variant deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete variant error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get combinations for product.
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCombinations($productId)
{
    $variants = $this->variantModel->getVariantsWithValues($productId);

    foreach ($variants as &$variant) {
        $variant['images'] = $this->variantImageModel->getImagesByVariant($variant['id']);
    }

    return response()->json([
        'success' => true,
        'data' => $variants,     // ← array of variants with values + images
    ]);
}

    /**
     * Delete a combination.
     *
     * @param int $variantId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCombination($variantId)
    {
        return $this->deleteVariant($variantId);
    }

    /**
     * Update a combination.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCombination(Request $request)
    {
        try {
            $variantId = $request->variant_id;
            $sku = $request->sku;
            $price = $request->price;
            $salePrice = $request->sale_price ?? 0;
            $discount = $request->discount ?? 0;
            $rating = $request->rating ?? 0;
            $stock = $request->stock;
            $slug = $request->slug;

            $variant = $this->variantModel->find($variantId);

            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Combination not found',
                ], 404);
            }

            $data = [
                'sku' => $sku,
                'slug' => $slug ?: $variant->slug,
                'price' => $price,
                'sale_price' => $salePrice,
                'discount' => $discount,
                'rating' => $rating,
                'stock' => $stock,
            ];

            $variant->update($data);

            Log::info('Combination updated: ID ' . $variantId . ' by admin');

            return response()->json([
                'success' => true,
                'message' => 'Combination updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Update combination error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== VARIANT IMAGE MANAGEMENT ====================

    /**
     * Upload variant image.
     *
     * @param Request $request
     * @param int $variantId
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadVariantImage(Request $request, $variantId)
    {
        try {
            $variant = $this->variantModel->find($variantId);

            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $path = $request->file('image')->store('variants', 'public');

            $existingImages = $this->variantImageModel->where('variant_id', $variantId)->count();
            $isPrimary = ($existingImages === 0) ? 1 : 0;

            $image = $this->variantImageModel->create([
                'variant_id' => $variantId,
                'product_id' => $variant->product_id,
                'image' => $path,
                'is_primary' => $isPrimary,
                'sort_order' => $existingImages,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => [
                    'id' => $image->id,
                    'image' => $path,
                    'is_primary' => $isPrimary,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Upload variant image error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get variant images.
     *
     * @param int $variantId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVariantImages($variantId)
    {
        try {
            $images = $this->variantImageModel->getImagesByVariant($variantId);

            return response()->json([
                'success' => true,
                'data' => $images,
            ]);
        } catch (\Exception $e) {
            Log::error('Get variant images error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete variant image.
     *
     * @param int $imageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteVariantImage($imageId)
    {
        try {
            $image = $this->variantImageModel->find($imageId);

            if (!$image) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found',
                ], 404);
            }

            if (Storage::disk('public')->exists($image->image)) {
                Storage::disk('public')->delete($image->image);
            }

            $variantId = $image->variant_id;
            $wasPrimary = $image->is_primary;

            $image->delete();

            // If deleted image was primary, set another as primary
            if ($wasPrimary) {
                $firstImage = $this->variantImageModel->where('variant_id', $variantId)
                    ->orderBy('sort_order', 'ASC')
                    ->first();

                if ($firstImage) {
                    $firstImage->update(['is_primary' => 1]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Delete variant image error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set primary variant image.
     *
     * @param int $imageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function setPrimaryVariantImage($imageId)
    {
        try {
            $image = $this->variantImageModel->find($imageId);

            if (!$image) {
                return response()->json([
                    'success' => false,
                    'message' => 'Image not found',
                ], 404);
            }

            $this->variantImageModel->setPrimaryImage($image->variant_id, $imageId);

            return response()->json([
                'success' => true,
                'message' => 'Primary image updated',
            ]);
        } catch (\Exception $e) {
            Log::error('Set primary variant image error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attach variant images.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function attachVariantImages(Request $request)
    {
        try {
            $variantId = $request->variant_id;
            $images = $request->images ?? [];

            if (is_string($images)) {
                $images = json_decode($images, true);
            }

            if (empty($images)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No images to attach',
                ], 422);
            }

            $variant = $this->variantModel->find($variantId);

            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant not found',
                ], 404);
            }

            $existingImages = $this->variantImageModel->where('variant_id', $variantId)->count();
            $attached = 0;
            $uploadedImages = [];

            foreach ($images as $index => $image) {
                $tempPath = $image['path'] ?? '';
                if (empty($tempPath)) continue;

                // Get the filename from temp path
                $filename = basename($tempPath);
                $newPath = 'variants/' . $filename;

                // Move file from temp to variants
                if (Storage::disk('public')->exists($tempPath)) {
                    // If file already exists, generate new name
                    if (Storage::disk('public')->exists($newPath)) {
                        $newName = uniqid() . '_' . $filename;
                        $newPath = 'variants/' . $newName;
                    }

                    Storage::disk('public')->move($tempPath, $newPath);

                    $imageData = [
                        'variant_id' => $variantId,
                        'product_id' => $variant->product_id,
                        'image' => $newPath,
                        'is_primary' => ($existingImages + $attached) === 0 ? 1 : 0,
                        'sort_order' => $existingImages + $attached,
                    ];

                    $imageModel = $this->variantImageModel->create($imageData);

                    if ($imageModel) {
                        $attached++;
                        $uploadedImages[] = [
                            'id' => $imageModel->id,
                            'image' => $newPath,
                            'is_primary' => $imageData['is_primary'],
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => $attached . ' images attached successfully',
                'data' => $uploadedImages,
            ]);
        } catch (\Exception $e) {
            Log::error('Attach variant images error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== PRICE/STOCK UPDATE ====================

    /**
     * Update product price (AJAX).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePrice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $productId = $request->product_id;
        $price = $request->price;
        $salePrice = $request->sale_price;

        // Validate sale price is not greater than price
        if (!empty($salePrice) && $salePrice > $price) {
            return response()->json([
                'success' => false,
                'message' => 'Sale price cannot be greater than regular price',
            ], 422);
        }

        $product = $this->productModel->find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $product->update([
            'price' => $price,
            'sale_price' => $salePrice,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Price updated successfully',
            'data' => [
                'price' => number_format($price, 2),
                'sale_price' => $salePrice ? number_format($salePrice, 2) : null,
            ],
        ]);
    }

    /**
     * Update product stock (AJAX).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'stock' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = $this->productModel->find($request->product_id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $product->update(['stock' => $request->stock]);

        return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully',
            'data' => ['stock' => $request->stock],
        ]);
    }

    // ==================== SPECIFICATION MANAGEMENT ====================

    /**
     * Get product specifications.
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSpecifications($productId)
    {
        $specifications = DB::table('product_specifications')
            ->where('product_id', $productId)
            ->orderBy('sort_order', 'ASC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $specifications,
        ]);
    }

    /**
     * Save product specifications.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSpecifications(Request $request)
    {
        $productId = $request->product_id;
        $specifications = $request->specifications;
    if (is_string($specifications)) {
        $specifications = json_decode($specifications, true) ?? [];
    }

    $deletedIds = $request->deleted_ids;
    if (is_string($deletedIds)) {
        $deletedIds = json_decode($deletedIds, true) ?? [];
    }


        if (!$productId) {
            return response()->json([
                'success' => false,
                'message' => 'Product ID is required',
            ], 422);
        }

        try {
            DB::transaction(function () use ($productId, $specifications, $deletedIds) {
                // Delete specifications that were removed
                if (!empty($deletedIds)) {
                    DB::table('product_specifications')
                        ->whereIn('id', $deletedIds)
                        ->where('product_id', $productId)
                        ->delete();
                }

                // Update or insert specifications
                if (!empty($specifications)) {
                    $sortOrder = 0;
                    foreach ($specifications as $spec) {
                        $data = [
                            'product_id' => $productId,
                            'label' => trim($spec['label']),
                            'value' => trim($spec['value']),
                            'sort_order' => $sortOrder,
                        ];

                        if (!empty($spec['id']) && is_numeric($spec['id'])) {
                            DB::table('product_specifications')
                                ->where('id', $spec['id'])
                                ->where('product_id', $productId)
                                ->update($data);
                        } else {
                            DB::table('product_specifications')->insert($data);
                        }
                        $sortOrder++;
                    }
                }
            });

            // Get updated specifications
            $updatedSpecs = DB::table('product_specifications')
                ->where('product_id', $productId)
                ->orderBy('sort_order', 'ASC')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Specifications saved successfully',
                'data' => $updatedSpecs,
            ]);
        } catch (\Exception $e) {
            Log::error('Save specifications error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete all product specifications.
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAllSpecifications($productId)
    {
        try {
            $deleted = DB::table('product_specifications')
                ->where('product_id', $productId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'All specifications deleted successfully',
                'data' => ['deleted_count' => $deleted],
            ]);
        } catch (\Exception $e) {
            Log::error('Delete all specifications error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== BULK OPERATIONS ====================

    /**
     * Bulk delete products.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $deleted = 0;
        foreach ($request->ids as $id) {
            // Call delete method which handles all related data
            $result = $this->delete($id);
            if ($result->getData()->success) {
                $deleted++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $deleted . ' products deleted successfully',
            'data' => ['deleted_count' => $deleted],
        ]);
    }

    /**
     * Bulk update product status.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:products,id',
            'status' => ['required', Rule::in(['active', 'inactive', 'draft'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = $this->productModel->whereIn('id', $request->ids)
            ->update(['status' => $request->status]);

        Log::info('Bulk status update: ' . $updated . ' products updated to ' . $request->status . ' by admin');

        return response()->json([
            'success' => true,
            'message' => $updated . ' products updated successfully',
            'data' => ['updated_count' => $updated],
        ]);
    }

    /**
     * Get product statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = [
            'total' => $this->productModel->count(),
            'active' => $this->productModel->where('status', 'active')->count(),
            'inactive' => $this->productModel->where('status', 'inactive')->count(),
            'draft' => $this->productModel->where('status', 'draft')->count(),
            'in_stock' => $this->productModel->where('stock', '>', 0)->count(),
            'out_of_stock' => $this->productModel->where('stock', '<=', 0)->count(),
            'featured' => $this->productModel->where('is_featured', 1)->count(),
            'on_home' => $this->productModel->where('is_home', 1)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Export products to CSV.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportCsv(Request $request)
    {
        $status = $request->get('status', 'all');
        $category = $request->get('category');

        $query = $this->productModel->with('category');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($category) {
            $query->where('category_id', $category);
        }

        $products = $query->get();

        $filename = 'products_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($products) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'ID', 'Name', 'Slug', 'SKU', 'Price', 'Sale Price',
                'Stock', 'Category', 'Status', 'Featured', 'On Home',
                'Rating', 'Discount', 'Created At',
            ]);

            foreach ($products as $product) {
                fputcsv($output, [
                    $product->id,
                    $product->name,
                    $product->slug,
                    $product->sku,
                    number_format($product->price, 2),
                    $product->sale_price ? number_format($product->sale_price, 2) : '',
                    $product->stock,
                    $product->category?->name ?? '',
                    $product->status,
                    $product->is_featured ? 'Yes' : 'No',
                    $product->is_home ? 'Yes' : 'No',
                    $product->rating ?? 0,
                    $product->discount ?? 0,
                    $product->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }
}