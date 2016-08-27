<?php

namespace Controllers;

use Engine\Sms;
use Models\Audio;
use Models\Favorite;
use Models\Follow;
use Models\MailTemlates;
use Models\Member;
use Models\News;
use Models\Picture;
use Models\Times;
use Models\Video;
use Phalcon\DI;

class UserController extends AbstractController
{
    public function register1Action()
    {
        $mobile = $this->request->getPost('mobile');
        $verify = $this->request->getPost('verify');
        if (empty($mobile) || empty($verify)) {
            callback(false, "请提供正确的手机号与验证码", 403);
        }
        if (!is_mobile($mobile)) {
            callback(false, "手机不正确", 403);
        }
        $model_member = new Member();
        $data['mobile'] = $mobile;
        $data['status'] = 100;

        $r = $model_member->getMobileReport($data);

        if (!$r || $r->id_code != $verify) callback(false, "验证失败", 403);
        callback(true, "验证成功", 201);
    }

    public function register2Action()
    {
        $mobile = $this->request->getPost('mobile');
        $nickname = $this->request->getPost('nickname');
        $password = $this->request->getPost('password');

        $userinfo = array();
        $userinfo['phpssouid'] = 2;
        $userinfo['encrypt'] = create_randomstr(6);
        $userinfo['username'] = $userinfo['mobile'] = $mobile;
        $userinfo['nickname'] = (isset($nickname) && is_username($nickname)) ? $nickname : callback(false, "用户名中含有非法字符");
        $password = (isset($password) && is_badword($password) == false) ? $password : callback(false, "密码中含有非法字符");
        $userinfo['password'] = password($password, $userinfo['encrypt']);
        $userinfo['email'] = $mobile . '@139.cn';
        $userinfo['regip'] = $userinfo['lastip'] = getIp();
        $userinfo['loginnum'] = 0;
        $userinfo['regdate'] = $userinfo['lastdate'] = TIMESTAMP;
        $userinfo['modelid'] = 10;
        $userinfo['groupid'] = 6;

        $model_member = new Member();
        $user = $model_member->getMemberInfo(['username' => $userinfo['username']]);
        if (empty($user)) {
            $userid = $model_member->saveMemberInfo($userinfo, 'INSERT');
            if ($userid > 0) {
                callback(true, "注册成功", 201, ['userid' => $userid, 'username' => $userinfo['username'], 'nickname' => $userinfo['nickname'], 'groupid' => $userinfo['groupid']]);
            } else {
                callback(false, "注册失败", 400);
            }
        } else  callback(false, "用户已存在", 403);

    }

    public function loginAction()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        if (empty($username) || empty($password)) {
            callback(false, "用户名或密码不能为空", 401);
        }
        $username = isset($username) && is_username($username) ? trim($username) : callback(false, "用户名不能为空", 401);
        $password = isset($password) && trim($password) ? trim($password) : callback(false, "密码不能为空", 401);

        $model_member = new Member();
        $user = $model_member->getMemberInfo(['username' => $username]);
        $ip = getIp();
        $times = new Times();
        $rtime = $times->getTimesInfo(array('username' => $username));

        if (!empty($rtime) && $rtime->times > 4) {
            $tepText = "密码重试次数太多，请过15分钟后重新登录！";
        }

