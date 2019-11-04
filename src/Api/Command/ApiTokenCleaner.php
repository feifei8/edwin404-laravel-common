<?php

namespace Edwin404\Api\Command;

use Carbon\Carbon;
use Edwin404\Base\Support\ModelHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ApiTokenCleaner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ApiTokenCleaner';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ApiTokenCleaner';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ModelHelper::model('api_token')->where('expireTime', '<', Carbon::now())->delete();
    }
}