<?php

namespace Edwin404\Forum\Services;


use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\TreeHelper;

class ForumService
{
    static $categories = null;
    static $treeCategories = null;

    public function loadCategory($id)
    {
        if (null != self::$categories) {
            foreach (self::$categories as $category) {
                if ($category['id'] == $id) {
                    return $category;
                }
            }
        }
        return ModelHelper::load('forum_category', ['id' => $id]);
    }

    public function updateCategory($id, $data)
    {
        return ModelHelper::updateOne('forum_category', ['id' => $id], $data);
    }

    public function getCategories()
    {
        if (null != self::$categories) {
            return self::$categories;
        }
        $categories = ModelHelper::find('forum_category');
        self::$categories = $categories;
        return $categories;
    }

    public function getChildCategories($categoryId)
    {
        $childCategories = [];
        $categories = $this->getCategories();
        foreach ($categories as $category) {
            if ($category['pid'] == $categoryId) {
                $childCategories[] = $category;
            }
        }
        TreeHelper::arraySortByKey($childCategories, 'sort', 'asc');
        return $childCategories;
    }

    public function getTreeCategories()
    {
        if (null != self::$treeCategories) {
            return self::$treeCategories;
        }
        $categories = $this->getCategories();
        self::$treeCategories = TreeHelper::nodeMerge($categories, 0, 'id', 'pid', 'sort');
        return self::$treeCategories;
    }

    public function getCategoryChildIds($categoryId)
    {
        $categories = $this->getCategories();
        return TreeHelper::allChildIds($categories, $categoryId);
    }

    public function getCategoryTagMap($categoryId)
    {
        $map = [];
        $tags = ModelHelper::find('forum_category_tag', ['categoryId' => $categoryId], ['sort', 'asc']);
        foreach ($tags as $tag) {
            $map[$tag['id']] = $tag['title'];
        }
        return $map;
    }

    public function loadThread($id)
    {
        return ModelHelper::load('forum_thread', ['id' => $id]);
    }

    public function deleteThread($id)
    {
        $thread = $this->loadThread($id);
        if (empty($thread)) {
            return;
        }
        ModelHelper::delete('forum_thread', ['id' => $id]);
        ModelHelper::delete('forum_thread_member_data', ['threadId' => $id]);
        ModelHelper::delete('forum_post', ['threadId' => $id]);

        $this->updateCategory($thread['categoryId'], [
            'threadCount' => $this->getCategoryThreadCount($thread['categoryId']),
            'postCount' => $this->getCategoryPostCount($thread['categoryId'])
        ]);
    }

    public function addThread($data)
    {
        $thread = ModelHelper::add('forum_thread', $data);

        $this->updateCategory($thread['categoryId'], [
            'threadCount' => $this->getCategoryThreadCount($thread['categoryId']),
        ]);

        return $thread;
    }

    public function updateThread($id, $data)
    {
        return ModelHelper::updateOne('forum_thread', ['id' => $id], $data);
    }

    public function getLatestThread($limit)
    {
        $list = ModelHelper::model('forum_thread')->limit($limit)->orderBy('id', 'desc')->get()->toArray();
        return $list;
    }

    public function loadThreadMemberData($threadId, $memberUserId)
    {
        $m = ModelHelper::load('forum_thread_member_data', ['threadId' => $threadId, 'memberUserId' => $memberUserId]);
        if (empty($m)) {
            $m = ModelHelper::add('forum_thread_member_data', ['threadId' => $threadId, 'memberUserId' => $memberUserId]);
        }
        return $m;
    }

    public function paginateThreadsByCategory($categoryId, $page, $pageSize, $option)
    {
        $categoryIds = $this->getCategoryChildIds($categoryId);
        $categoryIds[] = $categoryId;
        $option['whereIn'] = ['categoryId', $categoryIds];
        return ModelHelper::modelPaginate('forum_thread', $page, $pageSize, $option);
    }

    public function paginateThreadsByMemberUserId($memberUserId, $page, $pageSize, $option)
    {
        $option['where']['memberUserId'] = $memberUserId;
        return ModelHelper::modelPaginate('forum_thread', $page, $pageSize, $option);
    }

    public function paginateMemberFavoriteThreads($memberUserId, $page, $pageSize, $option)
    {
        $option['where']['memberUserId'] = $memberUserId;
        $option['where']['fav'] = 1;
        $paginateData = ModelHelper::modelPaginate('forum_thread_member_data', $page, $pageSize, $option);
        ModelHelper::modelJoin($paginateData['records'], 'threadId', '_thread', 'forum_thread', 'id');
        $threads = [];
        foreach ($paginateData['records'] as $record) {
            if (empty($record['_thread'])) {
                continue;
            }
            $threads[] = $record['_thread'];
        }
        return [
            'records' => $threads,
            'total' => $paginateData['total'],
        ];
    }

