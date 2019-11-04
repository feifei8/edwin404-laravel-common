<?php

namespace Edwin404\Partner\Services;


use Edwin404\Base\Support\ModelHelper;
use Illuminate\Support\Facades\Cache;

class PartnerService
{
    const CACHE_KEY_PREFIX = 'edwin404.partner.';

    public function listByPosition($position = 'home')
    {
        return ModelHelper::model('partner')->where(['position' => $position])->orderBy('sort', 'asc')->get()->toArray();
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