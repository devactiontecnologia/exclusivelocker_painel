<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class Brand
{
    public static function name(): string
    {
        return (string) (Setting::get('empresa') ?: config('app.name', 'Exclusive Locker'));
    }

    public static function subtitle(): string
    {
        return (string) (Setting::get('documento') ?: 'Locker Master');
    }

    public static function mark(): string
    {
        $name = self::name();
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = collect($parts)->filter()->take(2)->map(
            fn ($p) => mb_strtoupper(mb_substr($p, 0, 1))
        )->implode('');

        return $initials !== '' ? $initials : 'EL';
    }

    public static function primaryColor(): string
    {
        $color = (string) Setting::get('cor_primaria', '#E2A84A');

        return preg_match('/^#[0-9A-Fa-f]{3,8}$/', $color) ? $color : '#E2A84A';
    }

    public static function logoUrl(): ?string
    {
        return self::publicAssetUrl(Setting::get('logo_path'));
    }

    public static function faviconUrl(): ?string
    {
        return self::publicAssetUrl(Setting::get('favicon_path'));
    }

    public static function toArray(): array
    {
        return [
            'name' => self::name(),
            'subtitle' => self::subtitle(),
            'mark' => self::mark(),
            'primary' => self::primaryColor(),
            'logo' => self::logoUrl(),
            'favicon' => self::faviconUrl(),
        ];
    }

    private static function publicAssetUrl(mixed $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', (string) $path), '/');
        if ($path === '') {
            return null;
        }

        // Novo destino: public/branding/...
        $absolute = public_path($path);
        if (is_file($absolute)) {
            return asset($path).'?v='.filemtime($absolute);
        }

        // Legado: storage/app/public/...
        if (Storage::disk('public')->exists($path)) {
            $full = Storage::disk('public')->path($path);

            return asset('storage/'.$path).'?v='.(is_file($full) ? filemtime($full) : time());
        }

        return null;
    }
}
