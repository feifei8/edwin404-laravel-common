<?php
namespace Edwin404\Wechat\Message;


use Edwin404\Base\Support\Exception;

abstract class MessageBase
{
    public $type = MessageType::UNKNOWN;
    public $k = null;

    public function toArray()
    {
        throw new Exception('you should override toArray in MessageXxx');
    }

    public function serialize()
    {
        return json_encode($this->toArray());
    }

    /**
     * @param $data
     * @return MessageBase
     */
    public static function fromArray($data)
    {
        if (empty($data['type'])) {
            return null;
        }
        switch ($data['type']) {
            case MessageType::TEXT:
                $m = new MessageText();
                $m->k = empty($data['k']) ? null : $data['k'];
                $m->content = empty($data['content']) ? null : $data['content'];
                return $m;
            case MessageType::NEWS:
                $m = new MessageNews();
                $m->k = empty($data['k']) ? null : $data['k'];
                $m->title = empty($data['title']) ? null : $data['title'];
                $m->cover = empty($data['cover']) ? null : $data['cover'];
                $m->summary = empty($data['summary']) ? null : $data['summary'];
                $m->link = empty($data['link']) ? null : $data['link'];
                return $m;
            case MessageType::LINK:
                $m = new MessageLink();
                $m->k = empty($data['k']) ? null : $data['k'];
                $m->url = empty($data['url']) ? null : $data['url'];
                return $m;
            case MessageType::IMAGE:
                $m = new MessageImage();
                $m->k = empty($data['k']) ? null : $data['k'];
                $m->url = empty($data['url']) ? null : $data['url'];
                return $m;
            case MessageType::CUSTOM:
                $m = new MessageCustom();
                $m->k = empty($data['k']) ? null : $data['k'];
                $m->name = empty($data['name']) ? null : $data['name'];
                $m->param = empty($data['param']) ? null : $data['param'];
                return $m;
        }
        return null;
    }

}