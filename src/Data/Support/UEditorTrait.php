<?php

namespace Edwin404\Data\Support;


use Edwin404\Base\Support\CurlHelper;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Data\Facades\DataFacade;
use Edwin404\Data\Services\DataService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;

/**
 * Class UEditorTrait
 * @package Edwin404\Data\Support
 *
 * 使用 UEditorHandle 作为替代
 * @deprecated
 */
trait UEditorTrait
{
    private function ueditorBasicConfig()
    {
        $dataUploadConfig = config('data.upload', []);
        $config = [
            // 上传图片配置项
            "imageActionName" => "image",
            "imageFieldName" => "file",
            "imageMaxSize" => $dataUploadConfig['image']['maxSize'],
            "imageAllowFiles" => array_map(function ($v) {
                return '.' . $v;
            }, $dataUploadConfig['image']['extensions']),
            "imageCompressEnable" => true,
            "imageCompressBorder" => 5000,
            "imageInsertAlign" => "none",
            "imageUrlPrefix" => "",

            // [暂未开启] 涂鸦图片上传配置项
            "scrawlActionName" => "crawl",
            "scrawlFieldName" => "file",
            "scrawlMaxSize" => $dataUploadConfig['image']['maxSize'],
            "scrawlUrlPrefix" => "",
            "scrawlInsertAlign" => "none",

            // [暂未开启] 截图工具上传
            "snapscreenActionName" => "snap",
            "snapscreenUrlPrefix" => "",
            "snapscreenInsertAlign" => "none",

            // [暂未开启] 抓取
            "catcherLocalDomain" => ["127.0.0.1", "localhost"],
            "catcherActionName" => "catch",
            "catcherFieldName" => "source",
            "catcherUrlPrefix" => "",
            "catcherMaxSize" => $dataUploadConfig['image']['maxSize'],
            "catcherAllowFiles" => array_map(function ($v) {
                return '.' . $v;
            }, $dataUploadConfig['image']['extensions']),

            // 上传视频配置
            "videoActionName" => "video",
            "videoFieldName" => "file",
            "videoUrlPrefix" => "",
            "videoMaxSize" => $dataUploadConfig['video']['maxSize'],
            "videoAllowFiles" => array_map(function ($v) {
                return '.' . $v;
            }, $dataUploadConfig['video']['extensions']),

            // 上传文件配置
            "fileActionName" => "file",
            "fileFieldName" => "file",
            "fileUrlPrefix" => "",
            "fileMaxSize" => $dataUploadConfig['file']['maxSize'],
            "fileAllowFiles" => array_map(function ($v) {
                return '.' . $v;
            }, $dataUploadConfig['file']['extensions']),

            // 列出图片
            "imageManagerActionName" => "listImage",
            "imageManagerListSize" => 20,
            "imageManagerUrlPrefix" => "",
            "imageManagerInsertAlign" => "none",
            "imageManagerAllowFiles" => array_map(function ($v) {
                return '.' . $v;
            }, $dataUploadConfig['image']['extensions']),

            // 列出指定目录下的文件
            "fileManagerActionName" => "listFile",
            "fileManagerUrlPrefix" => "",
            "fileManagerListSize" => 20,
            "fileManagerAllowFiles" => array_map(function ($v) {
                return '.' . $v;
            }, $dataUploadConfig['file']['extensions'])

        ];
        return $config;
    }
    
