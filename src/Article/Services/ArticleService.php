<?php

namespace Edwin404\Article\Services;


use Edwin404\Base\Support\ModelHelper;
use Illuminate\Support\Facades\Cache;

class ArticleService
{
    const CACHE_KEY_PREFIX = 'edwin404.article.';

    public function listByPosition($position = 'home')
    {
        return ModelHelper::model('article')->where(['position' => $position])->orderBy('id', 'asc')->get()->toArray();
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