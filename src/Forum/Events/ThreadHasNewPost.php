<?php

namespace Edwin404\Forum\Events;


class ThreadHasNewPost
{
    public $thread;
    public $post;
    public $postPage;
}