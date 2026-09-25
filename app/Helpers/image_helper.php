<?php

if (!function_exists('optimized_image')) {
    /**
     * Build an <img> tag with lazy loading.
     */
    function optimized_image(
        string $path,
        string $alt = '',
        $width = null,
        $height = null,
        string $class = ''
    ): string {
        $img = '<img src="' . e(url($path)) . '" alt="' . e($alt) . '"';

        if ($width)  { $img .= ' width="'  . e((string) $width)  . '"'; }
        if ($height) { $img .= ' height="' . e((string) $height) . '"'; }
        if ($class)  { $img .= ' class="'  . e($class)           . '"'; }

        $img .= ' loading="lazy" decoding="async">';

        return $img;
    }
}