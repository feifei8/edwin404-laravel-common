<?php

namespace Edwin404\Forum\Traits;

use Carbon\Carbon;
use Edwin404\Base\Support\HtmlHelper;
use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\PageHelper;
use Edwin404\Base\Support\RequestHelper;
use Edwin404\Base\Support\Response;
use Edwin404\Forum\Services\ForumService;
use Edwin404\Member\Services\MemberService;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;

trait SimpleForumTrait
{
    public function index(ForumService $forumService,
                          $categoryId = 0)
    {
        $categoryId = intval($categoryId);

        $page = Input::get('page', 1);
        $pageSize = 10;
        $option = [];
        $option['order'] = [['isTop', 'desc'], ['updated_at', 'desc']];

        $paginateData = $forumService->paginateThreadsByCategory($categoryId, $page, $pageSize, $option);
        $threads = $paginateData['records'];
        $pageHtml = PageHelper::render($paginateData['total'], $pageSize, $page, "?page={page}");

        ModelHelper::modelJoin($threads, 'memberUserId', '_memberUser', 'member_user', 'id');
        ModelHelper::modelJoin($threads, 'categoryId', '_category', 'forum_category', 'id');

        $categories = $forumService->getCategories();

        return $this->_view('forum.index', compact('categoryId', 'threads', 'categories', 'pageHtml'));
    }

    public function threadMy(ForumService $forumService)
    {
        if (!$this->memberUserId()) {
            return Response::send(-1, null, null, '/login?redirect=' . urlencode(RequestHelper::currentPageUrl()));
        }

        $page = Input::get('page', 1);
        $pageSize = 10;
        $option = [];
        $option['order'] = [['isTop', 'desc'], ['updated_at', 'desc']];

        $paginateData = $forumService->paginateMemberThreads($this->memberUserId(), $page, $pageSize, $option);
        $threads = $paginateData['records'];
        $pageHtml = PageHelper::render($paginateData['total'], $pageSize, $page, "?page={page}");

        $categories = $forumService->getCategories();

        return $this->_view('forum.threadMy', compact('threads', 'categories', 'pageHtml'));
    }

    public function thread(ForumService $forumService,
                           MemberService $memberService,
                           $threadId = 0)
    {

        $thread = $forumService->loadThread($threadId);
        if (empty($thread)) {
            return Response::send(-1, 'thread not found');
        }
        $thread['_memberUser'] = $memberService->load($thread['memberUserId']);
        $thread['_category'] = $forumService->loadCategory($thread['categoryId']);

        $page = Input::get('page', 1);
        $pageSize = 10;
        $option = [];
        $option['order'] = ['id', 'asc'];
        $paginateData = $forumService->paginateThreadPost($threadId, $page, $pageSize, $option);
        $pageHtml = PageHelper::render($paginateData['total'], $pageSize, $page, "?page={page}");

        $posts = $paginateData['records'];
        ModelHelper::modelJoin($posts, 'memberUserId', '_memberUser', 'member_user', 'id');

        $isCategoryAdmin = false;
        if ($this->memberUserId()) {
            if ($forumService->isCategoryAdmin($this->memberUserId(), $thread['_category']['id'])) {
                $isCategoryAdmin = true;
            }
        }

        return $this->_view('forum.thread', compact('thread', 'posts', 'pageHtml', 'isCategoryAdmin'));
    }

    public function threadDelete(ForumService $forumService,
                                 $threadId)
    {
        if (!$this->memberUserId()) {
            return Response::send(-1, null, null, '/login?redirect=' . urlencode(RequestHelper::currentPageUrl()));
        }

        $thread = $forumService->loadThread($threadId);
        if (empty($thread)) {
            return Response::send(-1, 'thread not found');
        }
        if ($thread['memberUserId'] != $this->memberUserId() && !$forumService->isCategoryAdmin($this->memberUserId(), $thread['categoryId'])) {
            return Response::send(-1, 'thread not yours');
        }

        $forumService->deleteThread($threadId);

        return Response::send(0, null, null, '/forum/' . $thread['categoryId']);
    }

