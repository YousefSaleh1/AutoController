<?php

namespace CodingPartners\AutoController\Helpers;

class helper
{
    public static function getSuffix($filename)
    {
        $parts = explode('_', $filename);
        return end($parts);
    }
}
