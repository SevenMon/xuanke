<?php
namespace app\api\controller;


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

    }

    public function checkLogin(){
        $token = input('token');
        $info = Db::name('admin')->where('token','=',$token)->find();
        if(empty($info)){
            return json(array(
                'status' => 101,
                'msg' => '请登陆之后使用！',
                'data' => array(
                )
            ));
        }
        $this->user_info = $info;

        $edu_db = Db::connect(config('edu_database'));
        $edu_admin_info = $edu_db->name('admin')->where('uid','=',$info['edu_uid'])->find();
        if(empty($edu_admin_info)){
            return json(array(
                'status' => 101,
                'msg' => '用户不存在请重新登陆',
                'data' => array()
            ));
        }
        $this->edu_user_info = $edu_admin_info;
        $where = arrray();
        $where['admin_id'] = $edu_admin_info['uid'];
        $this->campus_arr = $edu_db->name('admin_campus')->where($where)->column('campus_id');
    }


}
