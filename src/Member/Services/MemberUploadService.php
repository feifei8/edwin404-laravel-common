<?php

namespace Edwin404\Member\Services;


use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\Response;
use Edwin404\Data\Facades\DataFacade;
use Edwin404\Data\Services\DataService;

class MemberUploadService
{
    public function saveTempDataInContent($userId, $content)
    {
        $saveInfo = DataFacade::saveTempDataInContent($content);
        foreach ($saveInfo['map'] as $key => $info) {
            $data = [];
            $data['userId'] = $userId;
            $data['category'] = $info['data']['category'];
            $data['dataId'] = $info['data']['id'];
            if (!ModelHelper::exists('member_upload', $data)) {
                ModelHelper::add('member_upload', $data);
            }
        }
        return $saveInfo['content'];
    }

    public function uploadTempHandle($userId, $category, $input)
    {
        $file = [];
        foreach (['name', 'type', 'lastModifiedDate', 'size'] as $k) {
            if (empty($input[$k])) {
                return Response::generate(-1, $k . '为空');
            }
            $file[$k] = $input[$k];
        }

        return DataFacade::uploadHandle($category, $input, ['userId' => $userId]);
    }

    /**
     * 用户上传图片文件
     *
     * @param $userId
     * @param $category
     * @param $filename
     * @param $content
     * @return array ['code'=>'0','msg'=>'ok','data'=>MemberUpload]
     */
    public function upload($userId, $category, $filename, $content)
    {

        $uploadRet = DataFacade::upload($category, $filename, $content);
        if ($uploadRet['code']) {
            return $uploadRet;
        }

        $data = $uploadRet['data'];

        ModelHelper::add('member_upload', ['userId' => $userId, 'dataId' => $data['id'], 'category' => $category,]);

        return Response::generate(0, 'ok', $data);
    }

    /**
     * @param $userId
     * @param $tempPath
     * @return array [code=>0,msg=>null,data=>Data]
     */
    public function storeTempByPath($userId, $tempPath)
    {
        $tempData = DataFacade::loadTempDataByPath($tempPath);
        if (empty($tempData)) {
            return Response::generate(-1, '临时文件不存在');
        }

        $storeRet = DataFacade::storeTempDataByPath($tempPath);
        if ($storeRet['code']) {
            return $storeRet;
        }

        $data = $storeRet['data']['data'];

        ModelHelper::add('member_upload', ['userId' => $userId, 'dataId' => $data['id'], 'category' => $tempData['category'],]);

        return Response::generate(0, 'ok', ['data' => $data]);
    }

    public function deleteByDataId($userId, $dataId)
    {
        $m = ModelHelper::load('member_upload', ['userId' => $userId, 'dataId' => $dataId]);
        if (empty($m)) {
            return;
        }
        DataFacade::deleteById($dataId);
        ModelHelper::delete('member_upload', ['id' => $m['id']]);
    }

    public function deleteByPath($userId, $path)
    {
        $data = DataFacade::loadByPath($path);
        if (empty($data)) {
            return;
        }
        $this->deleteByDataId($userId, $data['id']);
    }

    public function paginate($memberUserId, $category, $page, $pageSize)
    {
        $option = [];
        $option['order'] = ['id', 'desc'];
        $option['where'] = ['userId' => $memberUserId, 'category' => $category];

        $paginateData = ModelHelper::modelPaginate('member_upload', $page, $pageSize, $option);
        ModelHelper::modelJoin($paginateData['records'], 'dataId', '_data', 'data');

        $list = [];
        foreach ($paginateData['records'] as $record) {
            if (empty($record['_data'])) {
                continue;
            }
            $item = [];
            $item['path'] = DataService::DATA . '/' . $record['_data']['category'] . '/' . $record['_data']['path'];
            $item['filename'] = htmlspecialchars($record['_data']['filename']);
            $list[] = $item;
        }
        return [
            'total' => $paginateData['total'],
            'records' => $list,
        ];
    }
}