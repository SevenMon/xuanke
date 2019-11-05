<?php
namespace app\api\controller;


use think\Controller;
use think\Db;
use think\Config;
class Student extends Controller
{

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
        /*$real_code = cache($str);
        if(empty($real_code)){
            return json(array(
                'status' => -1,
                'msg' => '验证码错误，请重新输入！！！',
                'data' => array()
            ));
        }
        if(time() - $real_code['time'] > 60){
            return json(array(
                'status' => -1,
                'msg' => '验证码过期，请重新发送！',
                'data' => array()
            ));
        }
        if($real_code['code'] != $code){
            return json(array(
                'status' => -1,
                'msg' => '验证码错误，请重新输入！',
                'data' => array()
            ));
        }*/

        $edu_db = Db::connect(config('edu_database'));
        $db = Db();
        $edu_student_info = $edu_db->name('student_baseinfo')->where('stu_phone','=',$phone)->find();
        if($edu_student_info == null){
            return json(array(
                'status' => -1,
                'msg' => '您输入的手机号码不存在，请联系带班老师！',
                'data' => array()
            ));
        }
        $limit_student_info = Db::name('student_limit')->where(array('edu_student_id' => $edu_student_info['id']))->find();
        if($limit_student_info == null){
            return json(array(
                'status' => -1,
                'msg' => '您输入的手机号码不存在，请联系带班老师！',
                'data' => array()
            ));
        }
        $sudent_info = $db->name('student')->where('edu_student_id','=',$edu_student_info['id'])->find();
        $token = md5(time()).randstr();
        if($sudent_info == null){
            $data = array(
                'edu_student_id' => $edu_student_info['id'],
                'token' => $token
            );
            $uid = $db->name('student')->insertGetId($data);
        }else{
            $data = array(
                'token' => $token
            );
            $db->name('student')->where('edu_student_id','=',$edu_student_info['id'])->update($data);
            $uid = $sudent_info['id'];
        }
        $edu_student_info['campus_info'] = $edu_db->name('campus')->where('id','=',$edu_student_info['campus_id'])->find();
        return json(array(
            'status' => 1,
            'msg' => '登录成功',
            'data' => array(
                'token' => $token,
                'edu_student_info' => $edu_student_info,
                'uid' => $uid,
            )
        ));
    }

    //退出
    public function logout(){
        $token = input('token');
        $info = Db::name('student')->where('token','=',$token)->find();
        if(empty($info)){
            return json(array(
                'status' => -1,
                'msg' => '请输入正确的token值',
                'data' => array(
                )
            ));
        }
        $delete_info = Db::name('student')->where('token','=',$token)->update(array('token' => ''));
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
        $edu_student_info = $edu_db->name('student_baseinfo')->where('stu_phone','=',$phone)->find();
        if(empty($edu_student_info)){
            return json(array(
                'status' => -1,
                'msg' => '您输入的手机号码不存在，请联系带班老师！',
                'data' => array()
            ));
        }

        $limit_student_info = Db::name('student_limit')->where(array('edu_student_id' => $edu_student_info['id']))->find();
        if($limit_student_info == null){
            return json(array(
                'status' => -1,
                'msg' => '您输入的手机号码不存在，请联系带班老师！',
                'data' => array()
            ));
        }

        //判断时间
        $str = encrypt($phone);
        $data = cache($str);
        if($data){
            if(time() - $data['time'] < 60){
                return json(array(
                    'status' => -1,
                    'msg' => '发送频繁，请'.(60 - (time() - $data['time'])).'秒之后再次发送',
                    'data' => array()
                ));
            }
        }

        $code = rand(1000,9999);
        //$str = md5(time()).randstr();
        $str = encrypt($phone);
        //TODO 发送短信给用户  验证码
        $alicloud = \think\Loader::model('AliCloud','service');
        //$result = $alicloud->sendLoginCode($phone,$code);
        //测试
        $result['code'] = 1;
        if($result['code'] == 1){
            cache($str, array('code'=>$code,'time' => time()), 300);
            return json(array(
                'status' => 1,
                'msg' => '发送成功！',
                'data' => array(
                    'str' => $str,
                )
            ));
        }else{
            return json(array(
                'status' => 1,
                'msg' => $result['msg'],
                'data' => array(
                    'str' => $str,
                )
            ));
        }

    }

}
