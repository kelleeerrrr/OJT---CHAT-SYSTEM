<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function isChatEnabled(): bool
    {
        return self::get('chat_enabled', 'true') === 'true';
    }

    public static function toggleChat(): bool
    {
        $current = self::isChatEnabled();
        self::set('chat_enabled', $current ? 'false' : 'true');
        return !$current;
    }
}
