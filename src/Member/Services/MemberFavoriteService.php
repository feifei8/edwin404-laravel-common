<?php

namespace Edwin404\Member\Services;


use Edwin404\Base\Support\ModelHelper;

class MemberFavoriteService
{

    public function add($userId, $category, $categoryId)
    {
        $m = ModelHelper::load('member_favorite', ['userId' => $userId, 'category' => $category, 'categoryId' => $categoryId]);
        if (empty($m)) {
            ModelHelper::add('member_favorite', [
                'userId' => $userId, 'category' => $category, 'categoryId' => $categoryId
            ]);
        }
    }

    public function delete($userId, $category, $categoryId)
    {
        ModelHelper::delete('member_favorite', ['userId' => $userId, 'category' => $category, 'categoryId' => $categoryId]);
    }

    public function exists($userId, $category, $categoryId)
    {
        return ModelHelper::exists('member_favorite', ['userId' => $userId, 'category' => $category, 'categoryId' => $categoryId]);
    }

    public function paginate($userId, $category, $page, $pageSize, $option = [])
    {
        $option['where']['userId'] = $userId;
        $option['where']['category'] = $category;
        return ModelHelper::modelPaginate('member_favorite', $page, $pageSize, $option);
    }

}