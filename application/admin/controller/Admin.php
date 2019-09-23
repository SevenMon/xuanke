<?php
namespace app\admin\controller;


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
        $password = input('password','');
        $confirm_phone = preg_match("/^1[34578]\d{9}$/", $phone);
        if(empty($phone) || !$confirm_phone){
             return json(array(
                'status' => -1,
                'msg' => '请输入，正确的手机号码！',
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
        var_dump($edu_admin_info);
        var_dump($edu_admin_info['salt']);
        echo hash('md5',hash('md5','4baf9371c7d7abc058612cbcb5aa9c17'.'123456'));
        exit();

        $admin_info = $db->name('admin')->where('edu_uid','=',$edu_admin_info['uid'])->find();
        $admin_token = md5(time()).randstr();
        if(empty($admin_info)){
            $data = array(
                'edu_uid' => $edu_admin_info['uid'],
                'admin_token' => $admin_token
            );
            $uid = $db->name('admin')->insertGetId($data);
        }else{
            $data = array(
                'admin_token' => $admin_token
            );
            $db->name('admin')->where('edu_uid','=',$edu_admin_info['uid'])->update($data);
            $uid = $admin_info['id'];
        }
        return json(array(
            'status' => 1,
            'msg' => '登录成功',
            'data' => array(
                'admin_token' => $admin_token,
                'edu_admin_info' => $edu_admin_info,
                'uid' => $uid,
            )
        ));
    }

    //退出
    public function logout(){
        $admin_token = input('admin_token');
        $info = Db::name('admin')->where('admin_token','=',$admin_token)->find();
        if(empty($info)){
            return json(array(
                'status' => -1,
                'msg' => 'admin_token错误',
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
