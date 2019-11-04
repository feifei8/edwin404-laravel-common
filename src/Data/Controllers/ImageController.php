<?php

namespace Edwin404\Data\Controllers;


use Edwin404\Base\Support\RequestHelper;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;

class ImageController extends Controller
{
    public function limit($file, $width, $height)
    {

        $width = min(max(5, $width), 5000);
        $height = min(max(5, $height), 5000);

        if (empty($file) || !file_exists($file)) {
            return null;
        }

        $cacheFlag = RequestHelper::currentPageUrl();
        $image = Cache::store('file')->get($cacheFlag, null);
        if (null === $image) {
            $image = Image::make($file);
            $whRatio = $image->width() / $image->height();
            $whRatioThumb = $width / $height;
            if ($whRatio > $whRatioThumb) {
                $image->resize($width, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            } else {
                $image->resize(null, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
            $response = $image->response();
            Cache::store('file')->put($cacheFlag, $response, 24 * 60);
            return $response;
        }
        return $image;
    }
}