<?php

namespace App\Traits;


trait Loggable
{
    public function log($message)
    {
        \Log::info($message);
    }

    public function logError($message)
    {
        \Log::error($message);
    }
}
