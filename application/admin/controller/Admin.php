<?php
namespace app\admin\controller;


use think\Controller;
use think\Db;
use think\Config;
class Admin extends Controller
{
    //登录
    public function login(){
        $username = input('username','');
        $password = input('password','');

        $edu_db = Db::connect(config('edu_database'));
        $db = Db();

        $admin_limit = Db::name('admin_limit')->where(array('username' => $username))->find();
        if($admin_limit == null){
            return json(array(
                'status' => -1,
                'msg' => '该用户没有权限登陆！',
                'data' => array()
            ));
        }

        $edu_admin_info = $edu_db->name('admin')->where('username','=',$username)->find();
        if(empty($edu_admin_info)){
            return json(array(
                'status' => -1,
                'msg' => '用户名不存在！',
                'data' => array()
            ));
        }

        if($password != $edu_admin_info['real_password']){
            return json(array(
                'status' => -1,
                'msg' => '密码错误，请重新输入！',
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
                'msg' => 'token错误',
                'data' => array(
                )
            ));
        }
        $delete_info = Db::name('admin')->where('id','=',$info['id'])->update(array('token' => ''));
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
}