        if (empty($user)) {    //用户不存在
            callback(false, "用户不存在", 404);
        } elseif (password($password, $user->encrypt) != $user->password) { //密码错误
            //密码错误剩余重试次数
            if (empty($rtime)) {
                $times->saveTimesInfo(array('username' => $username, 'ip' => $ip, 'logintime' => TIMESTAMP, 'times' => 1), 'INSERT');
                $time = 5;
                $tepText = "密码输入错误,剩余" . $time . "次输入次数";
            } elseif ($rtime && $rtime->times < 5) {
                $time = 5 - intval($rtime->times);
                $times->saveTimesInfo(array('ip' => '127.0.0.1', 'times' => 'times + 1'), '', array('username' => $username));
                $tepText = "密码输入错误,剩余" . $time . "次输入次数";
            }

            callback(false, $tepText, 402);
        } elseif ($user->islock) {//如果用户被锁定
            callback(false, "用户已经被锁定", 401);
        } else {
            $updatearr = array('lastip' => $ip, 'lastdate' => TIMESTAMP);
            $model_member->saveMemberInfo($updatearr, array('userid' => $user->userid));
            callback(true, "登录成功", 201, ['userid' => $user->userid, 'username' => $user->username, 'nickname' => $user->nickname, 'groupid' => $user->groupid]);
        }

    }

    public function logoutAction()
    {
        $userid = $this->request->getPost('userid');
        $model_member = new Member();
        $user = $model_member->getMemberInfo(['userid' => $userid]);
        if (!empty($user)) {
            callback(true, "退出成功", 201, ['userid' => $user->userid, 'username' => $user->username, 'nickname' => $user->nickname, 'groupid' => $user->groupid]);
        } else {
            callback(false, "退出失败", 403);
        }
    }

    public function passwordAction()
    {
        $userid = $this->request->getPost('userid');
        $oldpassword = $this->request->getPost('oldpassword');
        $newpassword = $this->request->getPost('newpassword');
        if (empty($userid) || empty($oldpassword) || empty($newpassword)) {
            callback(false);
        }
        $model_member = new Member();
        $user = $model_member->getMemberInfo(['userid' => $userid]);
        if (empty($user)) {
            callback(false, '用戶不存在', 403);
        }

        if (!is_password($newpassword)) {
            callback(false, '密码格式错误', 403);
        }
        if ($user->password != password($oldpassword, $user->encrypt)) {
            callback(false, '原密码错误', 403);
        }

        $newpassword = password($newpassword, $user->encrypt);
        if ($user->password == $newpassword) {
            callback(false, '原密码与新密码相同', 403);
        }
        $updateinfo = array();
        $updateinfo['password'] = $newpassword;

        $arr = $model_member->saveMemberInfo($updateinfo, 'update', array('userid' => $userid));

        ($arr) ? callback(true, "修改密码成功", 201) : callback(false, "修改密码失败", 403);

    }

    public function forgetAction()
    {
        $mobile = $this->request->getPost('mobile');
        $verify = $this->request->getPost('verify');
        if (empty($mobile) || empty($verify)) {
            callback(false, "请提供正确的手机号与验证码", 401);
        }
        if (!is_mobile($mobile)) {
            callback(false, "手机不正确", 401);
        }
        $model_member = new Member();
        $data['mobile'] = $mobile;
        $data['status'] = 101;
        $user = $model_member->getMemberInfo(['mobile' => $mobile]);
        if (empty($user)) {    //用户不存在
            callback(false, "用户不存在", 404);
        }

        $r = $model_member->getMobileReport($data);

        $model_tpl = new MailTemlates();
        $tpl_info = $model_tpl->getTplInfo(['code' => 'reset_pwd']);

        $param = array();
        $param['send_time'] = date('Y-m-d H:i', TIMESTAMP);
        $param['new_password'] = $new_password = random(6, 1);
        $param['site_name'] = "无界传媒";
        $message = ncReplaceText($tpl_info->content, $param);

        if (!$r)
            callback(false, "验证码已经失效，请重新申请", 403);

        if ($r->id_code != $verify) {
            callback(false, "验证失败", 403);
        }
        $result = $model_member->getMobileLog($data);
        $arr = false;
        if ($result->total == 1) {
            $data = array('mobile' => $mobile, 'id_code' => $new_password, 'msg' => $message, 'status' => 102, 'send_userid' => 0, 'posttime' => TIMESTAMP, 'return_id' => 1, 'ip' => getIp());
            $model_member->sendMobileLog($data);
            $deldata = array('mobile' => $mobile, 'id_code' => $verify,  'status' => 101);
            $model_member->delMobileLog($deldata);
            $userdata['password'] = password($new_password, $user->encrypt);
            $arr = $model_member->saveMemberInfo($userdata, 'update', array('userid' => $user->userid));
        }

        if ($arr) {
            $sms = new Sms();
            $sms->send($mobile, $message);
            callback(true, "找回密码成功", 201);
        } else callback(true, "找回密码失败", 403);
    }

    public function sendCodeAction($type, $mobile)
    {
        $model_member = new Member();
        if (!is_mobile($mobile))
            callback(false, "手机不正确", 401);


        $check_member_mobile = $model_member->getMemberInfo(['mobile' => $mobile]);
        if (is_array($check_member_mobile) and count($check_member_mobile) > 0) {
            callback(false, "手机已被使用", 401);
        } else {
            $this->_send_mobile_vcode($type, $mobile);
        }
    }

    public function favoriteAction($userid, $page)
    {
        $model_favorite = new Favorite();
        if (empty($userid))
            callback(false, "用户不能为空", 401);
        $parameters['page'] = (int)$page != 0 ? (int)$page : 1;
        list($pagecount, $list) = $model_favorite->getFavoriteList(['userid' => $userid], $parameters);
        $json =
            [
                "pagecount" => $pagecount,
                "list" => $list
            ];
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
    }

    public function delfavoriteAction()
    {
        $id = $this->request->getPost('id');
        $userid = $this->request->getPost('userid');

        $model_favorite = new Favorite();
        if (empty($userid))
            callback(false, "用户不能为空", 403);
        $dels = $model_favorite->delFavoriteInfo(['userid' => $userid, 'id' => $id]);
        $dels ? callback(true, "取消收藏成功", 204) : callback(false, "取消收藏失败", 403);
    }

    public function addfavoriteAction()
    {
        $data['aid'] = $this->request->getPost('id');
        $data['userid'] = $userid = $this->request->getPost('userid');
        $data['title'] = $this->request->getPost('title');
        $data['catid'] = $this->request->getPost('cid');
        $data['type'] = $this->request->getPost('type');
        $model_favorite = new Favorite();
        if (empty($userid))
            callback(false, "用户不能为空");

        $ids = $model_favorite->addFavoriteInfo($data);
        $ids ? callback(true, '收藏成功', 201) : callback(false, "添加失败");
    }

    public function followAction($userid, $page)
    {
        $model_follow = new Follow();
        if (empty($userid))
            callback(false, "用户不能为空", 403);
        $parameters['page'] = (int)$page != 0 ? (int)$page : 1;
        list($pagecount, $list) = $model_follow->getFollowList(['userid' => $userid], $parameters);
        $json =
            [
                "pagecount" => $pagecount,
                "list" => $list
            ];
        echo json_encode($json, JSON_UNESCAPED_UNICODE);
    }

    public function delFollowAction()
    {
        $id = $this->request->getPost('id');
        $userid = $this->request->getPost('userid');

        $model_follow = new Follow();
        if (empty($userid))
            callback(false, "用户不能为空", 403);
        $dels = $model_follow->delFollowInfo(['id' => $id]);
        $dels ? callback(true, '取消关注成功', 204) : callback(false, "取消关注失败", 403);
    }

    public function addFollowAction()
    {
        $data['catid'] = $this->request->getPost('cid');
        $data['userid'] = $userid = $this->request->getPost('userid');
        $model_follow = new Follow();
        if (empty($userid))
            callback(false, "用户不能为空", 403);

        $ids = $model_follow->addFollowInfo($data);
        $ids ? callback(true, '关注成功', 201) : callback(false, "关注失败", 403);
    }

    private function _send_mobile_vcode($type, $mobile)
    {
        $verify_code = rand(100, 999) . rand(100, 999);
        $data = array();
        $model_tpl = new MailTemlates();
        if ($type == 'reg') {
            $code = 'reg_mobile';
            $status = '100';
        }
        if ($type == 'forget') {
            $code = 'reset_mobile';
            $status = '101';
        }
        $tpl_info = $model_tpl->getTplInfo(['code' => $code]);

        $param = array();
        $param['send_time'] = date('Y-m-d H:i', TIMESTAMP);
        $param['verify_code'] = $verify_code;
        $param['new_password'] = $new_password = random(6, 1);
        $param['site_name'] = "无界传媒";
        $subject = ncReplaceText($tpl_info->title, $param);
        $message = ncReplaceText($tpl_info->content, $param);

        $sms = new Sms();
        $model_member = New Member();

        $data = array('mobile' => $mobile, 'id_code' => $verify_code, 'msg' => $message, 'status' => $status, 'send_userid' => 0, 'posttime' => TIMESTAMP, 'return_id' => 1, 'ip' => getIp());
        $result = $model_member->getMobileLog($data);
        $arr = array();
        if ($type == 'forget') {
            $user = $model_member->getMemberInfo(['mobile' => $mobile]);
            if (empty($user)) {    //用户不存在
                callback(false, "用户不存在", 404);
            }
        }
        if ($result->total) {
            $param['verify_code'] = $result->id_code;
            $param['send_time'] = date('Y-m-d H:i', TIMESTAMP);
            $message = ncReplaceText($tpl_info->content, $param);
            $arr = true;
        } else {
            $arr = $model_member->sendMobileLog($data);
        }
        if ($arr) {
            $sms->send($mobile, $message);
            callback(true, "发送成功", 200);
        } else {
            callback(false, "验证码发送失败", 404);
        }
    }
}
