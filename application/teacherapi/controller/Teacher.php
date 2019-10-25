<?php
namespace app\teacherapi\controller;


use think\Controller;
use think\Db;
use think\Config;
class Teacher extends Controller
{

    //登录
    public function login(){
        $username = input('username','');
        $password = input('password','');

        $edu_db = Db::connect(config('edu_database'));
        $db = Db();
        $where = array();
        $where['username'] = $username;
        $where['post'] = array('in',[4,5]);
        $edu_teacher_info = $edu_db->name('admin')->where($where)->find();
        if($edu_teacher_info == null){
            return json(array(
                'status' => -1,
                'msg' => '账户不存在，请重新输入！',
                'data' => array()
            ));
        }

        $where = array();
        $where['teacher_main_uid'] = $edu_teacher_info['uid'];
        $course = Db::name('course')->where($where)->find();
        if(empty($course)){
            return json(array(
                'status' => -1,
                'msg' => '登录失败、您没有课程！',
                'data' => array()
            ));
        }

        if($edu_teacher_info['real_password'] != $password){
            return json(array(
                'status' => -1,
                'msg' => '密码错误，请重新输入！',
                'data' => array()
            ));
        }

        $teacher_info = $db->name('teacher')->where('edu_teacher_uid','=',$edu_teacher_info['uid'])->find();
        $token = md5(time()).randstr();
        if($teacher_info == null){
            $data = array(
                'edu_teacher_uid' => $edu_teacher_info['uid'],
                'token' => $token
            );
            $uid = $db->name('teacher')->insertGetId($data);
        }else{
            $data = array(
                'token' => $token
            );
            $db->name('teacher')->where('edu_steacher_uid','=',$edu_teacher_info['uid'])->update($data);
            $uid = $teacher_info['id'];
        }
        return json(array(
            'status' => 1,
            'msg' => '登录成功',
            'data' => array(
                'token' => $token,
                'edu_teacher_info' => $edu_teacher_info,
                'uid' => $uid,
            )
        ));
    }

    //退出
    public function logout(){
        $token = input('token');
        $info = Db::name('teacher')->where('token','=',$token)->find();
        if(empty($info)){
            return json(array(
                'status' => -1,
                'msg' => '请输入正确的token值',
                'data' => array(
                )
            ));
        }
        $delete_info = Db::name('teacher')->where('token','=',$token)->update(array('token' => ''));
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
