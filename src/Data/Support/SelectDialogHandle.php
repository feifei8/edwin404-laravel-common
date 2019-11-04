<?php

namespace Edwin404\Data\Support;


use Edwin404\Base\Support\FileHelper;
use Edwin404\Base\Support\ImageHelper;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\Response;
use Edwin404\Base\Support\TreeHelper;
use Edwin404\Config\Facades\ConfigFacade;
use Edwin404\Data\Facades\DataFacade;
use Edwin404\Data\Services\DataService;
use Edwin404\Data\Types\WatermarkType;
use Edwin404\Demo\Helpers\DemoHelper;
use EdwinFound\Utils\FileUtil;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;

class SelectDialogHandle
{
    private $dataService;
    private $dataServerClient;

    public function __construct(DataService $dataService,
                                DataServerClient $dataServerClient)
    {
        $this->dataService = $dataService;
        $this->dataServerClient = $dataServerClient;
    }

    public function executeServerMode($server, $key, $cdn)
    {
        exit('dev');
        if (Request::isMethod('post')) {
            switch (Input::get('action')) {
                case 'save':
                    $path = Input::get('path');
                    if (empty($path)) {
                        return Response::send(-1, 'path empty');
                    }
                    ImageHelper::limitSizeAndDetectOrientation(
                        $path,
                        config('data.upload.image.maxWidth', 9999),
                        config('data.upload.image.maxHeight', 9999)
                    );

                    $tempData = $this->dataService->loadTempDataByPath($path);
                    if (empty($tempData)) {
                        return Response::send(-1, 'path not found');
                    }

                    if (!file_exists($path)) {
                        return Response::send(-1, 'path file not exists');
                    }

                    $filename = $tempData['filename'];
                    $category = $tempData['category'];
                    $content = file_get_contents($path);

                    $ret = $this->dataServerClient->clientUpload($server, $key, $category, $filename, $content);
                    if ($ret['code']) {
                        return Response::send(-1, $ret['msg']);
                    }

                    $data = $ret['data']['data'];
                    ModelHelper::add('admin_upload', ['category' => $data['category'], 'dataId' => $data['id'], 'adminUploadCategoryId' => 0,]);

                    $this->dataService->deleteTempDataByPath($path);

                    return Response::json(0, null);
                case 'init':
                    return $this->dataService->uploadHandle('image', Input::all());

                case 'categoryDelete':
                    $id = intval(Input::get('id'));
                    $category = ModelHelper::load('admin_upload_category', ['id' => $id]);
                    if (empty($category)) {
                        return Response::send(-1, '分类不存在');
                    }

                    $adminUploadCategories = ModelHelper::find('admin_upload_category');
                    $childIds = TreeHelper::allChildIds($adminUploadCategories, $id);
                    $childIds[] = $id;

                    foreach ($childIds as $childId) {
                        ModelHelper::update('admin_upload', ['adminUploadCategoryId' => $childId], ['adminUploadCategoryId' => 0]);
                    }
                    foreach ($childIds as $childId) {
                        ModelHelper::delete('admin_upload_category', ['id' => $childId]);
                    }

                    return Response::send(0, null);

                case 'categoryEdit':

                    $id = intval(Input::get('id'));
                    $pid = intval(Input::get('pid'));
                    $title = trim(Input::get('title'));

                    if (empty($title)) {
                        return Response::send(-1, '名称为空');
                    }

                    if ($id) {
                        $category = ModelHelper::load('admin_upload_category', ['id' => $id]);
                        if (empty($category)) {
                            return Response::send(-1, '分类不存在');
                        }
                        if (!TreeHelper::modelNodeChangeAble('admin_upload_category', $id, $category['pid'], $pid)) {
                            return Response::send(-1, '分类父分类不能这样修改');
                        }
                        ModelHelper::updateOne('admin_upload_category', ['id' => $id], [
                            'pid' => $pid,
                            'sort' => null,
                            'title' => $title,
                        ]);
                    } else {
                        ModelHelper::add('admin_upload_category', [
                            'category' => 'image',
                            'pid' => $pid,
                            'sort' => null,
                            'title' => $title,
                        ]);
                    }

                    return Response::send(0, null);

                case 'category':

                    $adminUploadCategories = ModelHelper::find('admin_upload_category');
                    $categories = [];
                    foreach ($adminUploadCategories as $adminUploadCategory) {
                        $categories[] = [
                            'name' => $adminUploadCategory['title'],
                            'spread' => true,
                            'id' => $adminUploadCategory['id'],
                            'pid' => $adminUploadCategory['pid'],
                            'sort' => $adminUploadCategory['sort'],
                            'href' => '#category-' . $adminUploadCategory['id'],
                        ];
                    }
                    TreeHelper::setChildKey('children');
                    $categoryNodes = TreeHelper::nodeMerge($categories);

                    $nodes = [
                        [
                            'name' => '已归类',
                            'spread' => true,
                            'children' => $categoryNodes,
                            'id' => 0,
                            'href' => '#category-0',
                        ],
                        [
                            'name' => '未归类',
                            'spread' => true,
                            'children' => [],
                            'id' => -1,
                            'href' => '#category--1',
                        ]
                    ];

                    $nodeList = TreeHelper::listIndent($nodes, 'id', 'name');

                    return Response::json(0, null, compact('nodes', 'nodeList'));

                case 'imageDelete':

                    $ids = [];
                    foreach (explode(',', trim(Input::get('id', ''))) as $id) {
                        $id = intval($id);
                        if (empty($id)) {
                            continue;
                        }
                        $ids[] = $id;
                    }

                    foreach ($ids as $id) {
                        $adminUpload = ModelHelper::load('admin_upload', ['id' => $id]);
                        if (empty($adminUpload)) {
                            continue;
                        }
                        $this->dataService->deleteById($adminUpload['dataId']);
                        ModelHelper::delete('admin_upload', ['id' => $id]);
                    }

                    return Response::json(0, null);

                case 'imageEdit':

                    $ids = [];
                    foreach (explode(',', trim(Input::get('id', ''))) as $id) {
                        $id = intval($id);
                        if (empty($id)) {
                            continue;
                        }
                        $ids[] = $id;
                    }

                    $categoryId = intval(Input::get('categoryId'));

                    foreach ($ids as $id) {
                        ModelHelper::updateOne('admin_upload', ['id' => $id], ['adminUploadCategoryId' => $categoryId]);
                    }

                    return Response::json(0, null);

                case 'list':

                    $page = intval(Input::get('page', 1));
                    if ($page < 1) {
                        $page = 1;
                    }
                    $pageSize = 10;
                    $option = [];
                    $option['order'] = ['id', 'desc'];
                    $option['where'] = ['category' => 'image'];

                    $categoryId = intval(Input::get('categoryId'));
                    if ($categoryId > 0) {
                        $adminUploadCategories = ModelHelper::find('admin_upload_category');
                        $childIds = TreeHelper::allChildIds($adminUploadCategories, $categoryId);
                        $childIds[] = $categoryId;
                        $option['whereIn'] = ['adminUploadCategoryId', $childIds];
                    } else if ($categoryId == 0) {
                        $option['whereOperate'] = ['adminUploadCategoryId', '>', 0];
                    } else if ($categoryId == -1) {
                        $option['where'] = ['adminUploadCategoryId' => 0];
                    }

                    $paginateData = ModelHelper::modelPaginate('admin_upload', $page, $pageSize, $option);
                    ModelHelper::modelJoin($paginateData['records'], 'dataId', '_data', 'data', 'id');

                    $list = [];
                    foreach ($paginateData['records'] as $record) {
                        $item = [];
                        $item['id'] = $record['id'];
                        $item['path'] = $cdn . '/' . DataService::DATA . '/' . $record['_data']['category'] . '/' . $record['_data']['path'];
                        $item['filename'] = htmlspecialchars($record['_data']['filename']);
                        $list[] = $item;
                    }

                    $data = [];
                    $data['total'] = $paginateData['total'];
                    $data['list'] = $list;
                    $data['pageSize'] = $pageSize;
                    return Response::json(0, null, $data);

                default:

                    return $this->dataService->uploadHandle('image', Input::all());

            }
        }
        return view('common::data.imageSelectDialog');
    }

