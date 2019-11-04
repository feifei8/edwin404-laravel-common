<?php
namespace Edwin404\Wechat\Message;


class MessageLink extends MessageBase
{
    public $type = MessageType::LINK;

    public $url;

    public function toArray()
    {
        return [
            'type' => $this->type,
            'url' => $this->url,
        ];
    }
}