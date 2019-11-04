<?php

namespace Edwin404\Common\Command;

use Edwin404\Base\Support\FileHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TempFileCleaner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TempFileCleaner';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'TempFileCleaner';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $files = FileHelper::listFiles(public_path('temp'));
        foreach ($files as $file) {
            if ($file['mtime'] < time() - 20 * 24 * 3600) {
                @unlink($file['pathname']);
                echo date('Y-m-d H:i:s') . " - delete:" . $file['pathname'] . " - mtime:" . date('Y-m-d H:i:s', $file['mtime']) . "\n";
            }
        }

        $dirs = FileHelper::listFiles(public_path('data_temp'));
        foreach ($dirs as $dir) {
            $files = FileHelper::listFiles($dir['pathname']);
            foreach ($files as $file) {
                if ($file['mtime'] < time() - 20 * 24 * 3600) {
                    @unlink($file['pathname']);
                    echo date('Y-m-d H:i:s') . " - delete:" . $file['pathname'] . " - mtime:" . date('Y-m-d H:i:s', $file['mtime']) . "\n";
                }
            }
        }

    }
}