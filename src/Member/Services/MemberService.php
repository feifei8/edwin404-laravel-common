<?php

namespace Edwin404\Member\Services;


use Edwin404\Base\Support\ModelHelper;
use Edwin404\Base\Support\Response;
use Edwin404\Data\Facades\DataFacade;
use Edwin404\Data\Services\DataService;
use Edwin404\Member\Facades\MemberUploadFacade;
use Edwin404\Member\Helpers\EncryptHelper;
use Edwin404\Member\Types\MemberMessageStatus;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class MemberService
{

    /**
     * 批量查询用户
     * @param $userIds
     * @return array [userId=>MemberUser,...]
     */
    public function findUsers($userIds)
    {
        $userMemberMap = [];
        $memberUsers = ModelHelper::model('member_user')->whereIn('id', $userIds)->get();
        foreach ($memberUsers as &$r) {
            $userMemberMap[$r->id] = $r->toArray();
        }
        return $userMemberMap;
    }

    public function mergeMemberUsers(&$records, $memberUserIdKey = 'memberUserId', $memberUserMergeKey = '_memberUser')
    {
        ModelHelper::modelJoin($records, $memberUserIdKey, $memberUserMergeKey, 'member_user', 'id');
    }

    public function add($data)
    {
        return ModelHelper::add('member_user', $data);
    }

    public function load($id)
    {
        return ModelHelper::load('member_user', ['id' => $id]);
    }

    public function update($id, $data)
    {
        return ModelHelper::updateOne('member_user', ['id' => $id], $data);
    }

    public function delete($id)
    {
        ModelHelper::delete('member_user', ['id' => $id]);
    }

    public function loadByUsername($username)
    {
        return ModelHelper::load('member_user', ['username' => $username]);
    }

    public function loadByEmail($email)
    {
        return ModelHelper::load('member_user', ['email' => $email]);
    }

    public function loadByPhone($phone)
    {
        return ModelHelper::load('member_user', ['phone' => $phone]);
    }

    /**
     * 唯一性检查
     *
     * @param string $type = email | phone | username
     * @param $value
     * @param int $ignoreUserId
     * @return array ['code'=>'0','msg'=>'ok']
     */
    public function uniqueCheck($type, $value, $ignoreUserId = 0)
    {
        $value = trim($value);
        switch ($type) {
            case 'email' :
                if (!preg_match('/(^[\w-.]+@[\w-]+\.[\w-.]+$)/', $value)) {
                    return Response::generate(-1, '邮箱格式不正确');
                }
                break;
            case 'phone' :
                if (!preg_match('/(^1[0-9]{10}$)/', $value)) {
                    return Response::generate(-1, '手机格式不正确');
                }
                break;
            case 'username' :
                if (strpos($value, '@') !== false) {
                    return Response::generate(-1, '用户名格式不正确');
                }
                break;
            default :
                return Response::generate(-1, '未能识别的类型' . $type);
        }

        $memberUser = ModelHelper::load('member_user', [$type => $value]);
        if (empty ($memberUser)) {
            return Response::generate(0, 'ok');
        }

        $lang = array(
            'username' => '用户名',
            'email' => '邮箱',
            'phone' => '手机号'
        );
        if ($ignoreUserId == $memberUser['id']) {
            return Response::generate(0, 'ok');
        }
        return Response::generate(-2, $lang [$type] . '已经被占用');
    }

    public function registerUsernameQuick($username)
    {
        $suggestionUsername = $username;
        while (true) {
            $ret = $this->register($suggestionUsername, '', '', '', true);
            if ($ret['code']) {
                $suggestionUsername = $username . Str::random(3);
            } else {
                return $ret;
            }
        }
    }

    /**
     * 注册，email phone username 可以只选择一个为注册ID
     *
     * @param string $username
     * @param string $phone
     * @param string $email
     * @param string $password
     * @param bool $ignorePassword
     * @return array ['code'=>'0','msg'=>'ok','data'=>'member_user array']
     */
    public function register($username = '', $phone = '', $email = '', $password = '', $ignorePassword = false)
    {
        $email = trim($email);
        $phone = trim($phone);
        $username = trim($username);

        if (!($email || $phone || $username)) {
            return Response::generate(-1, '所有注册字段均为空');
        }

        if ($email) {
            $ret = $this->uniqueCheck('email', $email);
            if ($ret['code']) {
                return $ret;
            }
        }
        if ($phone) {
            $ret = $this->uniqueCheck('phone', $phone);
            if ($ret['code']) {
                return $ret;
            }
        }
        if ($username) {
            $ret = $this->uniqueCheck('username', $username);
            if ($ret['code']) {
                return $ret;
            }
            // 为了统一登录时区分邮箱
            if (Str::contains($username, '@')) {
                return Response::generate(-1, '用户名不能包含特殊字符');
            }
            // 为了统一登录时候区分手机号
            if (preg_match('/^[0-9]{11}$/', $username)) {
                return Response::generate(-1, '用户名不能为纯数字');
            }
        }
        if (!$ignorePassword) {
            if (empty($password) || strlen($password) < 6) {
                return Response::generate(-3, '密码不合法');
            }
        }

        $passwordSalt = Str::random(16);

        $memberUser = ModelHelper::add('member_user', [
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'password' => $ignorePassword ? null : EncryptHelper::md5Encode($password, $passwordSalt),
            'passwordSalt' => $ignorePassword ? null : $passwordSalt,
        ]);

        return Response::generate(0, 'ok', $memberUser);
    }

    /**
     * 修改密码
     * 注意参数顺序!!!
     *
     * @param $userId
     * @param $new
     * @param $old
     * @param bool $ignoreOld
     * @return array
     */
    public function changePassword($userId, $new, $old, $ignoreOld = false)
    {
        if (!$ignoreOld && empty($old)) {
            return Response::generate(-1, '旧密码不能为空');
        }

        $memberUser = ModelHelper::load('member_user', ['id' => $userId]);
        if (empty($memberUser)) {
            return Response::generate(-1, "用户不存在");
        }
        if (empty ($new)) {
            return Response::generate(-1, '新密码为空');
        }
        if (!$ignoreOld && EncryptHelper::md5Encode($old, $memberUser['passwordSalt']) != $memberUser['password']) {
            return Response::generate(-1, '旧密码不正确');
        }

        $passwordSalt = Str::random(16);

        ModelHelper::updateOne('member_user', ['id' => $memberUser['id']], [
            'passwordSalt' => $passwordSalt,
            'password' => EncryptHelper::md5Encode($new, $passwordSalt)
        ]);

        return Response::generate(0, 'ok');
    }

    /**
     * 设置头像
     *
     * @param $userId
     * @param $avatarData
     * @return array ['code'=>'0','msg'=>'ok']
     */
    public function setAvatar($userId, $avatarData, $avatarExt = 'jpg')
    {
        $memberUser = $this->load($userId);
        if (empty($memberUser)) {
            return Response::generate(-1, '用户不存在');
        }
        if (empty($avatarData)) {
            return Response::generate(-1, '图片数据为空');
        }
        $imageBig = Image::make($avatarData)->resize(400, 400)->encode($avatarExt, 75);
        $imageMedium = Image::make($avatarData)->resize(200, 200)->encode($avatarExt, 75);
        $image = Image::make($avatarData)->resize(50, 50)->encode($avatarExt, 75);

        $retBig = DataFacade::upload('image', 'uid_' . $userId . '_avatar_big.' . $avatarExt, $imageBig);
        if ($retBig['code']) {
            return Response::generate(-1, '头像存储失败（' . $retBig['msg'] . '）');
        }
        $retMedium = DataFacade::upload('image', 'uid_' . $userId . '_avatar_middle.' . $avatarExt, $imageMedium);
        if ($retMedium['code']) {
            DataFacade::deleteById($retBig['data']['id']);
            if ($retBig['code']) {
                return Response::generate(-1, '头像存储失败（' . $retMedium['msg'] . '）');
            }
        }
        $ret = DataFacade::upload('image', 'uid_' . $userId . '_avatar.' . $avatarExt, $image);
        if ($ret['code']) {
            DataFacade::deleteById($retBig['data']['id']);
            DataFacade::deleteById($retMedium['data']['id']);
            if ($retBig['code']) {
                return Response::generate(-1, '头像存储失败（' . $ret['msg'] . '）');
            }
        }

        $this->update($memberUser['id'], [
            'avatarBig' => DataService::DATA . '/' . $retBig['data']['category'] . '/' . $retBig['data']['path'],
            'avatarMedium' => DataService::DATA . '/' . $retMedium['data']['category'] . '/' . $retMedium['data']['path'],
            'avatar' => DataService::DATA . '/' . $ret['data']['category'] . '/' . $ret['data']['path']
        ]);

        return Response::generate(0, 'ok');
    }

    /**
     * 登录，email phone username 只能选择一个作为登录凭证
     *
     * @param string $username
     * @param string $phone
     * @param string $email
     * @param string $password
     * @return array ['code'=>'0','msg'=>'ok','data'=>MemberUser]
     */
    public function login($username = '', $phone = '', $email = '', $password = '')
    {
        $email = trim($email);
        $phone = trim($phone);
        $username = trim($username);

        if (!($email || $phone || $username)) {
            return Response::generate(-1, '所有登录字段均为空');
        }
        if (!$password) {
            return Response::generate(-2, '密码为空');
        }

        if ($email) {
            if (!preg_match('/(^[\w-.]+@[\w-]+\.[\w-.]+$)/', $email)) {
                return Response::generate(-3, '邮箱格式不正确');
            }
            $where = array(
                'email' => $email
            );
        } else if ($phone) {
            if (!preg_match('/(^1[0-9]{10}$)/', $phone)) {
                return Response::generate(-4, '手机格式不正确');
            }
            $where = array(
                'phone' => $phone
            );
        } else if ($username) {
            if (strpos($username, '@') !== false) {
                return Response::generate(-5, '用户名格式不正确');
            }
            $where = array(
                'username' => $username
            );
        }

        $memberUser = ModelHelper::load('member_user', $where);
        if (empty($memberUser)) {
            return Response::generate(-6, '登录失败:用户名或密码错误');
        }

        if ($memberUser['password'] != EncryptHelper::md5Encode($password, $memberUser['passwordSalt'])) {
            return Response::generate(-7, '登录失败:用户名或密码错误');
        }

        return Response::generate(0, 'ok', $memberUser);
    }

    public function getMemberUserIdByOauth($oauthType, $openId)
    {
        $m = ModelHelper::load('member_oauth', ['type' => $oauthType, 'openId' => $openId]);
        if (empty($m)) {
            return 0;
        }
        return intval($m['memberUserId']);
    }

    public function getOauthOpenId($memberUserId, $oauthType)
    {
        $where = ['memberUserId' => $memberUserId, 'type' => $oauthType];
        $m = ModelHelper::load('member_oauth', $where);
        if (empty($m)) {
            return null;
        }
        return $m['openId'];
    }

    public function putOauth($memberUserId, $oauthType, $openId)
    {
        $where = ['memberUserId' => $memberUserId, 'type' => $oauthType];
        $m = ModelHelper::load('member_oauth', $where);
        if (empty($m)) {
            ModelHelper::add('member_oauth', array_merge($where, ['openId' => $openId]));
        } else if ($m['openId'] != $openId) {
            ModelHelper::updateOne('member_oauth', ['id' => $m['id']], ['openId' => $openId]);
        }
    }

    public function forgetOauth($oauthType, $openId)
    {
        ModelHelper::delete('member_oauth', ['type' => $oauthType, 'openId' => $openId]);
    }

    public function updateNewMessageStatus($memberUserId)
    {
        ModelHelper::updateOne('member_user', ['id' => $memberUserId], [
            'newMessageCount' => ModelHelper::count('member_message', [
                'userId' => $memberUserId,
                'status' => MemberMessageStatus::UNREAD,
            ])
        ]);
    }

    public function updateNewChatMsgStatus($memberUserId)
    {
        ModelHelper::updateOne('member_user', ['id' => $memberUserId], [
            'newChatMsgCount' => ModelHelper::sum('member_chat', 'unreadMsgCount', [
                'memberUserId' => $memberUserId,
            ])
        ]);
    }

    public function paginate($page, $pageSize, $option = [])
    {
        return ModelHelper::modelPaginate('member_user', $page, $pageSize, $option);
    }

}