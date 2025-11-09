<?php

namespace App\Support;

/**
 * Simple Toast builder to standardize structure across web + JSON.
 */
class Toast
{
    public static function make(
        string $message,
        string $type = 'info',
        string $position = 'tc',
        int $timeout = 2000,
        string $size = 'sm'
    ): array {
        return compact('type','message','position','timeout','size');
    }

    public static function success(string $message, int $timeout = 1600): array
    { return self::make($message,'success','uc',$timeout,'lg'); }

    public static function info(string $message, int $timeout = 1600): array
    { return self::make($message,'info','uc',$timeout,'lg'); }

    public static function error(string $message, int $timeout = 2000): array
    { return self::make($message,'error','uc',$timeout,'lg'); }

    public static function warning(string $message, int $timeout = 2000): array
    { return self::make($message,'warning','uc',$timeout,'lg'); }

    public static function from(array $raw): array
    {
        return self::make(
            $raw['message'] ?? '',
            $raw['type'] ?? 'info',
            $raw['position'] ?? 'tc',
            (int) ($raw['timeout'] ?? 2000),
            $raw['size'] ?? 'sm'
        );
    }
}