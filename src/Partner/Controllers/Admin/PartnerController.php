<?php

namespace Edwin404\Partner\Controllers\Admin;

use Edwin404\Admin\Cms\Field\FieldImage;
use Edwin404\Admin\Cms\Field\FieldSelect;
use Edwin404\Admin\Cms\Field\FieldText;
use Edwin404\Admin\Cms\Handle\BasicCms;
use Edwin404\Admin\Http\Controllers\Support\AdminCheckController;
use Edwin404\Partner\Services\PartnerService;
use Edwin404\Base\Support\Response;
use Illuminate\Support\Facades\Request;

class PartnerController extends AdminCheckController
{
    protected $cmsConfigBasic = [
        'model' => 'partner',
        'pageTitle' => '友情链接',
        'group' => 'data',
        'canAdd' => true,
        'canEdit' => true,
        'canDelete' => true,
        'fields' => [
            'position' => ['type' => FieldSelect::class, 'title' => '位置', 'list' => true, 'edit' => true, 'add' => true, 'desc' => '请保持默认', 'options' => [
                'pcHome' => 'PC首页',
            ]],
            'title' => ['type' => FieldText::class, 'title' => '标题', 'list' => true, 'edit' => true, 'add' => true,],
            'logo' => ['type' => FieldImage::class, 'title' => 'Logo', 'list' => true, 'edit' => true, 'add' => true,],
            'link' => ['type' => FieldText::class, 'title' => '链接', 'list' => true, 'edit' => true, 'add' => true, 'desc' => '网址',],
            'sort' => ['type' => FieldText::class, 'title' => '排序', 'list' => true, 'edit' => true, 'add' => true, 'desc' => '数字越小越靠前',],
        ]
    ];

    private $partnerService;

    public function __construct(PartnerService $partnerService)
    {
        parent::__construct();
        $this->partnerService = $partnerService;
        $this->setUpConfig();
    }

    protected function setUpConfig()
    {

    }

    public function dataPostAdd(&$data)
    {
        $this->partnerService->clearCache($data['position']);
    }

    public function dataPostEdit(&$data)
    {
        $this->partnerService->clearCache($data['position']);
    }

    public function dataPostDelete(&$data)
    {
        $this->partnerService->clearCache($data['position']);
    }

    public function dataList(BasicCms $basicCms)
    {
        return $basicCms->executeList($this, $this->cmsConfigBasic);;
    }

    public function dataDelete(BasicCms $basicCms)
    {
        if (Request::isMethod('post') && env('ADMIN_DEMO_USER_ID', 0) && $this->adminUserId() == env('ADMIN_DEMO_USER_ID', 0)) {
            return Response::send(-1, '演示账号禁止修改信息');
        }

        return $basicCms->executeDelete($this, $this->cmsConfigBasic);
    }

    public function dataEdit(BasicCms $basicCms)
    {
        if (Request::isMethod('post') && env('ADMIN_DEMO_USER_ID', 0) && $this->adminUserId() == env('ADMIN_DEMO_USER_ID', 0)) {
            return Response::send(-1, '演示账号禁止修改信息');
        }

        return $basicCms->executeEdit($this, $this->cmsConfigBasic);
    }

    public function dataAdd(BasicCms $basicCms)
    {
        if (Request::isMethod('post') && env('ADMIN_DEMO_USER_ID', 0) && $this->adminUserId() == env('ADMIN_DEMO_USER_ID', 0)) {
            return Response::send(-1, '演示账号禁止修改信息');
        }

        return $basicCms->executeAdd($this, $this->cmsConfigBasic);
    }


}