    public function executeCustom($category, $uploadTable, $uploadCategoryTable, $userId, $option = [])
    {
        if (Request::isMethod('post')) {
            switch (Input::get('action')) {
                case 'save':

                    $path = Input::get('path');
                    if (empty($path)) {
                        return Response::send(-1, 'path empty');
                    }
                    $ret = $this->dataService->storeTempDataByPath($path);
                    if ($ret['code']) {
                        return Response::json(-1, $ret['msg']);
                    }

                    if ($category == 'image') {
                        ImageHelper::limitSizeAndDetectOrientation(
                            $ret['data']['path'],
                            config('data.upload.image.maxWidth', 9999),
                            config('data.upload.image.maxHeight', 9999)
                        );
                    }

                    $categoryId = intval(Input::get('categoryId'));
                    if ($categoryId <= 0) {
                        $categoryId = 0;
                    }

                    $data = $ret['data']['data'];
                    ModelHelper::add($uploadTable,
                        [
                            'userId' => $userId,
                            'category' => $data['category'],
                            'dataId' => $data['id'],
                            'uploadCategoryId' => $categoryId,
                        ]
                    );

                    return Response::json(0, null);
                case 'init':

                    return $this->dataService->uploadHandle($category, Input::all());

                case 'categoryDelete':

                    $id = intval(Input::get('id'));
                    $category = ModelHelper::load($uploadCategoryTable, ['id' => $id]);
                    if (empty($category)) {
                        return Response::send(-1, '分类不存在');
                    }

                    $uploadCategories = ModelHelper::find($uploadCategoryTable, ['userId' => $userId,]);
                    $childIds = TreeHelper::allChildIds($uploadCategories, $id);
                    $childIds[] = $id;

                    foreach ($childIds as $childId) {
                        ModelHelper::update($uploadTable, ['userId' => $userId, 'uploadCategoryId' => $childId], ['uploadCategoryId' => 0]);
                    }
                    foreach ($childIds as $childId) {
                        ModelHelper::delete($uploadCategoryTable, ['userId' => $userId, 'id' => $childId]);
                    }

                    return Response::send(0, null);

                case 'categoryEdit':

                    $id = intval(Input::get('id'));
                    $pid = intval(Input::get('pid'));
                    $title = trim(Input::get('title'));

                    if (empty($title)) {
                        return Response::send(-1, '名称为空');
                    }

                    if ($id) {
                        $category = ModelHelper::load($uploadCategoryTable, ['id' => $id, 'userId' => $userId,]);
                        if (empty($category)) {
                            return Response::send(-1, '分类不存在');
                        }
                        if (!TreeHelper::modelNodeChangeAble($uploadCategoryTable, $id, $category['pid'], $pid)) {
                            return Response::send(-1, '分类父分类不能这样修改');
                        }
                        ModelHelper::updateOne($uploadCategoryTable, ['id' => $id, 'userId' => $userId,], [
                            'pid' => $pid,
                            'sort' => null,
                            'title' => $title,
                        ]);
                    } else {
                        ModelHelper::add($uploadCategoryTable, [
                            'userId' => $userId,
                            'category' => $category,
                            'pid' => $pid,
                            'sort' => null,
                            'title' => $title,
                        ]);
                    }

                    return Response::send(0, null);

                case 'category':

                    $uploadCategories = ModelHelper::find($uploadCategoryTable, ['userId' => $userId, 'category' => $category]);
                    $categories = [];
                    foreach ($uploadCategories as $uploadCategory) {
                        $categories[] = [
                            'name' => $uploadCategory['title'],
                            'spread' => false,
                            'id' => $uploadCategory['id'],
                            'pid' => $uploadCategory['pid'],
                            'sort' => $uploadCategory['sort'],
                            'href' => '#category-' . $uploadCategory['id'],
                        ];
                    }
                    TreeHelper::setChildKey('children');
                    $categoryNodes = TreeHelper::nodeMerge($categories);

                    $nodes = [
                        [
                            'name' => '已归类',
                            'spread' => true,
                            'children' => $categoryNodes,
                            'id' => 0,
                            'href' => '#category-0',
                        ],
                        [
                            'name' => '未归类',
                            'spread' => true,
                            'children' => [],
                            'id' => -1,
                            'href' => '#category--1',
                        ]
                    ];

                    $nodeList = TreeHelper::listIndent($nodes, 'id', 'name');

                    return Response::json(0, null, compact('nodes', 'nodeList'));

                case 'imageDelete':

                    $ids = [];
                    foreach (explode(',', trim(Input::get('id', ''))) as $id) {
                        $id = intval($id);
                        if (empty($id)) {
                            continue;
                        }
                        $ids[] = $id;
                    }

                    foreach ($ids as $id) {
                        $adminUpload = ModelHelper::load($uploadTable, ['id' => $id, 'userId' => $userId,]);
                        if (empty($adminUpload)) {
                            continue;
                        }
                        $this->dataService->deleteById($adminUpload['dataId']);
                        ModelHelper::delete($uploadTable, ['id' => $id, 'userId' => $userId,]);
                    }

                    return Response::json(0, null);

                case 'imageEdit':

                    $ids = [];
                    foreach (explode(',', trim(Input::get('id', ''))) as $id) {
                        $id = intval($id);
                        if (empty($id)) {
                            continue;
                        }
                        $ids[] = $id;
                    }

                    $categoryId = intval(Input::get('categoryId'));

                    foreach ($ids as $id) {
                        ModelHelper::updateOne($uploadTable, ['id' => $id, 'userId' => $userId,], ['uploadCategoryId' => $categoryId]);
                    }

                    return Response::json(0, null);

                case 'list':

                    $page = intval(Input::get('page', 1));
                    if ($page < 1) {
                        $page = 1;
                    }
                    $pageSize = 20;
                    $option = [];
                    $option['order'] = ['id', 'desc'];
                    $option['where'] = ['userId' => $userId, 'category' => $category];

                    $categoryId = intval(Input::get('categoryId'));
                    if ($categoryId > 0) {
                        $uploadCategories = ModelHelper::find($uploadCategoryTable, ['userId' => $userId,]);
                        $childIds = TreeHelper::allChildIds($uploadCategories, $categoryId);
                        $childIds[] = $categoryId;
                        $option['whereIn'] = ['uploadCategoryId', $childIds];
                    } else if ($categoryId == 0) {
                        $option['whereOperate'] = ['uploadCategoryId', '>', 0];
                    } else if ($categoryId == -1) {
                        $option['where']['uploadCategoryId'] = 0;
                    }

                    $paginateData = ModelHelper::modelPaginate($uploadTable, $page, $pageSize, $option);
                    ModelHelper::modelJoin($paginateData['records'], 'dataId', '_data', 'data', 'id');

                    $list = [];
                    foreach ($paginateData['records'] as $record) {
                        $item = [];
                        $item['id'] = $record['id'];
                        $item['path'] = '/' . DataService::DATA . '/' . $record['_data']['category'] . '/' . $record['_data']['path'];
                        $item['filename'] = htmlspecialchars($record['_data']['filename']);
                        $item['type'] = FileHelper::extension($record['_data']['path']);
                        $item['category'] = $category;
                        $list[] = $item;
                    }

                    $data = [];
                    $data['total'] = $paginateData['total'];
                    $data['list'] = $list;
                    $data['pageSize'] = $pageSize;
                    return Response::json(0, null, $data);

                default:

                    return $this->dataService->uploadHandle($category, Input::all());
            }
        }
        return view('common::data.selectDialog', [
            'category' => $category,
        ]);
    }

