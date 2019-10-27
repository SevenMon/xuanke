<?php
namespace app\teacherapi\controller;


use think\Controller;
use think\Db;
use think\Config;
use think\Request;


class Base extends Controller
{
    public $edu_teacher_info = '';
    public $teacher_info = '';


    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        //检查登陆
    }

    public function checkLogin(){
        $token = input('token');
        $info = Db::name('teacher')->where('token','=',$token)->find();
        if($info == null){
            echo json_encode(array(
                'status' => 101,
                'msg' => '登录超时，请重新登录！',
                'data' => array(
                )
            ));
            exit();
        }
        $this->teacher_info = $info;

        $edu_db = Db::connect(config('edu_database'));
        $edu_teacher_info = $edu_db->name('admin')->where('id','=',$info['edu_teacher_uid'])->find();
        if($edu_teacher_info == null){
            echo json_encode(array(
                'status' => 101,
                'msg' => '用户不存在请重新登陆',
                'data' => array()
            ));
            exit();
        }
        $this->edu_teacher_info = $edu_teacher_info;
    }
}
