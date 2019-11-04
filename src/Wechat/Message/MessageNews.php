<?php
namespace Edwin404\Wechat\Message;


class MessageNews extends MessageBase
{
    public $type = MessageType::NEWS;

    public $title;
    public $cover;
    public $summary;
    public $link;

    public function toArray()
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'cover' => $this->cover,
            'summary' => $this->summary,
            'link' => $this->link,
        ];
    }
}