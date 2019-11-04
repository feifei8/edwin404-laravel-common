<?php

namespace Edwin404\Article\Services;


use Edwin404\Base\Support\TreeHelper;

class ArticleBottomTreeService
{
    public function listAll()
    {
        $trees = TreeHelper::model2Nodes('article_bottom_tree', ['id' => 'id', 'title' => 'title']);
        return $trees;
    }
}