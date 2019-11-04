<?php

namespace Edwin404\Forum\Events;


class PostHasNewReply
{
    public $thread;
    public $yourPost;
    public $replyPost;
    public $replyPostPage;
}