<?php

use App\Models\NavigationMenu;

if (!function_exists('getHeaderMenus')) {
    function getHeaderMenus()
    {
        return NavigationMenu::getHeaderMenus();
    }
}

if (!function_exists('getFooterMenus')) {
    function getFooterMenus()
    {
        return NavigationMenu::getFooterMenus();
    }
}

if (!function_exists('renderMenuLink')) {
    /**
     * Render a menu link. Handles internal vs external URLs.
     *
     * @param  array|object  $menu
     */
    function renderMenuLink($menu): string
    {
        $menuName = is_array($menu) ? ($menu['menu_name'] ?? '') : ($menu->menu_name ?? '');
        $url      = is_array($menu) ? ($menu['url']       ?? '#') : ($menu->url       ?? '#');

        if (preg_match('/^https?:\/\//i', $url)) {
            $link   = $url;
            $target = ' target="_blank" rel="noopener noreferrer"';
        } else {
            $link   = url(ltrim($url, '/'));
            $target = '';
        }

        return '<a href="' . e($link) . '"' . $target . '>' . e($menuName) . '</a>';
    }
}