    public function threadEdit(ForumService $forumService,
                               $id = 0)
    {
        if (!$this->memberUserId()) {
            return Response::send(-1, null, null, '/login?redirect=' . urlencode(RequestHelper::currentPageUrl()));
        }

        $thread = null;
        if ($id) {
            $thread = $forumService->loadThread($id);
            if (empty($thread)) {
                return Response::send(-1, 'thread not found');
            }
            if ($thread['memberUserId'] != $this->memberUserId()) {
                return Response::send(-1, 'thread edit forbidden');
            }
        }

        if (Request::isMethod('post')) {

            $data = [];
            $data['title'] = Input::get('title');
            $data['categoryId'] = Input::get('categoryId');
            $data['content'] = Input::get('content');
            $data['content'] = HtmlHelper::filter($data['content']);

            if (empty($data['categoryId'])) {
                return Response::send(-1, '分类不能为空');
            }

            if (empty($data['title'])) {
                return Response::send(-1, '标题不能为空');
            }
            if (empty($data['content'])) {
                return Response::send(-1, '内容不能为空');
            }

            if ($thread) {
                $thread = $forumService->updateThread($thread['id'], $data);
                return Response::send(0, null, null, '/forum/thread/' . $thread['id']);
            } else {
                $data['memberUserId'] = $this->memberUserId();
                $thread = $forumService->addThread($data);
                return Response::send(0, null, null, '/forum/thread/' . $thread['id']);
            }
        }

        $categories = $forumService->getCategories();

        return $this->_view('forum.threadEdit', compact('categories', 'thread'));
    }


    public function postEdit(ForumService $forumService,
                             $threadId,
                             $postId = 0)
    {
        if (!$this->memberUserId()) {
            return Response::send(-1, null, null, '/login?redirect=' . urlencode(RequestHelper::currentPageUrl()));
        }

        $thread = $forumService->loadThread($threadId);
        if (empty($thread)) {
            return Response::send(-1, 'thread not found');
        }

        if ($postId) {
            $post = $forumService->loadPost($postId);
            if (empty($post) || $post['memberUserId'] != $this->memberUserId()) {
                return Response::send(-1, 'post not found');
            }
        }

        if (Request::isMethod('post')) {
            $data = [];
            $data['content'] = Input::get('content');
            $data['content'] = HtmlHelper::filter($data['content']);
            if (empty($data['content'])) {
                return Response::send(-1, '内容不能为空');
            }

            $data['categoryId'] = $thread['categoryId'];
            $data['threadId'] = $thread['id'];
            $data['memberUserId'] = $this->memberUserId();
            $data['replyPostId'] = intval(Input::get('replyPostId'));
            if (!preg_match('/@(.*?):/', $data['content'])) {
                $data['replyPostId'] = 0;
            }

            if ($postId) {
                $post = $forumService->updatePost($postId, $data);
            } else {
                $post = $forumService->addPost($data);
            }
            $page = $forumService->getPostPageInThread($post['id'], $thread['id'], 20);
            $forumService->updateThread($thread['id'], ['lastReplyTime' => Carbon::now(), 'lastReplyMemberUserId' => $this->memberUserId()]);

            return Response::send(0, null, null, '/forum/thread/' . $thread['id'] . '?page=' . $page . 'postId=' . $post['id']);
        }

        return $this->_view('forum.postEdit', compact('thread', 'post'));
    }


    public function postDelete(ForumService $forumService,
                               $postId)
    {
        if (!$this->memberUserId()) {
            return Response::send(-1, null, null, '/login?redirect=' . urlencode(RequestHelper::currentPageUrl()));
        }

        $post = $forumService->loadPost($postId);
        if (empty($post)) {
            return Response::send(-1, 'post not found');
        }

        $forumService->deletePost($post['id']);

        return Response::send(0, null, null, '/forum/thread/' . $post['threadId']);
    }

}