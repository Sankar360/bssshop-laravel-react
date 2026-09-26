<?php

use App\Models\ProductSpecification;

if (!function_exists('getProductSpecifications')) {
    /**
     * Get product specifications.
     */
    function getProductSpecifications($productId): array
    {
        if (empty($productId)) {
            return [];
        }

        return ProductSpecification::where('product_id', $productId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->toArray();
    }
}

if (!function_exists('format_rupee')) {
    function format_rupee($amount): string
    {
        return '₹' . number_format((float) $amount, 2);
    }
}

if (!function_exists('format_rupee_short')) {
    function format_rupee_short($amount): string
    {
        $amount = (float) $amount;

        if ($amount >= 10000000) {
            return '₹' . round($amount / 10000000, 2) . ' Cr';
        }
        if ($amount >= 100000) {
            return '₹' . round($amount / 100000, 2) . ' L';
        }
        if ($amount >= 1000) {
            return '₹' . round($amount / 1000, 2) . 'K';
        }

        return '₹' . number_format($amount, 2);
    }
}