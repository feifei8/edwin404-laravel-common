<?php

namespace Edwin404\Shop\Command;

use Carbon\Carbon;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Shop\Jobs\OrderExpireCheckJob;
use Edwin404\Shop\Types\OrderStatus;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;

class ShopOrderExpireCheck extends Command
{
    use DispatchesJobs;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ShopOrderExpireCheck';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ShopOrderExpireCheck';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $expiredOrders = ModelHelper::model('order')
            ->where(['status' => OrderStatus::WAIT_PAY])
            ->where('created_at', '<', Carbon::now()->subMinutes(30))
            ->limit(10)
            ->get()->toArray();

        foreach ($expiredOrders as $expiredOrder) {
            $this->dispatch(new OrderExpireCheckJob($expiredOrder['id']));
        }

    }
}