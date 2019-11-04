<?php

namespace Edwin404\Article\Controllers\Admin;

use Edwin404\Admin\Cms\Field\FieldImage;
use Edwin404\Admin\Cms\Field\FieldRichtext;
use Edwin404\Admin\Cms\Field\FieldSelect;
use Edwin404\Admin\Cms\Field\FieldText;
use Edwin404\Admin\Cms\Field\FieldTextarea;
use Edwin404\Admin\Cms\Handle\BasicCms;
use Edwin404\Admin\Http\Controllers\Support\AdminCheckController;
use Edwin404\Article\Services\ArticleService;
use Edwin404\Banner\Services\BannerService;
use Edwin404\Base\Support\Response;
use Illuminate\Support\Facades\Request;

class ArticleController extends AdminCheckController
{
    protected $cmsConfigBasic = [
        'model' => 'article',
        'pageTitle' => '文章管理',
        'group' => 'data',
        'canAdd' => true,
        'canEdit' => true,
        'canDelete' => true,
        'fields' => [
            'position' => ['type' => FieldSelect::class, 'title' => '位置', 'list' => true, 'edit' => true, 'add' => true, 'desc' => '请保持默认', 'options' => [
                'footer' => '网站底部',
            ]],
            'title' => ['type' => FieldText::class, 'title' => '标题', 'list' => true, 'edit' => true, 'add' => true,],
            'content' => ['type' => FieldRichtext::class, 'title' => '内容', 'list' => true, 'edit' => true, 'add' => true,],
        ]
    ];

    private $articleService;

    public function __construct(ArticleService $articleService)
    {
        parent::__construct();
        $this->articleService = $articleService;
    }

    public function dataPostAdd(&$data)
    {
        $this->articleService->clearCache($data['position']);
    }

    public function dataPostEdit(&$data)
    {
        $this->articleService->clearCache($data['position']);
    }

    public function dataPostDelete(&$data)
    {
        $this->articleService->clearCache($data['position']);
    }

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