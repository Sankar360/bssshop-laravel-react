<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantValue;
use App\Models\ProductVariantImage;
use App\Models\ProductFeatureValue;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
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
     * @var ProductFeatureValue
     */
    protected $featureValueModel;

    /**
     * @var Feature
     */
    protected $featureModel;

    /**
     * ProductController constructor.
     */
    public function __construct()
    {
        $this->productModel = new Product();
        $this->categoryModel = new ProductCategory();
        $this->productImageModel = new ProductImage();
        $this->variantModel = new ProductVariant();
        $this->variantValueModel = new ProductVariantValue();
        $this->variantImageModel = new ProductVariantImage();
        $this->featureValueModel = new ProductFeatureValue();
        $this->featureModel = new Feature();
    }

    /**
     * Get product detail by slug.
     *
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($slug)
    {
        if (empty($slug)) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $product = null;
        $selectedVariant = null;
        $isVariantSlug = false;

        // First, try to find by variant slug
        $variant = $this->variantModel->getVariantBySlug($slug);

        Log::debug('Variant lookup for slug: ' . $slug);
        Log::debug('Variant found: ' . json_encode($variant));

        if ($variant) {
            // Found a variant - get the parent product
            $product = $this->productModel->find($variant['product_id']);
            if ($product) {
                $isVariantSlug = true;
                $selectedVariant = $variant;

                // Get variant values if not already loaded
                if (empty($selectedVariant['values'])) {
                    $selectedVariant['values'] = $this->variantValueModel->getVariantValues($selectedVariant['id']);
                }

                // Get variant images if not already loaded
                if (empty($selectedVariant['images'])) {
                    $selectedVariant['images'] = $this->variantImageModel->getImagesByVariant($selectedVariant['id']);
                }
            }
        }

        // If no variant found, try to find product by slug
        if (!$product) {
            $product = $this->productModel->getProductBySlug($slug);
            Log::debug('Product lookup for slug: ' . $slug);
            Log::debug('Product found: ' . json_encode($product));
        }

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Get all variants for this product with values and images
        $variants = $this->variantModel->getVariantsWithValuesAndImages($product['id']);

        Log::debug('Total variants found: ' . count($variants));

        // If no variant was selected from slug, select the first available variant with stock
        if (!$selectedVariant && !empty($variants)) {
            // Try to find first variant with stock
            foreach ($variants as $v) {
                if ($v['stock'] > 0) {
                    $selectedVariant = $v;
                    break;
                }
            }
            // If no variant has stock, just take the first one
            if (!$selectedVariant) {
                $selectedVariant = $variants[0] ?? null;
            }
        }

        // Ensure $selectedVariant is never null
        if ($selectedVariant === null) {
            $selectedVariant = [
                'id' => 0,
                'price' => $product['price'] ?? 0,
                'sale_price' => $product['sale_price'] ?? 0,
                'discount' => $product['discount'] ?? 0,
                'rating' => $product['rating'] ?? 0,
                'stock' => $product['stock'] ?? 0,
                'sku' => $product['sku'] ?? '',
                'slug' => $product['slug'] ?? '',
                'variant_name' => '',
                'values' => [],
                'images' => [],
            ];
        }

        // Merge selected variant data into product
        $product['selected_variant_id'] = $selectedVariant['id'] ?? 0;
        $product['selected_variant_price'] = $selectedVariant['price'] ?? $product['price'] ?? 0;
        $product['selected_variant_sale_price'] = $selectedVariant['sale_price'] ?? $product['sale_price'] ?? 0;
        $product['selected_variant_discount'] = $selectedVariant['discount'] ?? $product['discount'] ?? 0;
        $product['selected_variant_rating'] = $selectedVariant['rating'] ?? $product['rating'] ?? 0;
        $product['selected_variant_stock'] = $selectedVariant['stock'] ?? $product['stock'] ?? 0;
        $product['selected_variant_sku'] = $selectedVariant['sku'] ?? $product['sku'] ?? '';
        $product['selected_variant_slug'] = $selectedVariant['slug'] ?? $product['slug'] ?? '';
        $product['has_variants'] = !empty($variants);

        // Build variant name
        $variantParts = [];
        if (!empty($selectedVariant['values'])) {
            foreach ($selectedVariant['values'] as $v) {
                $variantParts[] = $v['value'] ?? '';
            }
        }
        $product['selected_variant_name'] = !empty($variantParts) ? implode(' / ', $variantParts) : '';
        $selectedVariant['variant_name'] = $product['selected_variant_name'];

        // Get product images
        $images = $this->productImageModel->getImagesByProduct($product['id']);

        // Get selected variant images
        $selectedVariantImages = [];
        if ($selectedVariant && !empty($selectedVariant['images'])) {
            $selectedVariantImages = $selectedVariant['images'];
        }

        // Get product features
        $features = $this->featureValueModel->getProductFeaturesWithDetails($product['id']);

        // Get category name
        $category = null;
        if ($product['category_id']) {
            $category = $this->categoryModel->find($product['category_id']);
        }

        // Get subcategory if exists
        $subcategory = null;
        if (!empty($product['subcategory_id'])) {
            $subcategory = $this->categoryModel->find($product['subcategory_id']);
        }

        // Get related products (same category)
        $relatedProducts = [];
        if ($product['category_id']) {
            $relatedProducts = $this->productModel
                ->where('category_id', $product['category_id'])
                ->where('id', '!=', $product['id'])
                ->where('status', 'active')
                ->limit(4)
                ->get();
        }

        // Build variant features map for the UI
        $featureMap = $this->buildFeatureMap($variants, $selectedVariant);

        Log::debug('Feature Map: ' . json_encode($featureMap));

        // Build variant options for dropdown
        $variantOptions = $this->buildVariantOptions($variants, $selectedVariant);

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'images' => $images,
                'selected_variant_images' => $selectedVariantImages,
                'variants' => $variants,
                'feature_map' => $featureMap,
                'variant_options' => $variantOptions,
                'selected_variant' => $selectedVariant,
                'features' => $features,
                'category' => $category,
                'subcategory' => $subcategory,
                'related_products' => $relatedProducts,
                'is_variant_slug' => $isVariantSlug,
            ],
        ]);
    }

    /**
     * Build feature map for variant dropdowns.
     *
     * @param array $variants
     * @param array $selectedVariant
     * @return array
     */
    private function buildFeatureMap(array $variants, array $selectedVariant): array
{
    $featureMap = [];

    if (empty($variants)) return $featureMap;

    foreach ($variants as $variant) {
        if (empty($variant['values']) || !is_array($variant['values'])) continue;

        foreach ($variant['values'] as $val) {
            $featureId   = $val['feature_id'] ?? 0;
            $featureName = $val['feature_name'] ?? 'Feature';
            $valueId     = $val['value'] ?? '';
            $valueText   = $val['value_text'] ?? $valueId;
            $isColor     = strtolower(trim($featureName)) === 'color';

            if ($featureId <= 0) continue;

            if (!isset($featureMap[$featureId])) {
                $featureMap[$featureId] = [
                    'id'            => $featureId,
                    'name'          => $featureName,
                    'is_color'      => $isColor,
                    'values'        => [],
                    'options_map'   => [],
                    'selected_value'=> null,
                ];
            }

            if (!in_array((string) $valueId, $featureMap[$featureId]['values'], true)) {
                $featureMap[$featureId]['values'][] = (string) $valueId;
            }

            $featureMap[$featureId]['options_map'][(string) $valueId] = $valueText;
        }
    }

    // Sort + reorder
    foreach ($featureMap as &$feature) {
        $ordered = $feature['values'];
        sort($ordered, SORT_NATURAL);
        $feature['values'] = $ordered;

        $reordered = [];
        foreach ($ordered as $vid) {
            $reordered[(string) $vid] = $feature['options_map'][(string) $vid] ?? $vid;
        }
        $feature['options_map'] = $reordered;
    }
    unset($feature);

    // Selected values
    if (!empty($selectedVariant['values'])) {
        foreach ($selectedVariant['values'] as $val) {
            $featureId = $val['feature_id'] ?? 0;
            $valueId   = $val['value'] ?? '';
            if ($featureId > 0 && isset($featureMap[$featureId])) {
                $featureMap[$featureId]['selected_value'] = (string) $valueId;
            }
        }
    }

    return $featureMap;
}

    /**
     * Build variant options for dropdown.
     *
     * @param array $variants
     * @param array $selectedVariant
     * @return array
     */
    private function buildVariantOptions(array $variants, array $selectedVariant): array
    {
        $options = [];

        if (empty($variants)) {
            return $options;
        }

        foreach ($variants as $variant) {
            $variantName = '';
            if (!empty($variant['values'])) {
                $values = $variant['values'];
                if ($values instanceof \Illuminate\Support\Collection) {
                    $values = $values->toArray();
                }
                $parts = array_column((array) $values, 'value');
                $variantName = implode(' / ', $parts);
            }

            $options[] = [
                'id' => $variant['id'],
                'slug' => $variant['slug'],
                'name' => $variantName,
                'price' => $variant['price'],
                'sale_price' => $variant['sale_price'],
                'discount' => $variant['discount'] ?? 0,
                'stock' => $variant['stock'],
                'sku' => $variant['sku'],
                'rating' => $variant['rating'],
                'is_selected' => $selectedVariant && $selectedVariant['id'] == $variant['id'],
                'in_stock' => $variant['stock'] > 0,
                'image' => !empty($variant['images']) ? $variant['images'][0]['image'] ?? null : null,
            ];
        }

        return $options;
    }

    /**
     * Find variant by feature combinations (AJAX).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findVariant(Request $request)
    {
        $productId = $request->input('product_id');
        $featuresRaw = $request->input('features');

        if (!$productId || !$featuresRaw) {
            return response()->json([
                'success' => false,
                'message' => 'Missing data',
            ], 422);
        }

        if (is_array($featuresRaw)) {
            $features = $featuresRaw;
        } elseif (is_string($featuresRaw)) {
            $features = json_decode($featuresRaw, true);
        } else {
            $features = null;
        }
        if (!is_array($features)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid features data',
            ], 422);
        }

        // Get all variants for this product with their values
        $variants = $this->variantModel->getVariantsWithValues($productId);

        $matchingVariant = null;

        foreach ($variants as $variant) {
            if (empty($variant['values'])) {
                continue;
            }

            $variantValues = [];
            foreach ($variant['values'] as $val) {
                $featureId = $val['feature_id'] ?? 0;
                $value = $val['value'] ?? '';
                if ($featureId > 0) {
                    $variantValues[$featureId] = $value;
                }
            }

            // Check if all selected features match
            $match = true;
            foreach ($features as $featureId => $selectedValue) {
                if (!isset($variantValues[$featureId]) || $variantValues[$featureId] !== $selectedValue) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                $matchingVariant = $variant;
                break;
            }
        }

        if ($matchingVariant) {
    $variantImages = $this->variantImageModel->getImagesByVariant($matchingVariant['id']);

    // Readable variant name
    $parts = [];
    foreach ($matchingVariant['values'] as $val) {
        $parts[] = $val['value_text'] ?? $val['value'];
    }
    $variantName = implode(' / ', $parts);

    $product = $this->productModel->find($productId);

    return response()->json([
        'success' => true,
        'data' => [
            'variant' => [
                'id'           => $matchingVariant['id'],
                'slug'         => $matchingVariant['slug'],
                'price'        => $matchingVariant['price'],
                'sale_price'   => $matchingVariant['sale_price'],
                'discount'     => $matchingVariant['discount'] ?? 0,
                'stock'        => $matchingVariant['stock'],
                'rating'       => $matchingVariant['rating'],
                'sku'          => $matchingVariant['sku'],
                'variant_name' => $variantName,
                'images'       => $variantImages,
                'values'       => $matchingVariant['values'], // ✅ include values
                'product_name' => $product['name'] ?? '',
            ],
        ],
    ]);
}

        return response()->json([
            'success' => false,
            'message' => 'No matching variant found',
        ], 404);
    }

    /**
     * Get product quick view (for modal).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function quickView($id)
    {
        $product = $this->productModel->with(['category', 'images'])
            ->where('status', 'active')
            ->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Get variants
        $variants = $this->variantModel->getVariantsWithValues($id);

        // Check if product has variants
        $hasVariants = !empty($variants);

        // Get first available variant if exists
        $selectedVariant = null;
        if ($hasVariants) {
            foreach ($variants as $v) {
                if ($v['stock'] > 0) {
                    $selectedVariant = $v;
                    break;
                }
            }
            if (!$selectedVariant) {
                $selectedVariant = $variants[0] ?? null;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'product' => $product,
                'has_variants' => $hasVariants,
                'variants' => $variants,
                'selected_variant' => $selectedVariant,
                'images' => $product->images,
            ],
        ]);
    }

    /**
     * Get product price for AJAX updates.
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPrice($id, Request $request)
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $variantId = $request->input('variant_id');

        $price = $product->price;
        $salePrice = $product->sale_price;
        $stock = $product->stock;
        $sku = $product->sku;

        if ($variantId && $variantId > 0) {
            $variant = $this->variantModel->find($variantId);
            if ($variant) {
                $price = $variant->price;
                $salePrice = $variant->sale_price;
                $stock = $variant->stock;
                $sku = $variant->sku;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'price' => $price,
                'formatted_price' => '$' . number_format($price, 2),
                'sale_price' => $salePrice,
                'formatted_sale_price' => $salePrice ? '$' . number_format($salePrice, 2) : null,
                'stock' => $stock,
                'in_stock' => $stock > 0,
                'sku' => $sku,
            ],
        ]);
    }

    /**
     * Get product variants (AJAX).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVariants($id)
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $variants = $this->variantModel->getVariantsWithValuesAndImages($id);

        return response()->json([
            'success' => true,
            'data' => $variants,
        ]);
    }

    /**
     * Get product reviews (if implemented).
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReviews($id, Request $request)
    {
        $product = $this->productModel->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        // This is a placeholder - implement with Review model
        // $reviews = Review::where('product_id', $id)->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => [],
                'average_rating' => $product->rating ?? 0,
                'total_reviews' => 0,
                'rating_distribution' => [
                    5 => 0,
                    4 => 0,
                    3 => 0,
                    2 => 0,
                    1 => 0,
                ],
            ],
        ]);
    }
}