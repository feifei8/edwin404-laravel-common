<?php

namespace Edwin404\Data\Services;


use Edwin404\Base\Support\FileHelper;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class AbstractDataStorageService
{
    protected $storage;
    protected $option = [];

    function __construct()
    {
        config(['filesystems.disks.data' => [
            'driver' => 'local',
            'root' => base_path('public/')
        ]]);
        $this->storage = Storage::disk('data');
    }

    public function get($filename)
    {
        if ($this->storage->has($filename)) {
            return $this->storage->get($filename);
        }
        return null;
    }

    public function put($filename, $content)
    {
        $this->storage->put($filename, $content);
    }

    public function size($filename)
    {
        return $this->storage->size($filename);
    }

    protected function multiPartInitToken($param)
    {
        $category = $param['category'];
        $file = $param['file'];
        ksort($file, SORT_STRING);
        $hash = md5(serialize($file));
        $hashFile = DataService::DATA_CHUNK . '/token/' . $hash . '.php';
        if (file_exists($hashFile)) {
            $file = (include $hashFile);
        } else {
            $file['chunkUploaded'] = 0;
            $file['hash'] = $hash;

            // 计算临时文件路径
            $extension = FileHelper::extension($file['name']);
            $file['path'] = strtolower(Str::random(32)) . '.' . $extension;
            $file['fullPath'] = DataService::DATA_TEMP . '/' . $category . '/' . $file['path'];

        }
        return $file;
    }

    protected function uploadChunkTokenAndDeleteToken($token)
    {
        $hash = $token['hash'];
        $hashFile = DataService::DATA_CHUNK . '/token/' . $hash . '.php';
        $this->storage->delete($hashFile);
    }

    protected function uploadChunkTokenAndUpdateToken($token)
    {
        $hash = $token['hash'];
        $hashFile = DataService::DATA_CHUNK . '/token/' . $hash . '.php';
        $this->storage->put($hashFile, '<' . '?php return ' . var_export($token, true) . ';');
    }

    public function init($option)
    {
        $this->option = $option;
    }

    public function multiPartInit($param)
    {
        throw new \Exception('you should overwrite multiPartInit function');
    }

    public function multiPartUpload($param)
    {
        throw new \Exception('you should overwrite multiPartUpload function');
    }

    public function exists($filename)
    {
        throw new \Exception('you should overwrite exists function');
    }

    public function move($from, $to)
    {
        throw new \Exception('you should overwrite move function');
    }

    public function delete($filename)
    {
        throw new \Exception('you should overwrite move function');
    }

}