    public function execute($category, $option = [])
    {
        if (Request::isMethod('post')) {
            switch (Input::get('action')) {
                case 'save':

                    if (DemoHelper::shouldDenyAdminDemo()) {
                        return Response::send(-1, '演示账号禁止该操作');
                    }

                    $path = Input::get('path');
                    if (empty($path)) {
                        return Response::send(-1, 'path empty');
                    }
                    $ret = $this->dataService->storeTempDataByPath($path, $option);
                    if ($ret['code']) {
                        return Response::json(-1, $ret['msg']);
                    }

                    if ($category == 'image') {
                        if (file_exists($ret['data']['path'])) {
                            ImageHelper::limitSizeAndDetectOrientation(
                                $ret['data']['path'],
                                config('data.upload.image.maxWidth', 9999),
                                config('data.upload.image.maxHeight', 9999)
                            );
                        }
                    }

                    $categoryId = intval(Input::get('categoryId'));
                    if ($categoryId <= 0) {
                        $categoryId = 0;
                    }

                    $data = $ret['data']['data'];
                    ModelHelper::add('admin_upload', ['category' => $data['category'], 'dataId' => $data['id'], 'adminUploadCategoryId' => $categoryId,]);

                    return Response::json(0, null);

                case 'categoryDelete':


                    $id = intval(Input::get('id'));
                    $category = ModelHelper::load('admin_upload_category', ['id' => $id]);
                    if (empty($category)) {
                        return Response::send(-1, '分类不存在');
                    }

                    $adminUploadCategories = ModelHelper::find('admin_upload_category');
                    $childIds = TreeHelper::allChildIds($adminUploadCategories, $id);
                    $childIds[] = $id;

                    foreach ($childIds as $childId) {
                        ModelHelper::update('admin_upload', ['adminUploadCategoryId' => $childId], ['adminUploadCategoryId' => 0]);
                    }
                    foreach ($childIds as $childId) {
                        ModelHelper::delete('admin_upload_category', ['id' => $childId]);
                    }

                    return Response::send(0, null);

                case 'categoryEdit':

                    $id = intval(Input::get('id'));
                    $pid = intval(Input::get('pid'));
                    $title = trim(Input::get('title'));

                    if (empty($title)) {
                        return Response::send(-1, '名称为空');
                    }

                    if ($id) {
                        $category = ModelHelper::load('admin_upload_category', ['id' => $id]);
                        if (empty($category)) {
                            return Response::send(-1, '分类不存在');
                        }
                        if (!TreeHelper::modelNodeChangeAble('admin_upload_category', $id, $category['pid'], $pid)) {
                            return Response::send(-1, '分类父分类不能这样修改');
                        }
                        ModelHelper::updateOne('admin_upload_category', ['id' => $id], [
                            'pid' => $pid,
                            'sort' => null,
                            'title' => $title,
                        ]);
                    } else {
                        ModelHelper::add('admin_upload_category', [
                            'category' => $category,
                            'pid' => $pid,
                            'sort' => null,
                            'title' => $title,
                        ]);
                    }

                    return Response::send(0, null);

                case 'category':

                    $adminUploadCategories = ModelHelper::find('admin_upload_category', ['category' => $category]);
                    $categories = [];
                    foreach ($adminUploadCategories as $adminUploadCategory) {
                        $categories[] = [
                            'name' => $adminUploadCategory['title'],
                            'spread' => false,
                            'id' => $adminUploadCategory['id'],
                            'pid' => $adminUploadCategory['pid'],
                            'sort' => $adminUploadCategory['sort'],
                            'href' => '#category-' . $adminUploadCategory['id'],
                        ];
                    }
                    TreeHelper::setChildKey('children');
                    $categoryNodes = TreeHelper::nodeMerge($categories);

                    $nodes = [
                        [
                            'name' => '已归类',
                            'spread' => true,
                            'children' => $categoryNodes,
                            'id' => 0,
                            'href' => '#category-0',
                        ],
                        [
                            'name' => '未归类',
                            'spread' => true,
                            'children' => [],
                            'id' => -1,
                            'href' => '#category--1',
                        ]
                    ];

                    $nodeList = TreeHelper::listIndent($nodes, 'id', 'name');

                    return Response::json(0, null, compact('nodes', 'nodeList'));

                case 'imageDelete':

                    $ids = [];
                    foreach (explode(',', trim(Input::get('id', ''))) as $id) {
                        $id = intval($id);
                        if (empty($id)) {
                            continue;
                        }
                        $ids[] = $id;
                    }

                    foreach ($ids as $id) {
                        $adminUpload = ModelHelper::load('admin_upload', ['id' => $id]);
                        if (empty($adminUpload)) {
                            continue;
                        }
                        $this->dataService->deleteById($adminUpload['dataId'], $option);
                        ModelHelper::delete('admin_upload', ['id' => $id]);
                    }

                    return Response::json(0, null);

                case 'imageEdit':

                    $ids = [];
                    foreach (explode(',', trim(Input::get('id', ''))) as $id) {
                        $id = intval($id);
                        if (empty($id)) {
                            continue;
                        }
                        $ids[] = $id;
                    }

                    $categoryId = intval(Input::get('categoryId'));

                    foreach ($ids as $id) {
                        ModelHelper::updateOne('admin_upload', ['id' => $id], ['adminUploadCategoryId' => $categoryId]);
                    }

                    return Response::json(0, null);

                case 'list':

                    $page = intval(Input::get('page', 1));
                    if ($page < 1) {
                        $page = 1;
                    }
                    $pageSize = 20;
                    $option = [];
                    $option['order'] = ['id', 'desc'];
                    $option['where'] = ['category' => $category];

                    $categoryId = intval(Input::get('categoryId'));
                    if ($categoryId > 0) {
                        $adminUploadCategories = ModelHelper::find('admin_upload_category');
                        $childIds = TreeHelper::allChildIds($adminUploadCategories, $categoryId);
                        $childIds[] = $categoryId;
                        $option['whereIn'] = ['adminUploadCategoryId', $childIds];
                    } else if ($categoryId == 0) {
                        $option['whereOperate'] = ['adminUploadCategoryId', '>', 0];
                    } else if ($categoryId == -1) {
                        $option['where']['adminUploadCategoryId'] = 0;
                    }

                    $paginateData = ModelHelper::modelPaginate('admin_upload', $page, $pageSize, $option);
                    ModelHelper::modelJoin($paginateData['records'], 'dataId', '_data', 'data', 'id');

                    $list = [];
                    foreach ($paginateData['records'] as $record) {
                        $item = [];
                        $item['id'] = $record['id'];
                        $item['path'] = '/' . DataService::DATA . '/' . $record['_data']['category'] . '/' . $record['_data']['path'];
                        if (!empty($record['_data']['domain'])) {
                            $item['path'] = $record['_data']['domain'] . $item['path'];
                        }
                        $item['filename'] = htmlspecialchars($record['_data']['filename']);
                        $item['type'] = FileHelper::extension($record['_data']['path']);
                        $item['category'] = $category;
                        $list[] = $item;
                    }

                    $data = [];
                    $data['total'] = $paginateData['total'];
                    $data['list'] = $list;
                    $data['pageSize'] = $pageSize;
                    return Response::json(0, null, $data);

                case 'init':
                    if (DemoHelper::shouldDenyAdminDemo()) {
                        return Response::send(-1, '演示账号禁止该操作');
                    }
                    return $this->dataService->uploadHandle($category, Input::all(), [], $option);

                default:
                    if (DemoHelper::shouldDenyAdminDemo()) {
                        return Response::send(-1, '演示账号禁止该操作');
                    }
                    return $this->dataService->uploadHandle($category, Input::all(), [], $option);
            }
        }
        return view('common::data.selectDialog', [
            'category' => $category,
        ]);
    }

