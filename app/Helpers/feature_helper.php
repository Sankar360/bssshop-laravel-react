<?php

use App\Models\FeatureValue;
use App\Models\ProductCategory;

if (!function_exists('getFeatureValue')) {
    function getFeatureValue($featureValueId): ?array
    {
        if (empty($featureValueId)) {
            return null;
        }

        static $cache = [];

        if (array_key_exists($featureValueId, $cache)) {
            return $cache[$featureValueId];
        }

        $row = FeatureValue::select('id', 'value')->find($featureValueId);

        return $cache[$featureValueId] = $row ? $row->toArray() : null;
    }
}

if (!function_exists('getFeatureValueText')) {
    function getFeatureValueText($valueId): string
    {
        if (empty($valueId)) {
            return '';
        }

        static $cache = [];

        if (isset($cache[$valueId])) {
            return $cache[$valueId];
        }

        $row = FeatureValue::select('value')->find($valueId);

        return $cache[$valueId] = $row->value ?? '';
    }
}

if (!function_exists('getProductSubcategory')) {
    function getProductSubcategory($subcategoryId): ?array
    {
        if (empty($subcategoryId)) {
            return null;
        }

        static $cache = [];

        if (array_key_exists($subcategoryId, $cache)) {
            return $cache[$subcategoryId];
        }

        $row = ProductCategory::select('id', 'name', 'link')->find($subcategoryId);

        return $cache[$subcategoryId] = $row ? $row->toArray() : null;
    }
}

if (!function_exists('getFeatureValueWithDetails')) {
    function getFeatureValueWithDetails($valueId): ?array
    {
        if (empty($valueId) || !is_numeric($valueId)) {
            return null;
        }

        $row = FeatureValue::select('id', 'value', 'sort_order')
            ->find((int) $valueId);

        return $row ? $row->toArray() : null;
    }
}

if (!function_exists('getFeatureValues')) {
    /**
     * Get multiple feature values keyed by their ID.
     */
    function getFeatureValues($valueIds): array
    {
        if (empty($valueIds)) {
            return [];
        }

        $valueIds = array_map('intval', (array) $valueIds);

        $rows = FeatureValue::select('id', 'value', 'sort_order')
            ->whereIn('id', $valueIds)
            ->orderBy('sort_order')
            ->get();

        $return = [];
        foreach ($rows as $row) {
            $return[$row->id] = $row->toArray();
        }

        return $return;
    }
}