<?php

namespace Edwin404\Data\Services;

use Edwin404\Base\Support\CurlHelper;
use Edwin404\Base\Support\FileHelper;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\Response;
use Edwin404\Config\Facades\ConfigFacade;
use Illuminate\Support\Str;

class DataService
{

    const DATA_TEMP = 'data_temp';
    const DATA = 'data';
    const DATA_CHUNK = 'data_chunk';

    const PATTERN_DATA_TEMP = '/^data_temp\\/([a-z_]+)\\/([a-zA-Z0-9]{32}\\.[a-z0-9]+)$/';
    const PATTERN_DATA = '/^data\\/([a-z_]+)\\/(\\d+\\/\\d+\\/\\d+\\/\\d+_[a-zA-Z0-9]{4}_\\d+\\.[a-z0-9]+)$/';

    static $UPLOAD_TIMESTAMP = null;

    private $dataStorageService;
    private $config;

    function __construct(DataStorageService $dataStorageService)
    {
        $this->dataStorageService = $dataStorageService;
        $this->config = config('data.upload', []);
    }

    protected function storeRemoteImage($imageUrl)
    {
        if (Str::startsWith($imageUrl, '//')) {
            $imageUrl = 'http:' . $imageUrl;
        }

        if (!(Str::startsWith($imageUrl, 'http://') || Str::startsWith($imageUrl, 'https://'))) {
            return Response::generate(-1, '图片路径不是完整URL,忽略 -> ' . $imageUrl);
        }

        $ext = FileHelper::extension($imageUrl);

        if (!in_array($ext, config('data.upload.image.extensions'))) {
            return Response::generate(-1, '图片格式不被允许');
        }

        $image = CurlHelper::getContent($imageUrl);
        if (empty($image)) {
            return Response::generate(-1, '图片抓取失败 -> ' . $imageUrl);
        }

        $category = 'image';
        $ret = $this->uploadToData($category, 'image.' . $ext, $image);
        if ($ret['code']) {
            return Response::generate(-1, '图片存取失败 -> ' . $imageUrl);
        }

        return Response::generate(0, null, [
            'path' => DataService::DATA . '/' . $category . '/' . $ret['data']['data']['path'],
            'data' => $ret['data']['data'],
        ]);
    }

    public function storeContentRemoteImages($content)
    {
        $images = [];
        preg_match_all('/(<img.*?)src="(.*?)"(.*?>)/i', $content, $mat);
        if (!empty($mat[2])) {
            $images = array_merge($images, $mat[2]);
        }
        preg_match_all('/(<img.*?)src=\'(.*?)\'(.*?>)/i', $content, $mat);
        if (!empty($mat[2])) {
            $images = array_merge($images, $mat[2]);
        }
        if (!empty($images)) {
            $imageMap = [];
            foreach ($images as $k => $oldImage) {
                if (empty($imageMap[$oldImage])) {

                    $imageMap[$oldImage] = $oldImage;

                    $ret = $this->storeRemoteImage($oldImage);
                    if ($ret['code']) {
                        continue;
                    }

                    $newImage = '/' . $ret['data']['path'];
                    $imageMap[$oldImage] = $newImage;

                }
            }

            foreach ($imageMap as $oldImage => $newImage) {
                $content = str_replace($oldImage, $newImage, $content);
            }

        }

        return $content;
    }

    // 将包含了临时上传文件的内容全部替换成正式文件
    public function storeContentTempPath($content)
    {
        if (!$content) {
            return $content;
        }
        preg_match_all('/(data_temp\\/([a-z_]+)\\/([a-zA-Z0-9]{32}\\.[a-z0-9]+))("|\')/', $content, $mat);
        $pathMap = [];

        if (!empty($mat[1])) {
            foreach ($mat[1] as $tempPath) {
                $pathMap[$tempPath] = '';
            }
        }
        if (!empty($pathMap)) {
            foreach ($pathMap as $tempPath => $empty) {
                $ret = $this->storeTempDataByPath($tempPath);
                if (!$ret['code']) {
                    $pathMap[$tempPath] = $ret['data']['path'];
                }
            }
            foreach ($pathMap as $tempPath => $path) {
                $content = str_replace($tempPath, $path, $content);
            }
        }
        return $content;
    }

