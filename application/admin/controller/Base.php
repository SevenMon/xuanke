<?php
namespace app\admin\controller;


use think\Controller;
use think\Db;
use think\Config;
use think\Request;


class Base extends Controller
{
    public $edu_admin_info = '';
    public $admin_info = '';
    public $campus_arr = '';


    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        //检查登陆
        //$this->checkLogin();

    }

    public function checkLogin(){
        $token = input('token');
        $info = Db::name('admin')->where('token','=',$token)->find();
        if($info == null){
            echo json_encode(array(
                'status' => 101,
                'msg' => '登录超时，请重新登录！',
                'data' => array(
                )
            ));
            exit();
        }
        $this->admin_info = $info;

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
        $this->edu_admin_info = $edu_admin_info;
        $where = array();
        $where['admin_id'] = $edu_admin_info['uid'];

        $admin_limit = Db::name('admin_limit')->where(array('username' => $edu_admin_info['username']))->find();
        if($admin_limit == null){
            return json(array(
                'status' => 101,
                'msg' => '该用户没有权限登陆！',
                'data' => array()
            ));
        }

        //$this->campus_arr = $edu_db->name('admin_campus')->where($where)->column('campus_id');
        if(empty($admin_limit['campus_id']) || $admin_limit['campus_id'] == null){
            $this->campus_arr = $edu_db->name('admin_campus')->where($where)->column('campus_id');
        }else{
            $this->campus_arr = [$admin_limit['campus_id']];
        }
    }


}
