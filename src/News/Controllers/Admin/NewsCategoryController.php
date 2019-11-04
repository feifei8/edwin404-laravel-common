<?php

namespace Edwin404\News\Controllers\Admin;


use Edwin404\Admin\Cms\Field\FieldAlias;
use Edwin404\Admin\Cms\Field\FieldCategory;
use Edwin404\Admin\Cms\Field\FieldRichtext;
use Edwin404\Admin\Cms\Field\FieldText;
use Edwin404\Admin\Cms\Handle\BasicCms;
use Edwin404\Admin\Cms\Handle\CategoryCms;
use Edwin404\Admin\Http\Controllers\Support\AdminCheckController;
use Edwin404\Base\Support\Response;
use Illuminate\Support\Facades\Request;

class NewsCategoryController extends AdminCheckController
{

    private $cmsConfigData = [
        'model' => 'news_category',
        'pageTitle' => '新闻分类',
        'group' => 'data',
        'maxLevel' => 1,
        'canAdd' => true,
        'canEdit' => true,
        'canDelete' => true,
        'canView' => false,
        'canSort' => true,
        'primaryKeyShow' => false,
        'fields' => [
            'name' => ['type' => FieldText::class, 'title' => '名称', 'list' => true, 'add' => true, 'edit' => true, 'view' => true],
        ]
    ];


    public function dataList(CategoryCms $categoryCms)
    {
        return $categoryCms->executeList($this, $this->cmsConfigData);
    }

    public function dataAdd(CategoryCms $categoryCms)
    {
        return $categoryCms->executeAdd($this, $this->cmsConfigData);
    }

    public function dataEdit(CategoryCms $categoryCms)
    {
        return $categoryCms->executeEdit($this, $this->cmsConfigData);
    }

    public function dataDelete(CategoryCms $categoryCms)
    {
        return $categoryCms->executeDelete($this, $this->cmsConfigData);
    }

    public function dataView(CategoryCms $categoryCms)
    {
        return $categoryCms->executeView($this, $this->cmsConfigData);
    }

    public function dataSort(CategoryCms $categoryCms)
    {
        return $categoryCms->executeSort($this, $this->cmsConfigData);
    }

}