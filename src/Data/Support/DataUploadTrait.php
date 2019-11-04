<?php

namespace Edwin404\Data\Support;

use Edwin404\Base\Support\Response;
use Edwin404\Data\Facades\DataFacade;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;

/**
 *
 * 使用 DataUploadHandle 作为替代
 * @deprecated
 */
trait DataUploadTrait
{
    /**
     * @return mixe
     *
     * @deprecated
     */
    public function editorTempDataUpload()
    {
        if (Session::get('_adminUserId', null) && env('ADMIN_DEMO_USER_ID', 0) && Session::get('_adminUserId', null) == env('ADMIN_DEMO_USER_ID', 0)) {
            return Response::send(-1, '演示账号禁止该操作');
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

        $uploadRet = DataFacade::uploadHandle($category, $input);
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