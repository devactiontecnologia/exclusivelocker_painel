<?php

namespace App\Support;

class NavIcons
{
    public static function svg(string $name): string
    {
        $icons = [
            'home' => '<path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z"/>',
            'chart' => '<path d="M4 19h16M7 16V9m5 7V5m5 11v-6"/>',
            'mail' => '<rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4 7 8 6 8-6"/>',
            'history' => '<path d="M4 12a8 8 0 1 0 2.3-5.6"/><path d="M4 5v4h4"/><path d="M12 8v5l3 2"/>',
            'locker' => '<rect x="5" y="4" width="14" height="16" rx="2"/><path d="M9 12h.01M12 8v8"/>',
            'building' => '<path d="M5 21V5.5A1.5 1.5 0 0 1 6.5 4h7A1.5 1.5 0 0 1 15 5.5V21M15 10h3.5A1.5 1.5 0 0 1 20 11.5V21"/><path d="M8 8h3M8 12h3M8 16h3M19 21H5"/>',
            'people' => '<circle cx="9" cy="8" r="3"/><path d="M4 19c.5-3 2.5-5 5-5s4.5 2 5 5"/><circle cx="16.5" cy="9" r="2.2"/><path d="M16 14.2c2.2.3 3.8 2 4.3 4.8"/>',
            'grid' => '<rect x="4" y="4" width="6" height="6" rx="1.2"/><rect x="14" y="4" width="6" height="6" rx="1.2"/><rect x="4" y="14" width="6" height="6" rx="1.2"/><rect x="14" y="14" width="6" height="6" rx="1.2"/>',
            'sync' => '<path d="M20 12a8 8 0 0 1-13.7 5.6M4 12A8 8 0 0 1 17.7 6.4"/><path d="M20 16v-4h-4M4 8V4h4"/>',
            'bell' => '<path d="M6 9a6 6 0 1 1 12 0c0 7 2 8 2 8H4s2-1 2-8"/><path d="M10 20a2 2 0 0 0 4 0"/>',
            'support' => '<path d="M4 12a8 8 0 0 1 16 0v5a2 2 0 0 1-2 2h-2v-6h3"/><path d="M4 12v5a2 2 0 0 0 2 2h2v-6H4"/><path d="M12 19v2"/>',
            'alert' => '<path d="M12 4 3.2 19h17.6L12 4z"/><path d="M12 10v4M12 17h.01"/>',
            'report' => '<path d="M7 4h7l5 5v11a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"/><path d="M14 4v5h5M9 13h6M9 17h4"/>',
            'audit' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4 4"/>',
            'logs' => '<path d="M8 7h12M8 12h12M8 17h8"/><path d="M4 7h.01M4 12h.01M4 17h.01"/>',
            'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 3.5v2.2M12 18.3v2.2M4.9 6.5l1.6 1.6M17.5 15.9l1.6 1.6M3.5 12h2.2M18.3 12h2.2M4.9 17.5l1.6-1.6M17.5 8.1l1.6-1.6"/>',
            'shield' => '<path d="M12 3.5 5 6.5v6.2c0 4 3 6.6 7 8.3 4-1.7 7-4.3 7-8.3V6.5z"/><path d="m9 12 2 2 4-4"/>',
            'plus' => '<path d="M12 6v12M6 12h12"/>',
            'search' => '<circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/>',
            'collapse' => '<path d="M15 6 9 12l6 6M8 6v12"/>',
            'expand' => '<path d="M9 6 15 12 9 18M16 6v12"/>',
            'menu' => '<path d="M5 7h14M5 12h14M5 17h14"/>',
            'logout' => '<path d="M10 5H6.5A1.5 1.5 0 0 0 5 6.5v11A1.5 1.5 0 0 0 6.5 19H10"/><path d="m13 8 4 4-4 4M17 12H9"/>',
            'dot' => '<circle cx="12" cy="12" r="3"/>',
            'bolt' => '<path d="M13 3 5.5 13h6L11 21l7.5-10h-6z"/>',
            'clock' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4.5L15 16"/>',
        ];

        $path = $icons[$name] ?? $icons['dot'];

        return '<svg class="nav-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$path.'</svg>';
    }
}
