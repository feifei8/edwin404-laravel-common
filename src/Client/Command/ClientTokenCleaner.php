<?php

namespace Edwin404\Client\Command;

use Carbon\Carbon;
use Edwin404\Base\Support\ModelHelper;
use Illuminate\Console\Command;

class ClientTokenCleaner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ClientTokenCleaner';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ClientTokenCleaner';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ModelHelper::model('client_token')->where('expireTime', '<', Carbon::now())->delete();
    }
}