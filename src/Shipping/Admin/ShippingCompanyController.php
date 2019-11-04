<?php

namespace Edwin404\Shipping\Admin;

use Edwin404\Admin\Cms\Field\FieldSwitch;
use Edwin404\Admin\Cms\Field\FieldText;
use Edwin404\Admin\Cms\Handle\BasicCms;
use Edwin404\Admin\Http\Controllers\Support\AdminCheckController;

class ShippingCompanyController extends AdminCheckController
{
    protected $cmsConfigBasic = [
        'model' => 'shipping_company',
        'pageTitle' => '快递公司',
        'group' => 'data',
        'canAdd' => true,
        'canEdit' => true,
        'canDelete' => true,
        'fields' => [
            'code' => ['type' => FieldText::class, 'title' => '公司代码', 'list' => true, 'edit' => true, 'add' => true,],
            'name' => ['type' => FieldText::class, 'title' => '公司名称', 'list' => true, 'edit' => true, 'add' => true,],
            'active' => ['type' => FieldSwitch::class, 'title' => '激活', 'list' => true, 'edit' => true, 'add' => true, 'default' => true,],
            'sort' => ['type' => FieldText::class, 'title' => '排序', 'list' => true, 'edit' => true, 'add' => true, 'default' => '999'],
        ]
    ];

    public function dataList(BasicCms $basicCms)
    {
        return $basicCms->executeList($this, $this->cmsConfigBasic);;
    }

    public function dataDelete(BasicCms $basicCms)
    {
        return $basicCms->executeDelete($this, $this->cmsConfigBasic);
    }

    public function dataEdit(BasicCms $basicCms)
    {
        return $basicCms->executeEdit($this, $this->cmsConfigBasic);
    }

    public function dataAdd(BasicCms $basicCms)
    {
        return $basicCms->executeAdd($this, $this->cmsConfigBasic);
    }


}