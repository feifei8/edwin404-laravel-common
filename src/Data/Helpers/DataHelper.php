<?php

namespace Edwin404\Data\Helpers;


class DataHelper
{
    public static function imageLimit($path, $width, $height)
    {
        $path = preg_replace(
            '(data\\/[a-z0-9_]+\\/\\d+\\/\\d+\\/\\d+\\/[a-z0-9_]+\\.[a-z0-9]+)',
            'data_image/\\0/limit_' . $width . 'x' . $height,
            $path);
        return $path;
    }
}