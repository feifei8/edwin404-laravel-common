<?php

namespace Edwin404\Banner\Services;


use Edwin404\Base\Support\ModelHelper;
use Illuminate\Support\Facades\Cache;

class BannerService
{
    const CACHE_KEY_PREFIX = 'edwin404.banner.';

    public function listByPosition($position = 'home')
    {
        return ModelHelper::model('banner')->where(['position' => $position])->orderBy('sort', 'asc')->get()->toArray();
    }

    public function listByPositionWithCache($position = 'home', $minutes = 60)
    {
        return Cache::remember(self::CACHE_KEY_PREFIX . $position, $minutes, function () use ($position) {
            return self::listByPosition($position);
        });
    }

    public function clearCache($position)
    {
        Cache::forget(self::CACHE_KEY_PREFIX . $position);
    }
}