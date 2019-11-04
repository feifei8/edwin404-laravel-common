<?php
namespace Edwin404\Article\Controllers\Admin;

use Edwin404\Admin\Cms\Field\FieldRichtext;
use Edwin404\Admin\Cms\Field\FieldText;
use Edwin404\Admin\Cms\Handle\CategoryCms;
use Edwin404\Admin\Http\Controllers\Support\AdminCheckController;

class ArticleBottomTreeController extends AdminCheckController
{
    private $cmsConfigData = [
        'model' => 'article_bottom_tree',
        'pageTitle' => '底部文章',
        'group' => 'data',
        'maxLevel' => 2,
        'canAdd' => true,
        'canEdit' => true,
        'canDelete' => true,
        'canView' => true,
        'canSort' => true,
        'fields' => [
            'title' => ['type' => FieldText::class, 'title' => '名称', 'list' => true, 'add' => true, 'edit' => true, 'view' => true],
            'content' => ['type' => FieldRichtext::class, 'title' => '名称', 'add' => true, 'edit' => true, 'view' => true],
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