<?php

namespace App\Constants;

class Orientacion
{
    const PORTRAIT = 'portrait';
    const LANDSCAPE = 'landscape';

    public static function isValid($orientation)
    {
        return in_array($orientation, [self::PORTRAIT, self::LANDSCAPE]);
    }
}
