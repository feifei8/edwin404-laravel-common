<?php

namespace Edwin404\Member\Services;


use Edwin404\Base\Support\ModelHelper;

class MemberFollowService
{
    // 意思是 $followerMemberUserId 是否关注了 $memberUserId
    public function isFollowed($followerMemberUserId, $memberUserId)
    {
        if (ModelHelper::exists('member_follower', [
            'followerMemberUserId' => $followerMemberUserId,
            'memberUserId' => $memberUserId,
        ])
        ) {
            return true;
        } else {
            return false;
        }
    }

    private function updateFollowStat($followerMemberUserId, $memberUserId)
    {
        $followCount = ModelHelper::count('member_follower', ['followerMemberUserId' => $followerMemberUserId]);
        ModelHelper::updateOne('member_user', ['id' => $followerMemberUserId], ['followCount' => $followCount]);

        $followerCount = ModelHelper::count('member_follower', ['memberUserId' => $memberUserId]);
        ModelHelper::updateOne('member_user', ['id' => $memberUserId], ['followerCount' => $followerCount]);
    }

    public function follow($followerMemberUserId, $memberUserId)
    {
        $data = [
            'followerMemberUserId' => $followerMemberUserId,
            'memberUserId' => $memberUserId,
        ];
        if (!ModelHelper::exists('member_follower', $data)) {
            ModelHelper::add('member_follower', $data);
            $this->updateFollowStat($followerMemberUserId, $memberUserId);
        }
    }

    public function unfollow($followerMemberUserId, $memberUserId)
    {
        $data = [
            'followerMemberUserId' => $followerMemberUserId,
            'memberUserId' => $memberUserId,
        ];
        ModelHelper::delete('member_follower', $data);
        $this->updateFollowStat($followerMemberUserId, $memberUserId);
    }

    public function paginateFollowerMemberUsers($memberUserId, $page, $pageSize, $option = [])
    {
        $option['where']['memberUserId'] = $memberUserId;
        $paginateData = ModelHelper::modelPaginate('member_follower', $page, $pageSize, $option);
        ModelHelper::modelJoin($paginateData['records'], 'followerMemberUserId', '_followerMemberUser', 'member_user', 'id');
        $memberUsers = [];
        foreach ($paginateData['records'] as $item) {
            $memberUsers[] = $item['_followerMemberUser'];
        }
        $paginateData['records'] = $memberUsers;
        return $paginateData;
    }

    public function paginateFollowingMemberUsers($memberUserId, $page, $pageSize, $option = [])
    {
        $option['where']['followerMemberUserId'] = $memberUserId;
        $paginateData = ModelHelper::modelPaginate('member_follower', $page, $pageSize, $option);
        ModelHelper::modelJoin($paginateData['records'], 'memberUserId', '_memberUser', 'member_user', 'id');
        $memberUsers = [];
        foreach ($paginateData['records'] as $item) {
            $memberUsers[] = $item['_memberUser'];
        }
        $paginateData['records'] = $memberUsers;
        return $paginateData;
    }

    public function mergeIsFollowed(&$memberUsers, $memberUserId)
    {
        if (empty($memberUsers)) {
            return;
        }
        $memberUserIds = [];
        foreach ($memberUsers as $memberUser) {
            $memberUserIds[] = $memberUser['id'];
        }
        $memberFollowers = ModelHelper::model('member_follower')->where([
            'followerMemberUserId' => $memberUserId
        ])->whereIn('memberUserId', $memberUserIds)->get()->toArray();
        $map = [];
        foreach ($memberFollowers as $memberFollower) {
            $map[$memberFollower['memberUserId']] = true;
        }
        foreach ($memberUsers as &$memberUser) {
            if (empty($map[$memberUser['id']])) {
                $memberUser['_isFollowed'] = false;
            } else {
                $memberUser['_isFollowed'] = true;
            }
        }
    }

    public function getFollowedMemberUserIds($memberUserId)
    {
        $memberUsers = ModelHelper::find('member_follower', ['followerMemberUserId' => $memberUserId]);
        return array_pluck($memberUsers, 'memberUserId');
    }
}