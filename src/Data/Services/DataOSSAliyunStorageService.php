<?php

namespace Edwin404\Data\Services;

use Edwin404\Base\Support\FileHelper;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use OSS\OssClient;

class DataOSSAliyunStorageService extends AbstractDataStorageService
{
    /**
     * @var OssClient
     */
    private $client;
    private $bucket;

    public function init($option)
    {
        parent::init($option);
        $this->client = new OssClient(
            $option['aliyunAccessKeyId'],
            $option['aliyunAccessKeySecret'],
            $option['aliyunEndpoint']
        );
        $this->bucket = $option['aliyunBucket'];
    }

    public function multiPartInit($param)
    {
        $token = $this->multiPartInitToken($param);
        $uploadId = $this->client->initiateMultipartUpload($this->bucket, $token['fullPath']);
        if (empty($uploadId)) {
            return Response::generate(-1, '初始化上传失败');
        }
        $token['multiPartUploadId'] = $uploadId;
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
            $options = [
                OssClient::OSS_FILE_UPLOAD => $data['file']->getRealPath(),
                OssClient::OSS_PART_NUM => $data['chunk'] + 1,
            ];
            $eTag = $this->client->uploadPart(
                $this->bucket,
                $token['fullPath'],
                $token['multiPartUploadId'],
                $options
            );
            if (empty($eTag)) {
                return Response::generate(-1, '上传文件失败');
            }
            if (empty($token['partIds'])) {
                $token['partIds'] = [];
            }
            $token['partIds'][] = [
                'PartNumber' => $data['chunk'] + 1,
                'ETag' => $eTag,
                'Size' => filesize($data['file']->getRealPath()),
            ];
            $token['chunkUploaded'] = $data['chunk'] + 1;
            $this->uploadChunkTokenAndUpdateToken($token);
            $data['finished'] = false;
            if ($token['chunkUploaded'] == $data['chunks']) {
                $ret = $this->client->completeMultipartUpload(
                    $this->bucket,
                    $token['fullPath'],
                    $token['multiPartUploadId'],
                    $token['partIds']
                );
                $this->uploadChunkTokenAndDeleteToken($token);
                if (empty($ret['etag'])) {
                    return Response::generate(-1, '文件组合失败');
                }
                $hashFileSize = 0;
                foreach ($token['partIds'] as $partId) {
                    $hashFileSize += $partId['Size'];
                }
                if ($hashFileSize != $token['size']) {
                    return Response::generate(-1, '文件组合失败(' . $hashFileSize . ',' . $token['size'] . ')');
                }

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
        try {
            $ret = $this->client->getObjectMeta($this->bucket, $filename);
            if (empty($ret['etag'])) {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function move($from, $to)
    {
        $this->client->copyObject($this->bucket, $from, $this->bucket, $to);
        $this->client->deleteObject($this->bucket, $from);
    }

    public function delete($filename)
    {
        $ret = $this->client->deleteObject($this->bucket, $filename);
    }


}