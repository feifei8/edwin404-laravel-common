<?php
namespace Edwin404\Wechat\Message;


class MessageCustom extends MessageBase
{
    public $type = MessageType::CUSTOM;

    public $name;
    public $param;

    public function toArray()
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'param' => $this->param,
        ];
    }
}