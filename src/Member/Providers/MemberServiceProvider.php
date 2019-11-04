<?php

namespace Edwin404\Member\Providers;

use Edwin404\Member\Services\MemberMessageService;
use Edwin404\Member\Services\MemberUploadService;
use Illuminate\Support\ServiceProvider;

class MemberServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->singleton('memberUploadService', MemberUploadService::class);
        $this->app->singleton('memberMessageService', MemberMessageService::class);
    }
}