    public function ueditorHandleForMemberUser()
    {
        $config = $this->ueditorBasicConfig();

        $action = Input::get('action', '');
        switch ($action) {
            case 'config':
                return Response::json($config);

            case 'catch':
                set_time_limit(0);
                $sret = array(
                    'state' => '',
                    'list' => null
                );
                $savelist = array();
                $flist = Input::get($config ['catcherFieldName'], []);
                if (empty ($flist)) {
                    $sret ['state'] = 'ERROR';
                } else {
                    $sret ['state'] = 'SUCCESS';
                    foreach ($flist as $f) {
                        if (preg_match('/^(http|ftp|https):\\/\\//i', $f)) {

                            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                            if (in_array('.' . $ext, $config ['catcherAllowFiles'])) {
                                if ($img = CurlHelper::getContent($f)) {
                                    $ret = DataFacade::uploadToTempData('image', '图片.' . $ext, $img);
                                    if (!$ret['code']) {
                                        $savelist [] = array(
                                            'state' => 'SUCCESS',
                                            'url' => '/' . DataService::DATA_TEMP . '/' . $ret['data']['category'] . '/' . $ret['data']['path'],
                                            'size' => strlen($img),
                                            'title' => '',
                                            'original' => '',
                                            'source' => htmlspecialchars($f)
                                        );
                                    } else {
                                        $ret ['state'] = 'Save remote file error!';
                                    }
                                } else {
                                    $ret ['state'] = 'Get remote file error';
                                }
                            } else {
                                $ret ['state'] = 'File ext not allowed';
                            }
                        } else {
                            $savelist [] = array(
                                'state' => 'not remote image',
                                'url' => '',
                                'size' => '',
                                'title' => '',
                                'original' => '',
                                'source' => htmlspecialchars($f)
                            );
                        }
                    }
                    $sret ['list'] = $savelist;
                }
                return Response::json($sret);


            case 'listImage':
            case 'listFile':

                $size = Input::get('size', 10);
                $start = Input::get('start', 0);
                $category = strtolower(substr($action, 4));
                $option = [];
                $option['order'] = ['id', 'desc'];
                $option['where']['userId'] = $this->memberUserId();
                $option['where']['category'] = $category;

                $list = [];
                $paginateData = ModelHelper::modelPaginate('member_upload', intval($start / $size) + 1, $size, $option);
                ModelHelper::modelJoin($paginateData['records'], 'dataId', '_data', 'data', 'id');
                foreach ($paginateData['records'] as &$r) {
                    $list [] = [
                        'url' => '/' . DataService::DATA . '/' . $r['category'] . '/' . $r['_data']['path'],
                        'mtime' => $r['created_at']
                    ];
                }

                return [
                    "state" => "SUCCESS",
                    "list" => $list,
                    "start" => $start,
                    "total" => $paginateData['total']
                ];

            case 'image':
            case 'video':
            case 'file':

                $category = $action;

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
                    $ret['state'] = '上传文件为空';
                    return $ret;
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
                    $ret['state'] = $uploadRet['msg'];
                    return $ret;
                }

                $data = $uploadRet['data']['data'];
                $path = $uploadRet['data']['path'];

                $ret['state'] = 'SUCCESS';
                $ret['url'] = '/' . $path;
                $ret['title'] = $data['filename'];
                $ret['original'] = $data['filename'];
                $ret['type'] = '';
                $ret['size'] = $data['size'];

                return $ret;
        }
    }

    public function ueditorHandle()
    {
        $config = $this->ueditorBasicConfig();

        $action = Input::get('action', '');
        switch ($action) {
            case 'config':
                return Response::json($config);

            case 'catch':

                set_time_limit(0);
                $sret = array(
                    'state' => '',
                    'list' => null
                );

                if (Session::get('_adminUserId', null) && env('ADMIN_DEMO_USER_ID', 0) && Session::get('_adminUserId', null) == env('ADMIN_DEMO_USER_ID', 0)) {
                    $sret ['state'] = 'ERROR';
                    return Response::json($sret);
                }

                $savelist = array();
                $flist = Input::get($config ['catcherFieldName'], []);
                if (empty ($flist)) {
                    $sret ['state'] = 'ERROR';
                } else {
                    $sret ['state'] = 'SUCCESS';
                    foreach ($flist as $f) {
                        if (preg_match('/^(http|ftp|https):\\/\\//i', $f)) {

                            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                            if (in_array('.' . $ext, $config ['catcherAllowFiles'])) {
                                if ($img = CurlHelper::getContent($f)) {
                                    $ret = DataFacade::uploadToTempData('image', '图片.' . $ext, $img);
                                    if (!$ret['code']) {
                                        $savelist [] = array(
                                            'state' => 'SUCCESS',
                                            'url' => '/' . DataService::DATA_TEMP . '/' . $ret['data']['category'] . '/' . $ret['data']['path'],
                                            'size' => strlen($img),
                                            'title' => '',
                                            'original' => '',
                                            'source' => htmlspecialchars($f)
                                        );
                                    } else {
                                        $ret ['state'] = 'Save remote file error!';
                                    }
                                } else {
                                    $ret ['state'] = 'Get remote file error';
                                }
                            } else {
                                $ret ['state'] = 'File ext not allowed';
                            }
                        } else {
                            $savelist [] = array(
                                'state' => 'not remote image',
                                'url' => '',
                                'size' => '',
                                'title' => '',
                                'original' => '',
                                'source' => htmlspecialchars($f)
                            );
                        }
                    }
                    $sret ['list'] = $savelist;
                }
                return Response::json($sret);


            case 'listImage':
            case 'listFile':
                $size = Input::get('size', 10);
                $start = Input::get('start', 0);

                $category = strtolower(substr($action, 4));

                $list = [];
                $paginate = DataFacade::paginateData($category, intval($start / $size) + 1, $size);
                foreach ($paginate['list'] as &$r) {
                    $list [] = [
                        'url' => '/' . DataService::DATA . '/' . $r['category'] . '/' . $r['path'],
                        'mtime' => $r['created_at']
                    ];
                }

                return [
                    "state" => "SUCCESS",
                    "list" => $list,
                    "start" => $start,
                    "total" => $paginate['count']
                ];

            case 'image':
            case 'video':
            case 'file':

                $category = $action;

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
                    $ret['state'] = '上传文件为空';
                    return $ret;
                }

                if (Session::get('_adminUserId', null) && env('ADMIN_DEMO_USER_ID', 0) && Session::get('_adminUserId', null) == env('ADMIN_DEMO_USER_ID', 0)) {
                    $sret ['state'] = 'ERROR';
                    return $ret;
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
                    $ret['state'] = $uploadRet['msg'];
                    return $ret;
                }

                $data = $uploadRet['data']['data'];
                $path = $uploadRet['data']['path'];

                $ret['state'] = 'SUCCESS';
                $ret['url'] = '/' . $path;
                $ret['title'] = $data['filename'];
                $ret['original'] = $data['filename'];
                $ret['type'] = '';
                $ret['size'] = $data['size'];

                return $ret;
        }
    }
}