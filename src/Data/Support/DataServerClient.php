<?php

namespace Edwin404\Data\Support;


use Edwin404\Base\Support\FileHelper;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\Response;
use Edwin404\Data\Facades\DataFacade;
use Edwin404\Data\Services\DataService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Str;

class DataServerClient
{
    /**
     * 文件保存
     *  将客户端的文件存储在文件服务器端
     *  存储路径为 data/<category>/path
     *  存储内容为 content
     * @param string $cdn 如 http://www.example.com
     * @return mixed
     */
    public function serverPutHandle($cdn = null)
    {
        $key = Input::get('key');
        $category = Input::get('category');
        $path = Input::get('path');
        $content = Input::get('content');

        if (empty($key)) {
            return Response::json(-1, 'key empty');
        }
        if (empty($category)) {
            return Response::json(-1, 'category empty');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $category)) {
            return Response::json(-1, 'category format error');
        }
        if (empty($path)) {
            return Response::json(-1, 'path empty');
        }
        if (!preg_match('/^[a-zA-Z0-9_\\.\\/]+$/', $path)) {
            return Response::json(-1, 'path format error');
        }
        if (empty($content)) {
            return Response::json(-1, 'content empty');
        }

        $dataClient = ModelHelper::load('data_client', ['key' => $key]);
        if (empty($dataClient)) {
            return Response::json(-1, 'client not found');
        }

        $ext = FileHelper::extension($path);
        if (empty($ext)) {
            return Response::json(-1, 'get file extension error');
        };

        $path = DataService::DATA . '/' . $category . '/' . $path;
        $dirPath = FileHelper::dirPath($path);
        if (!file_exists($dirPath)) {
            @mkdir($dirPath, 0777, true);
        }
        file_put_contents($path, $content);

        if (!Str::endsWith($cdn, '/')) {
            $cdn .= '/';
        }

        $data = [];
        $data['path'] = $cdn . DataService::DATA . '/' . $category . '/' . $path;

        return Response::json(0, 'ok', $data);
    }

    /**
     * 文件上传
     *  将客户端的文件上传到文件服务器端
     *  存储路径为 data/<category>/xxx.<filename.extension>
     *  存储内容为 content
     * @param string $cdn 如 http://www.example.com
     * @return mixed
     */
    public function serverHandle($cdn = null)
    {
        $key = Input::get('key');
        $category = Input::get('category');
        $filename = Input::get('filename');
        $content = Input::get('content');

        if (empty($key)) {
            return Response::json(-1, 'key empty');
        }
        if (empty($category)) {
            return Response::json(-1, 'category empty');
        }
        if (empty($filename)) {
            return Response::json(-1, 'filename empty');
        }
        if (empty($content)) {
            return Response::json(-1, 'content empty');
        }

        $dataClient = ModelHelper::load('data_client', ['key' => $key]);
        if (empty($dataClient)) {
            return Response::json(-1, 'client not found');
        }

        $ret = DataFacade::uploadToData($category, $filename, $content);
        if ($ret['code']) {
            return Response::json(-1, $ret['msg']);
        }

        if (!Str::endsWith($cdn, '/')) {
            $cdn .= '/';
        }

        $data = [];
        $data['path'] = $cdn . $ret['data']['path'];
        $data['data'] = $ret['data']['data'];

        return Response::json(0, 'ok', $data);

    }

    /**
     * 将文件保存到文件服务器 可参考 serverPutHandle 方法
     * @param $server
     * @param $key
     * @param $category
     * @param $path
     * @param $content
     * @return array
     */
    public function clientPut($server, $key, $category, $path, $content)
    {
        $client = new \GuzzleHttp\Client();

        $data = [];
        $data['key'] = $key;
        $data['category'] = $category;
        $data['path'] = $path;
        $data['content'] = $content;

        $response = $client->post($server, [
            'form_params' => ``
        ]);
        $content = $response->getBody();

        $ret = @json_decode($content, true);
        if (!isset($ret['code'])) {
            return Response::generate(-1, 'put error', $ret);
        }

        return Response::generate($ret['code'], $ret['msg'], $ret['data']);
    }

    /**
     * 将文件上传到文件服务器 可参考 serverHandle 方法
     *
     * @param $server
     * @param $key
     * @param $category
     * @param $filename
     * @param $content
     * @return array
     */
    public function clientUpload($server, $key, $category, $filename, $content)
    {
        $client = new \GuzzleHttp\Client();

        $data = [];
        $data['key'] = $key;
        $data['category'] = $category;
        $data['filename'] = $filename;
        $data['content'] = $content;

        $response = $client->post($server, [
            'form_params' => $data
        ]);
        $content = $response->getBody();

        $ret = @json_decode($content, true);
        if (!isset($ret['code'])) {
            return Response::generate(-1, 'upload error', $ret);
        }

        return Response::generate($ret['code'], $ret['msg'], $ret['data']);
    }
}