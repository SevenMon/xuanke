<?php
namespace app\api\controller;


use think\Controller;
use think\Db;
use think\Config;
class Admin extends Controller
{
    public function index()
    {
        $db = Db::connect(config('edu_database'));
        $list = $db->name('admin')->select();
        var_dump($list);
        exit();
    }

    //登录
    public function login(){
        $phone = input('phone','');
        $code = input('code','');
        $str = input('str','');
        $confirm_phone = preg_match("/^1[34578]\d{9}$/", $phone);
        $confirm_code = preg_match("/^\d{4}$/", $code);
        if(empty($phone) || empty($code) || !$confirm_phone || !$confirm_code){
             return json(array(
                'status' => -1,
                'msg' => '请输入，正确的手机号码和验证码！',
                'data' => array()
            ));
        }
        //检查验证码
        $real_code = cache($str);
        if(empty($real_code)){
            return json(array(
                'status' => -1,
                'msg' => '验证码过期，请重新发送！',
                'data' => array()
            ));
        }
        if($real_code != $code){
            return json(array(
                'status' => -1,
                'msg' => '验证码错误，请重新输入！',
                'data' => array()
            ));
        }

        $edu_db = Db::connect(config('edu_database'));
        $db = Db();
        $edu_admin_info = $edu_db->name('admin')->where('account_phone','=',$phone)->find();
        if(empty($edu_admin_info)){
            return json(array(
                'status' => -1,
                'msg' => '手机号不存在，请输入正确手机号码！',
                'data' => array()
            ));
        }
        $admin_info = $db->name('admin')->where('edu_uid','=',$edu_admin_info['uid'])->find();
        $token = md5(time()).randstr();
        if(empty($admin_info)){
            $data = array(
                'edu_uid' => $edu_admin_info['uid'],
                'token' => $token
            );
            $uid = $db->name('admin')->insertGetId($data);
        }else{
            $data = array(
                'token' => $token
            );
            $db->name('admin')->where('edu_uid','=',$edu_admin_info['uid'])->update($data);
            $uid = $admin_info['id'];
        }
        return json(array(
            'status' => 1,
            'msg' => '登录成功',
            'data' => array(
                'token' => $token,
                'edu_admin_info' => $edu_admin_info,
                'uid' => $uid,
            )
        ));
    }

    //退出
    public function logout(){
        $token = input('token');
        $info = Db::name('admin')->where('token','=',$token)->find();
        if(empty($info)){
            return json(array(
                'status' => -1,
                'msg' => '请输入正确的token值',
                'data' => array(
                )
            ));
        }
        $delete_info = Db::name('admin')->where('token','=',$token)->update(array('token' => ''));
        if(empty($delete_info)){
            return json(array(
                'status' => -1,
                'msg' => '退出失败',
                'data' => array(
                )
            ));
        }else{
            return json(array(
                'status' => 1,
                'msg' => '退出成功',
                'data' => array(
                )
            ));
        }
    }

    //发送验证码
    public function sendCode(){
        $phone = input('phone','');
        $confirm_phone = preg_match("/^1[34578]\d{9}$/", $phone);
        if(empty($phone) || !$confirm_phone){
            return json(array(
                'status' => -1,
                'msg' => '请输入，正确的手机号码！',
                'data' => array()
            ));
        }
        $edu_db = Db::connect(config('edu_database'));
        $edu_admin_info = $edu_db->name('admin')->where('account_phone','=',$phone)->find();
        if(empty($edu_admin_info)){
            return json(array(
                'status' => -1,
                'msg' => '手机号不存在，请输入正确手机号码！',
                'data' => array()
            ));
        }

        $code = rand(1000,9999);
        $str = md5(time()).randstr();
        //TODO 发送短信给用户  验证码

        cache($str, $code, 300);
        return json(array(
            'status' => 1,
            'msg' => '发送成功！',
            'data' => array(
                'code' => $code,
                'str' => $str,
            )
        ));
    }

}
