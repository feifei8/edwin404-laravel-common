<?php

namespace Edwin404\Forum\Providers;

use Edwin404\Forum\Services\ForumService;
use Illuminate\Support\ServiceProvider;

class ForumServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->singleton('forumService', ForumService::class);
    }
}
