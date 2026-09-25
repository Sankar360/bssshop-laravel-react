<?php

if (!function_exists('renderStars')) {
    /**
     * Render star rating HTML.
     */
    function renderStars($rating, bool $showCount = false, int $count = 0): string
    {
        $rating     = (float) $rating;
        $fullStars  = (int) floor($rating);
        $halfStar   = ($rating - $fullStars) >= 0.5;
        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);

        $html = '';
        for ($i = 0; $i < $fullStars; $i++) {
            $html .= '<i class="bi bi-star-fill text-warning"></i>';
        }
        if ($halfStar) {
            $html .= '<i class="bi bi-star-half text-warning"></i>';
        }
        for ($i = 0; $i < $emptyStars; $i++) {
            $html .= '<i class="bi bi-star text-warning"></i>';
        }

        if ($showCount && $count > 0) {
            $html .= ' <span class="text-muted ms-1">(' . $count . ')</span>';
        }

        return $html;
    }
}

if (!function_exists('calculateDiscount')) {
    /**
     * Calculate discount percentage.
     */
    function calculateDiscount($price, $salePrice, $discount = null): float|int
    {
        if ($discount !== null && $discount > 0) {
            return $discount;
        }

        if ($salePrice > 0 && $price > 0 && $salePrice < $price) {
            return round((1 - $salePrice / $price) * 100);
        }

        return 0;
    }
}

if (!function_exists('formatPrice')) {
    /**
     * Format price with optional sale price. Returns HTML.
     */
    function formatPrice($price, $salePrice = null): string
    {
        $html = '';

        if ($salePrice !== null && $salePrice > 0) {
            $html .= '<span class="text-decoration-line-through text-muted me-2">$' . number_format($price, 2) . '</span>';
            $html .= '<span class="text-primary fw-bold">$' . number_format($salePrice, 2) . '</span>';
        } else {
            $html .= '<span class="text-primary fw-bold">$' . number_format($price, 2) . '</span>';
        }

        return $html;
    }
}