    // 将包含了临时上传文件的内容全部替换成正式文件 并且 返回替换识别到的文件信息
    public function saveTempDataInContent($content)
    {
        if (!$content) {
            return [
                'map' => [],
                'content' => $content
            ];
        }
        preg_match_all('/(data_temp\\/([a-z_]+)\\/([a-zA-Z0-9]{32}\\.[a-z0-9]+))/', $content, $mat);
        $map = [];

        if (!empty($mat[1])) {
            foreach ($mat[1] as $tempPath) {
                $map[$tempPath] = '';
            }
        }
        if (!empty($map)) {
            foreach ($map as $tempPath => $empty) {
                $ret = $this->storeTempDataByPath($tempPath);
                if (!$ret['code']) {
                    $map[$tempPath] = $ret['data'];
                } else {
                    unset($map[$tempPath]);
                }
            }
            foreach ($map as $tempPath => $info) {
                $content = str_replace($tempPath, $info['path'], $content);
            }
        }
        // $map = [ [path=>data/xxx/xxx.xxx,data=>Data] ];
        return [
            'map' => $map,
            'content' => $content
        ];
    }

    /** 和 upload 方法的唯一区别是返回值不同 */
    public function uploadToData($category, $filename, $content)
    {
        if (empty($this->config[$category])) {
            return Response::generate(-1, '未知的分类:' . $category);
        }
        $config = $this->config[$category];

        if (strlen($filename) > 200) {
            return Response::generate(-2, '文件名太长，最多200字节');
        }

        $extension = FileHelper::extension($filename);
        if (!in_array($extension, $config['extensions'])) {
            return Response::generate(-3, '不允许的文件类型:' . $extension);
        }

        $size = strlen($content);
        if ($size > $config['maxSize']) {
            return Response::generate(-4, '文件大小超过上限:' . FileHelper::formatByte($config['maxSize']));
        }

        $updateTimestamp = time();
        if (self::$UPLOAD_TIMESTAMP) {
            $updateTimestamp = self::$UPLOAD_TIMESTAMP;
        }

        $retry = 0;
        do {
            $path = date('Y/m/d/', $updateTimestamp) . (time() % 86400) . '_' . strtolower(Str::random(4)) . '_' . mt_rand(1000, 9999) . '.' . $extension;
            $fullPath = self::DATA . '/' . $category . '/' . $path;
        } while ($retry++ < 10 && $this->dataStorageService->exists($fullPath));
        if ($retry >= 10) {
            return Response::generate(-2, '创建文件次数超时');
        }

        $this->dataStorageService->put($fullPath, $content);

        $data = ModelHelper::add('data', [
            'category' => $category,
            'path' => $path,
            'filename' => $filename,
            'size' => $size,
        ]);

        return Response::generate(0, 'ok', [
            'data' => $data,
            'path' => self::DATA . '/' . $data['category'] . '/' . $data['path']
        ]);
    }

    public function uploadToTempData($category, $filename, $content)
    {
        if (empty($this->config[$category])) {
            return Response::generate(-1, '未知的分类:' . $category);
        }
        $config = $this->config[$category];

        if (strlen($filename) > 200) {
            return Response::generate(-2, '文件名太长，最多200字节');
        }

        $extension = FileHelper::extension($filename);
        if (!in_array($extension, $config['extensions'])) {
            return Response::generate(-3, '不允许的文件类型:' . $extension);
        }

        $size = strlen($content);
        if ($size > $config['maxSize']) {
            return Response::generate(-4, '文件大小超过上限:' . FileHelper::formatByte($config['maxSize']));
        }

        $retry = 0;
        do {
            $path = strtolower(Str::random(32)) . '.' . $extension;
            $fullPath = self::DATA_TEMP . '/' . $category . '/' . $path;
        } while ($retry++ < 10 && $this->dataStorageService->exists($fullPath));
        if ($retry >= 10) {
            return Response::generate(-5, '上传失败，创建临时文件次数超时');
        }

        $this->dataStorageService->put($fullPath, $content);

        $m = ModelHelper::add('data_temp', [
            'category' => $category,
            'path' => $path,
            'filename' => $filename,
            'size' => $size,
        ]);

        return Response::generate(0, 'ok', $m);
    }

    public function storeTempDataByPath($tempPath, $option = [])
    {
        $option = $this->prepareOption($option);

        $tempPath = trim($tempPath, '/');
        if (preg_match(self::PATTERN_DATA_TEMP, $tempPath, $mat)) {
            return $this->storeTempData($mat[1], $mat[2], $option);
        }
        return Response::generate(-1, '错误的临时文件路径', null);
    }

    public function loadTempDataByPath($tempPath)
    {
        $tempPath = trim($tempPath, '/');
        if (preg_match(self::PATTERN_DATA_TEMP, $tempPath, $mat)) {
            return ModelHelper::load('data_temp', ['category' => $mat[1], 'path' => $mat[2]]);
        }
        return null;
    }

