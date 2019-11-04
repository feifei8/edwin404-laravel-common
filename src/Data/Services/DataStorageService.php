<?php

namespace Edwin404\Data\Services;

use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\Response;

class DataStorageService extends AbstractDataStorageService
{
    private function combine($filename)
    {
        $root = config('filesystems.disks.data.root');
        $out = @fopen($root . $filename, "wb");
        if (flock($out, LOCK_EX)) {
            for ($i = 0; ; $i++) {
                if (!$this->storage->has($filename . '.' . $i)) {
                    break;
                }
                $content = file_get_contents($root . $filename . '.' . $i);
                fwrite($out, $content);
                @unlink($root . $filename . '.' . $i);
            }
            flock($out, LOCK_UN);
        }
        fclose($out);
    }

    public function multiPartInit($param)
    {
        $token = $this->multiPartInitToken($param);
        $this->uploadChunkTokenAndUpdateToken($token);
        return Response::generate(0, 'ok', $token);
    }

    public function multiPartUpload($param)
    {
        $token = $this->multiPartInitToken($param);
        $input = $param['input'];
        $category = $param['category'];

        $data = [];
        if (!isset($input['chunks'])) {
            $input['chunks'] = 1;
        }
        if (!isset($input['chunk'])) {
            $input['chunk'] = 0;
        }
        if (empty($input['file'])) {
            return Response::generate(-1, '上传文件为空');
        }

        $data['chunks'] = $input['chunks'];
        $data['chunk'] = $input['chunk'];
        $data['file'] = $input['file'];

        $hash = $token['hash'];
        $hashFile = DataService::DATA_CHUNK . '/data/' . $hash;
        if ($data['chunk'] < $data['chunks']) {
            $content = file_get_contents($data['file']->getRealPath());
            $this->storage->put($hashFile . '.' . $data['chunk'], $content);
            $token['chunkUploaded'] = $data['chunk'] + 1;
            $this->uploadChunkTokenAndUpdateToken($token);
            $data['finished'] = false;
            if ($token['chunkUploaded'] == $data['chunks']) {
                $this->combine($hashFile);
                $this->uploadChunkTokenAndDeleteToken($token);
                $hashFileSize = $this->size($hashFile);
                if ($hashFileSize != $token['size']) {
                    return Response::generate(-1, '文件组合失败(' . $hashFileSize . ',' . $token['size'] . ')');
                }

                $this->move($hashFile, $token['fullPath']);

                // 存储到临时表
                $dataTemp = ModelHelper::add('data_temp', [
                    'category' => $category,
                    'path' => $token['path'],
                    'filename' => $token['name'],
                    'size' => $token['size'],
                ]);

                $data['data'] = $dataTemp;
                $data['path'] = $token['fullPath'];
                $data['finished'] = true;

            }
        }

        return Response::generate(0, 'ok', $data);
    }

    public function exists($filename)
    {
        return file_exists($filename);
    }

    public function move($from, $to)
    {
        $this->storage->move($from, $to);
    }

    public function delete($filename)
    {
        if ($this->storage->has($filename)) {
            $this->storage->delete($filename);
        }
    }

}