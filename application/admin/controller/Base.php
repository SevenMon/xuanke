<?php
namespace app\admin\controller;


use think\Controller;
use think\Db;
use think\Config;
use think\Request;


class Base extends Controller
{
    public $edu_user_info = '';
    public $user_info = '';
    public $campus_arr = '';


    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        //检查登陆
        //$this->checkLogin();

    }

    public function checkLogin(){
        $token = input('admin_token');
        $info = Db::name('admin')->where('admin_token','=',$token)->find();
        if($info == null){
            echo json_encode(array(
                'status' => 101,
                'msg' => '登录超时，请重新登录！',
                'data' => array(
                )
            ));
            exit();
        }
        $this->user_info = $info;

        $edu_db = Db::connect(config('edu_database'));
        $edu_admin_info = $edu_db->name('admin')->where('uid','=',$info['edu_uid'])->find();
        if($edu_admin_info == null){
            echo json_encode(array(
                'status' => 101,
                'msg' => '用户不存在请重新登陆',
                'data' => array()
            ));
            exit();
        }
        $this->edu_user_info = $edu_admin_info;
        $where = array();
        $where['admin_id'] = $edu_admin_info['uid'];
        $this->campus_arr = $edu_db->name('admin_campus')->where($where)->column('campus_id');
    }


}