    public function executeForMemberUser($memberUserId, $category)
    {
        if (Request::isMethod('post')) {
            switch (Input::get('action')) {
                case 'save':
                    $path = Input::get('path');
                    if (empty($path)) {
                        return Response::send(-1, 'path empty');
                    }
                    $ret = $this->dataService->storeTempDataByPath($path);
                    if ($ret['code']) {
                        return Response::json(-1, $ret['msg']);
                    }

                    $data = $ret['data']['data'];
                    ModelHelper::add('member_upload', ['userId' => $memberUserId, 'category' => $data['category'], 'dataId' => $data['id']]);
                    $retData = [];
                    $retData['path'] = '/' . DataService::DATA . '/' . $data['category'] . '/' . $data['path'];
                    return Response::json(0, null, $retData);
                case 'init':
                    return $this->dataService->uploadHandle('audio', Input::all());
                default:
                    $file = Input::file('file');
                    if (empty($file) || Input::get('chunks', null)) {
                        if (Input::get('id')) {
                            return $this->dataService->uploadHandle($category, Input::all());
                        } else {

                            $page = intval(Input::get('page', 1));
                            if ($page < 1) {
                                $page = 1;
                            }
                            $pageSize = intval(Input::get('pageSize', 10));
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
                                $item['path'] = '/' . DataService::DATA . '/' . $record['_data']['category'] . '/' . $record['_data']['path'];
                                $item['filename'] = htmlspecialchars($record['_data']['filename']);
                                $list[] = $item;
                            }

                            $data = [];
                            $data['total'] = $paginateData['total'];
                            $data['list'] = $list;
                            return Response::json(0, null, $data);

                        }
                    } else {
                        // 单文件直接上传
                        $file = Input::file('file');
                        $input = [
                            'file' => $file,
                            'name' => $file->getClientOriginalName(),
                            'type' => $file->getClientMimeType(),
                            'lastModifiedDate' => 'no-modified-date',
                            'size' => $file->getClientSize()
                        ];
                        $ret = $this->dataService->uploadHandle($category, $input);
                        if ($ret['code']) {
                            return Response::json(-1, $ret['msg']);
                        }
                        $path = $ret['data']['path'];
                        $ret = $this->dataService->storeTempDataByPath($path);
                        if ($ret['code']) {
                            return Response::json(-1, $ret['msg']);
                        }
                        $data = $ret['data']['data'];
                        ModelHelper::add('member_upload', ['userId' => $memberUserId, 'category' => $data['category'], 'dataId' => $data['id']]);
                        $retData = [];
                        $retData['path'] = '/' . DataService::DATA . '/' . $data['category'] . '/' . $data['path'];
                        return Response::json(0, null, $retData);
                    }
            }
        }
        exit('dev');
        return view('common::data.selectDialog', [
            'category' => $category,
        ]);
    }
}