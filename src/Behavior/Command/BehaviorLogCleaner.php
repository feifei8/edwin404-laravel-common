<?php

namespace Edwin404\Behavior\Command;

use Carbon\Carbon;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Shop\Jobs\OrderExpireCheckJob;
use Edwin404\Shop\Types\OrderStatus;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\DB;

class BehaviorLogCleaner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'BehaviorLogCleaner';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'BehaviorLogCleaner';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::table('behavior_log')->where('time', '<', time() - 3 * 24 * 3600)->delete();
    }
}