    public function deleteTempDataByPath($tempPath)
    {
        $tempPath = trim($tempPath, '/');
        if (preg_match(self::PATTERN_DATA_TEMP, $tempPath, $mat)) {
            $dataTemp = ModelHelper::load('data_temp', ['category' => $mat[1], 'path' => $mat[2]]);
            if (empty($dataTemp)) {
                return;
            }
            $this->dataStorageService->delete(self::DATA_TEMP . '/' . $dataTemp['category'] . '/' . $dataTemp['path']);
            ModelHelper::delete('data_temp', ['id' => $dataTemp['id']]);
        }
    }

    public function storeTempData($category, $tempDataPath, $option = [])
    {
        $option = $this->prepareOption($option);

        $dataTemp = ModelHelper::load('data_temp', ['category' => $category, 'path' => $tempDataPath]);
        if (empty($dataTemp)) {
            return Response::generate(-1, '临时文件不存在');
        }

        $extension = FileHelper::extension($dataTemp['filename']);

        $updateTimestamp = time();
        if (self::$UPLOAD_TIMESTAMP) {
            $updateTimestamp = self::$UPLOAD_TIMESTAMP;
        }

        $path = date('Y/m/d/', $updateTimestamp) . (time() % 86400) . '_' . strtolower(Str::random(4)) . '_' . mt_rand(1000, 9999) . '.' . $extension;
        $fullPath = self::DATA . '/' . $category . '/' . $path;

        $from = self::DATA_TEMP . '/' . $dataTemp['category'] . '/' . $dataTemp['path'];
        $to = self::DATA . '/' . $dataTemp['category'] . '/' . $path;

        if (!$this->dataStorageService->exists($from)) {
            ModelHelper::delete('data_temp', ['id' => $dataTemp['id']]);
            return Response::generate(-3, '临时文件不存在');
        }

        $this->dataStorageService->move($from, $to);

        $data = ModelHelper::add('data', [
            'category' => $dataTemp['category'],
            'path' => $path,
            'filename' => $dataTemp['filename'],
            'size' => $dataTemp['size'],
        ]);

        switch ($option['driver']) {
            case 'ossAliyun':
                ModelHelper::updateOne('data', ['id' => $data['id']], [
                    'driver' => $option['driver'],
                    'domain' => $option['domain'],
                ]);
                $data['driver'] = $option['driver'];
                $data['domain'] = $option['domain'];
                break;
        }

        ModelHelper::delete('data_temp', ['id' => $dataTemp['id']]);

        return Response::generate(0, 'ok', [
            'data' => $data,
            'path' => self::DATA . '/' . $data['category'] . '/' . $data['path']
        ]);
    }

    public function load($id)
    {
        return ModelHelper::load('data', ['id' => $id]);
    }

    public function loadByPath($path)
    {
        if (preg_match(self::PATTERN_DATA, $path, $mat)) {
            return ModelHelper::load('data', ['category' => $mat[1], 'path' => $mat[2]]);
        }
        return null;
    }

    /**
     * 根据ID删除文件（包括物理删除）
     * @param $id
     */
    public function deleteById($id, $option = [])
    {
        $data = ModelHelper::load('data', ['id' => $id]);
        if (empty($data)) {
            return;
        }
        $option = $this->prepareOption($option);
        $file = self::DATA . '/' . $data['category'] . '/' . $data['path'];
        $this->dataStorageService->delete($file);
        ModelHelper::delete('data', ['id' => $id]);
    }

    /**
     * 根据路径删除
     *
     * @param $path
     */
    public function deleteByPath($path)
    {
        if (preg_match(self::PATTERN_DATA, $path, $mat)) {
            $data = ModelHelper::load('data', ['category' => $mat[1], 'path' => $mat[2]]);
            if (empty($data)) {
                return;
            }
            $this->dataStorageService->delete($path);
            ModelHelper::delete('data', ['id' => $data['id']]);
        }
    }

    public function isDataPath($path)
    {
        return preg_match(self::PATTERN_DATA, $path);
    }

    public function isTempDataPath($path)
    {
        return preg_match(self::PATTERN_DATA_TEMP, $path);
    }

    public function getContentByPath($path)
    {
        return $this->dataStorageService->get($path);
    }

