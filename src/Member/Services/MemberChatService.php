<?php

namespace Edwin404\Member\Services;


use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\Response;
use Edwin404\Html\HtmlConverter;
use Edwin404\Html\HtmlType;
use Edwin404\Html\SimpleEmotionConverterInterceptor;
use Edwin404\Member\Events\MemberHasChatMsgEvent;
use Edwin404\Member\Types\MemberChatMsgType;
use Illuminate\Support\Facades\Event;

class MemberChatService
{
    public function create($memberUserId, $chatMemberUserId)
    {
        $data = [
            'memberUserId' => $memberUserId,
            'chatMemberUserId' => $chatMemberUserId,
        ];
        $chat = ModelHelper::load('member_chat', $data);
        if (empty($chat)) {
            $chat = ModelHelper::add('member_chat', $data);
        }
        return $chat;
    }

    public function getMemberUserChat($memberUserId, $chatId)
    {
        $chat = ModelHelper::load('member_chat', [
            'id' => $chatId,
            'memberUserId' => $memberUserId,
        ]);
        return $chat;
    }

    private function send($fromMemberUserId, $toMemberUserId, $type, $content)
    {
        $chatData = ['memberUserId' => $fromMemberUserId, 'chatMemberUserId' => $toMemberUserId];
        $chat = ModelHelper::load('member_chat', $chatData);
        if (empty($chat)) {
            $chat = ModelHelper::add('member_chat', $chatData);
        }
        $recvChatData = ['memberUserId' => $toMemberUserId, 'chatMemberUserId' => $fromMemberUserId];
        $recvChat = ModelHelper::load('member_chat', $recvChatData);
        if (empty($recvChat)) {
            $recvChat = ModelHelper::add('member_chat', $recvChatData);
        }

        $msg = ModelHelper::add('member_chat_msg', [
            'chatId' => $chat['id'],
            'senderMemberUserId' => $fromMemberUserId,
            'isRead' => true,
            'type' => $type,
            'content' => $content,
        ]);
        $recvMsg = ModelHelper::add('member_chat_msg', [
            'chatId' => $recvChat['id'],
            'senderMemberUserId' => $fromMemberUserId,
            'isRead' => false,
            'type' => $type,
            'content' => $content,
        ]);

        ModelHelper::updateOne('member_chat', ['id' => $chat['id']], [
            'msgCount' => ModelHelper::count('member_chat_msg', ['chatId' => $chat['id']]),
            'unreadMsgCount' => 0,
            'lastMsgId' => $msg['id'],
        ]);

        ModelHelper::updateOne('member_chat', ['id' => $recvChat['id']], [
            'msgCount' => ModelHelper::count('member_chat_msg', ['chatId' => $recvChat['id']]),
            'unreadMsgCount' => ModelHelper::count('member_chat_msg', ['chatId' => $recvChat['id'], 'isRead' => false,]),
            'lastMsgId' => $recvMsg['id'],
        ]);

        return Response::generate(0, null, [
            'msg' => $msg,
            'recvMsg' => $recvChat,
        ]);
    }

    public function sendText($fromMemberUserId, $toMemberUserId, $content)
    {
        $content = HtmlConverter::convertToHtml(HtmlType::SIMPLE_TEXT, $content, [
            SimpleEmotionConverterInterceptor::class,
        ]);
        return $this->send($fromMemberUserId, $toMemberUserId, MemberChatMsgType::TEXT, $content);
    }

    public function sendImage($fromMemberUserId, $toMemberUserId, $content)
    {
        $content = '<img data-image-preview src="' . $content . '" />';
        return $this->send($fromMemberUserId, $toMemberUserId, MemberChatMsgType::IMAGE, $content);
    }

    public function listNewMsgsWithChatIdAndMaxId($chatId, $msgMaxId, $limit = 999)
    {
        $newMsgs = ModelHelper::model('member_chat_msg')
            ->where('chatId', $chatId)
            ->where('id', '>', $msgMaxId)
            ->orderBy('id', 'asc')
            ->limit($limit)->get()
            ->toArray();

        return $newMsgs;
    }

    public function setChatMsgAsRead($chatId, $msgIds = [])
    {
        if (empty($msgIds)) {
            return;
        }
        ModelHelper::model('member_chat_msg')
            ->where('chatId', $chatId)
            ->whereIn('id', $msgIds)
            ->update(['isRead' => true]);
        ModelHelper::updateOne(
            'member_chat',
            ['id' => $chatId],
            [
                'unreadMsgCount' => ModelHelper::count('member_chat_msg', ['chatId' => $chatId, 'isRead' => false,]),
            ]
        );
    }

    public function paginateChatMsg($chatId, $page, $pageSize, $option = [])
    {
        $option['where']['chatId'] = $chatId;
        $paginateData = ModelHelper::modelPaginate('member_chat_msg', $page, $pageSize, $option);
        return $paginateData;
    }

    public function paginateChat($memberUserId, $page, $pageSize, $option = [])
    {
        $option['search'] = [];
        $option['where']['memberUserId'] = $memberUserId;
        $paginateData = ModelHelper::modelPaginate('member_chat', $page, $pageSize, $option);
        ModelHelper::modelJoin($paginateData['records'], 'chatMemberUserId', '_chatMemberUser', 'member_user', 'id');
        ModelHelper::modelJoin($paginateData['records'], 'lastMsgId', '_lastMsg', 'member_chat_msg', 'id');
        return $paginateData;
    }

    public function markReadAll($memberUserId)
    {
        $chats = ModelHelper::find('member_chat', ['memberUserId' => $memberUserId]);
        $chatIds = array_fetch($chats, function ($k, $v) {
            return [$k, $v['id']];
        });
        ModelHelper::model('member_chat_msg')->whereIn('chatId', $chatIds)->update(['isRead' => true]);
        ModelHelper::model('member_chat')->where('memberUserId', $memberUserId)->update(['unreadMsgCount' => 0]);
    }

    public function deleteMemberChat($memberUserId, $chatId)
    {
        $chat = ModelHelper::load('member_chat', ['id' => $chatId, 'memberUserId' => $memberUserId]);
        if (empty($chat)) {
            return;
        }
        ModelHelper::delete('member_chat', ['id' => $chat['id']]);
        ModelHelper::delete('member_chat_msg', ['chatId' => $chat['id']]);
    }
}