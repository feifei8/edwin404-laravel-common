<?php

namespace Edwin404\Data\Support;


use Edwin404\Base\Support\Response;
use Edwin404\Data\Facades\DataFacade;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;

class DataUploadHandle
{
    public function temp($category = '', $option = [])
    {
        if (Session::get('_adminUserId', null) && env('ADMIN_DEMO_USER_ID', 0) && Session::get('_adminUserId', null) == env('ADMIN_DEMO_USER_ID', 0)) {
            return Response::send(-1, '演示账号禁止该操作');
        }

        $categoryInfo = config('data.upload.' . $category, null);
        if (empty($categoryInfo)) {
            return Response::send(-1, 'category error');
        }

        $file = Input::file('file');
        if (empty($file) || Input::get('chunks', null)) {
            $inputAll = Input::all();
            $inputAll['lastModifiedDate'] = 'no-modified-date';
            return DataFacade::uploadHandle($category, $inputAll, [], $option);
        } else {
            // 单文件直接上传
            $input = [
                'file' => $file,
                'name' => $file->getClientOriginalName(),
                'type' => $file->getClientMimeType(),
                'lastModifiedDate' => 'no-modified-date',
                'size' => $file->getClientSize()
            ];
            return DataFacade::uploadHandle($category, $input, [], $option);
        }
    }
}