    /** 和 uploadToData 方法的唯一区别是返回值不同 */
    public function upload($category, $filename, $content)
    {
        if (empty($this->config[$category])) {
            return Response::generate(-1, '未知的分类:' . $category);
        }
        $config = $this->config[$category];

        if (empty($filename)) {
            return Response::generate(-2, '文件名为空');
        }
        if (strlen($filename) > 200) {
            return Response::generate(-3, '文件名太长，最多200字节');
        }

        $extension = FileHelper::extension($filename);
        if (!in_array($extension, $config['extensions'])) {
            return Response::generate(-4, '不允许的文件类型:' . $extension);
        }

        $size = strlen($content);
        if ($size == 0) {
            return Response::generate(-5, '上传文件为空');
        }
        if ($size > $config['maxSize']) {
            return Response::generate(-6, '文件大小超过上限:' . FileHelper::formatByte($config['maxSize']));
        }

        $updateTimestamp = time();
        if (self::$UPLOAD_TIMESTAMP) {
            $updateTimestamp = self::$UPLOAD_TIMESTAMP;
        }

        $retry = 0;
        do {
            $path = date('Y/m/d/', $updateTimestamp) . (time() % 86400) . '_' . strtolower(Str::random(4)) . '_' . mt_rand(1000, 9999) . '.' . $extension;
            $fullPath = self::DATA . '/' . $category . '/' . $path;
        } while ($retry++ < 10 && $this->dataStorageService->exists($fullPath));
        if ($retry >= 10) {
            return Response::generate(-7, '上传失败，创建文件次数超时');
        }

        $this->dataStorageService->put($fullPath, $content);

        $data = ModelHelper::add('data', [
            'category' => $category,
            'path' => $path,
            'filename' => $filename,
            'size' => $size,
        ]);

        return Response::generate(0, 'ok', $data);
    }

    public function uploadHandle($category, $input, $extra = [], $option = [])
    {
        $option = $this->prepareOption($option);

        $action = empty($input['action']) ? '' : $input['action'];

        $file = [];
        foreach (['name', 'type', 'lastModifiedDate', 'size'] as $k) {
            if (empty($input[$k])) {
                return Response::generate(-1, $k . '为空');
            }
            $file[$k] = $input[$k] . '';
        }
        $file = array_merge($file, $extra);

        if (empty($this->config[$category])) {
            return Response::generate(-2, '未知的分类:' . $category);
        }
        $config = $this->config[$category];

        if (strlen($file['name']) > 200) {
            return Response::generate(-3, '文件名太长，最多200字节');
        }

        $extension = FileHelper::extension($file['name']);
        if (!in_array($extension, $config['extensions'])) {
            return Response::generate(-4, '不允许的文件类型:' . $extension);
        }

        if ($file['size'] > $config['maxSize']) {
            return Response::generate(-5, '文件大小超过上限:' . FileHelper::formatByte($config['maxSize']));
        }

        switch ($action) {

            case 'init':
                return $this->dataStorageService->multiPartInit([
                    'category' => $category,
                    'file' => $file,
                ]);

            default:
                return $this->dataStorageService->multiPartUpload([
                    'category' => $category,
                    'file' => $file,
                    'input' => $input,
                ]);
        }

    }

    public function paginateData($category, $page, $pageSize)
    {
        $where = [];
        if ($category) {
            $where = ['category' => $category];
        }
        $data = ModelHelper::model('data')->orderBy('id', 'desc')->where($where)->paginate($pageSize, ['*'], 'page', $page)->toArray();
        return [
            'list' => $data['data'],
            'count' => $data['total']
        ];
    }

    public function getTempFullPath($path)
    {
        $option = $this->getOSSOption();
        switch ($option['driver']) {
            case 'ossAliyun':
                return $option['domain'] . '/' . $path;
        }
        return $path;
    }

    public function prepareTempDataForLocalUse($file)
    {
        $fileFullPath = $this->getTempFullPath($file);
        $localFile = FileHelper::savePathToLocal($fileFullPath, '.' . FileHelper::extension($file));
        if (!file_exists($localFile)) {
            return Response::generate(-1, '保存文件出错');
        }
        return Response::generate(0, null, [
            'path' => $localFile
        ]);
    }

    public function getOSSOption()
    {
        $option = [];
        $option['driver'] = ConfigFacade::get('uploadDriver', '');
        $option['domain'] = ConfigFacade::get('uploadDriverDomain', '');
        switch ($option['driver']) {
            case 'ossAliyun':
                $option['aliyunAccessKeyId'] = ConfigFacade::get('uploadDriverAliyunAccessKeyId', '');
                $option['aliyunAccessKeySecret'] = ConfigFacade::get('uploadDriverAliyunAccessKeySecret', '');
                $option['aliyunEndpoint'] = ConfigFacade::get('uploadDriverAliyunEndpoint', '');
                $option['aliyunBucket'] = ConfigFacade::get('uploadDriverAliyunBucket', '');
                break;
        }
        return $option;
    }

    private function prepareOption($option)
    {
        if (empty($option['driver'])) {
            $option['driver'] = $option;
        }
        switch ($option['driver']) {
            case 'ossAliyun':
                $this->dataStorageService = app(DataOSSAliyunStorageService::class);
                $this->dataStorageService->init($option);
                break;
        }
        return $option;
    }

}