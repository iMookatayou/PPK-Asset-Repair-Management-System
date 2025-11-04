<?php

namespace App\Support;

class Toast
{
    public static function push(string $type, string $msg, array $opt = []): void
    {
        session()->flash('toast', array_merge([
            'type' => $type,
            'message' => $msg,
            'position' => 'br',   // br|tr|center
            'timeout' => 2800,    // ms
        ], $opt));
    }

    public static function success(string $msg, array $opt = []): void { self::push('success', $msg, $opt); }
    public static function info(string $msg, array $opt = []): void    { self::push('info', $msg, $opt); }
    public static function warning(string $msg, array $opt = []): void { self::push('warning', $msg, $opt); }
    public static function error(string $msg, array $opt = []): void   { self::push('error', $msg, $opt); }
}
