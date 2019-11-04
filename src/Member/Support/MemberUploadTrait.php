<?php

namespace Edwin404\Member\Support;

use Edwin404\Base\Support\Response;
use Edwin404\Member\Facades\MemberUploadFacade;
use Illuminate\Support\Facades\Input;

trait MemberUploadTrait
{
    public function temp($category = '')
    {
        $categoryInfo = config('data.upload.' . $category, null);
        if (empty($categoryInfo)) {
            return Response::send(-1, '错误的Category');
        }

        $file = Input::file('file');
        if (empty($file) || Input::get('chunks', null)) {
            return MemberUploadFacade::uploadTempHandle($this->memberUserId(), $category, Input::all());
        } else {
            // 单文件直接上传
            $input = [
                'file' => $file,
                'name' => $file->getClientOriginalName(),
                'type' => $file->getClientMimeType(),
                'lastModifiedDate' => 'no-modified-date',
                'size' => $file->getClientSize()
            ];
            return MemberUploadFacade::uploadTempHandle($this->memberUserId(), $category, $input);
        }
    }

    public function editor()
    {
        if (!$this->memberUserId()) {
            return Response::send(-1, '请您登录');
        }

        $category = 'image';

        $ret = [
            'state' => '',
            'url' => '',
            'title' => '',
            'original' => '',
            'type' => '',
            'size' => 0
        ];

        $file = Input::file('file');
        if (empty($file)) {
            return Response::send(-1, '上传文件为空');
        }

        $input = [
            'file' => $file,
            'name' => $file->getClientOriginalName(),
            'type' => $file->getClientMimeType(),
            'lastModifiedDate' => 'no-modified-date',
            'size' => $file->getClientSize()
        ];

        $uploadRet = MemberUploadFacade::uploadTempHandle($this->memberUserId(), $category, $input);
        if ($uploadRet['code']) {
            return $uploadRet;
        }

        $data = $uploadRet['data']['data'];
        $path = $uploadRet['data']['path'];

        $ret['state'] = 'SUCCESS';
        $ret['url'] = $path;
        $ret['title'] = $data->filename;
        $ret['original'] = $data->filename;
        $ret['type'] = '';
        $ret['size'] = $data->size;

        return json_encode($ret);
    }
}