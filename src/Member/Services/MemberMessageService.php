<?php

namespace Edwin404\Member\Services;

use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\Response;
use Edwin404\Member\Types\MemberMessageStatus;

class MemberMessageService
{

    public function loadWithUserId($userId, $id)
    {
        return ModelHelper::load('member_message', ['id' => $id, 'userId' => $userId]);
    }

    public function getMessageCountWithStatus($userId, $status)
    {
        return ModelHelper::count('member_message', ['userId' => $userId, 'status' => $status]);
    }

    public function getMessageWithStatus($userId, $status)
    {
        return ModelHelper::find('member_message', ['userId' => $userId, 'status' => $status]);
    }

    public function getMemberUnreadMessageCount($userId)
    {
        return ModelHelper::count('member_message', ['userId' => $userId, 'status' => MemberMessageStatus::UNREAD]);
    }

    public function setMemberMessageRead($userId, $ids = [])
    {
        if (empty($ids)) {
            return;
        }
        ModelHelper::model('member_message')->where(['userId' => $userId])->whereIn('id', $ids)->update(['status' => MemberMessageStatus::READ]);
    }

    public function setMemberMessageReadAll($userId)
    {
        ModelHelper::model('member_message')->where(['userId' => $userId])->update(['status' => MemberMessageStatus::READ]);
    }

    public function send($userId, $content, $fromId = 0)
    {
        ModelHelper::add('member_message', [
            'userId' => $userId,
            'fromId' => $fromId,
            'status' => MemberMessageStatus::UNREAD,
            'content' => $content,
        ]);
        return Response::generate(0, null);
    }


    public function paginate($userId, $page, $pageSize, $option = [])
    {
        $option['where']['userId'] = $userId;
        return ModelHelper::modelPaginate('member_message', $page, $pageSize, $option);
    }

    public function update($id, $data)
    {
        return ModelHelper::updateOne('member_message', ['id' => $id], $data);
    }

    public function deleteMemberUserMessage($id, $memberUserId)
    {
        ModelHelper::delete('member_message', ['id' => $id, 'userId' => $memberUserId]);
    }

}