    public function paginateMemberUpThreads($memberUserId, $page, $pageSize, $option)
    {
        $option['where']['memberUserId'] = $memberUserId;
        $option['where']['up'] = 1;
        $paginateData = ModelHelper::modelPaginate('forum_thread_member_data', $page, $pageSize, $option);
        ModelHelper::modelJoin($paginateData['records'], 'threadId', '_thread', 'forum_thread', 'id');
        $threads = [];
        foreach ($paginateData['records'] as $record) {
            if (empty($record['_thread'])) {
                continue;
            }
            $threads[] = $record['_thread'];
        }
        return [
            'records' => $threads,
            'total' => $paginateData['total'],
        ];
    }

    public function updateThreadMemberData($threadId, $memberUserId, $data)
    {
        return ModelHelper::updateOne('forum_thread_member_data', ['threadId' => $threadId, 'memberUserId' => $memberUserId], $data);
    }

    public function addPost($data)
    {
        $post = ModelHelper::add('forum_post', $data);

        $this->updateThread($post['threadId'], [
            'postCount' => $this->getThreadPostCount($post['threadId']),
        ]);
        $this->updateCategory($post['categoryId'], [
            'postCount' => $this->getCategoryPostCount($post['categoryId'])
        ]);

        return $post;
    }

    public function updatePost($id, $data)
    {
        $post = ModelHelper::updateOne('forum_post', ['id' => $id], $data);
        return $post;
    }

    public function loadPost($id)
    {
        return ModelHelper::load('forum_post', ['id' => $id]);
    }

    public function deletePost($id)
    {
        $post = ModelHelper::load('forum_post', ['id' => $id]);
        if (empty($post)) {
            return;
        }
        ModelHelper::delete('forum_post', ['id' => $id]);
        ModelHelper::update('forum_post', ['replyPostId' => $id], ['replyPostId' => 0]);

        $this->updateThread($post['threadId'], [
            'postCount' => $this->getThreadPostCount($post['threadId']),
        ]);
        $this->updateCategory($post['categoryId'], [
            'postCount' => $this->getCategoryPostCount($post['categoryId'])
        ]);

    }

    public function paginateThreadPost($threadId, $page, $pageSize, $option)
    {
        $option['where']['threadId'] = $threadId;
        return ModelHelper::modelPaginate('forum_post', $page, $pageSize, $option);
    }

    public function getPostPageInThread($postId, $threadId, $pageSize = 10)
    {
        $posts = ModelHelper::model('forum_post')->select('id')->where(['threadId' => $threadId])->orderBy('id', 'asc')->get();
        foreach ($posts as $i => &$post) {
            if ($postId == $post->id) {
                return ceil(($i + 1) / $pageSize);
            }
        }
        return 1;
    }

    public function getThreadPostCount($threadId)
    {
        return intval(ModelHelper::count('forum_post', ['threadId' => $threadId]));
    }

    public function getCategoryPostCount($categoryId)
    {
        $categoryIds = $this->getCategoryChildIds($categoryId);
        $categoryIds[] = $categoryId;
        return intval(ModelHelper::model('forum_post')->whereIn('categoryId', $categoryIds)->count());
    }

    public function getCategoryThreadCount($categoryId)
    {
        $categoryIds = $this->getCategoryChildIds($categoryId);
        $categoryIds[] = $categoryId;
        return intval(ModelHelper::model('forum_thread')->whereIn('categoryId', $categoryIds)->count());
    }

    public function getBanners()
    {
        return ModelHelper::find('forum_banner');
    }

    public function isCategoryAdmin($memberUserId, $categoryId)
    {
        $categories = $this->getCategories();
        $adminCategoryIds = ModelHelper::fieldValues('forum_category_admin', 'categoryId', ['memberUserId' => $memberUserId]);
        if (in_array($categoryId, $adminCategoryIds)) {
            return true;
        }
        $currentCategoryId = $categoryId;
        $limit = 0;
        while ($currentCategoryId && $limit++ < 999) {
            foreach ($categories as $category) {
                if ($category['id'] == $currentCategoryId) {
                    $currentCategoryId = $category['pid'];
                    if (empty($currentCategoryId)) {
                        return false;
                    }
                    if (in_array($currentCategoryId, $adminCategoryIds)) {
                        return true;
                    }
                    break;
                }
            }
        }
        return false;
    }

    public function getMemberThreadCount($memberUserId)
    {
        return ModelHelper::count('forum_thread', ['memberUserId' => $memberUserId]);
    }

    public function getMemberPostCount($memberUserId)
    {
        return ModelHelper::count('forum_post', ['memberUserId' => $memberUserId]);
    }

    public function paginateMemberThreads($memberUserId, $page, $pageSize, $option)
    {
        $option['where'] = ['memberUserId' => $memberUserId];
        return ModelHelper::modelPaginate('forum_thread', $page, $pageSize, $option);
    }

    public function paginateMemberPosts($memberUserId, $page, $pageSize, $option)
    {
        $option['where'] = ['memberUserId' => $memberUserId];
        return ModelHelper::modelPaginate('forum_post', $page, $pageSize, $option);
    }

}