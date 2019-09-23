<?php
namespace app\api\controller;


use think\Controller;
use think\Db;
use think\Config;
use think\Request;


class Base extends Controller
{
    public $edu_student_info = '';
    public $student_info = '';


    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        //检查登陆

    }

    public function checkLogin(){
        $token = input('token');
        $info = Db::name('student')->where('token','=',$token)->find();
        if($info == null){
            echo json_encode(array(
                'status' => 101,
                'msg' => '登录超时，请重新登录！',
                'data' => array(
                )
            ));
            exit();
        }
        $this->student_info = $info;

        $edu_db = Db::connect(config('edu_database'));
        $edu_student_info = $edu_db->name('student_baseinfo')->where('id','=',$info['edu_student_id'])->find();
        if($edu_student_info == null){
            echo json_encode(array(
                'status' => 101,
                'msg' => '用户不存在请重新登陆',
                'data' => array()
            ));
            exit();
        }
        $this->edu_student_info = $edu_student_info;
    }


}
