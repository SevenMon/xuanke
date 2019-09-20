<?php
namespace app\api\controller;

use think\Controller;
use think\Db;
use think\Request;

class Courses extends Controller
{
    public $edu_user_info = '';
    public $user_info = '';
    public $campus_arr = '';

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->checkLogin();
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

    public function index(){
        $page = input('page',1);
        $limit = config('api_page_limit');

        $result = array();
        $result['edu_user_info'] = $this->edu_user_info;
        $result['user_info'] = $this->user_info;

        $where = array();
        $where['status'] = 1;
        $where['campus_id'] = array('in',$this->campus_arr);
        $course_all_num = Db::name('course')->where($where)->count();
        $course_list = Db::name('course')->where($where)->order('sort desc,id des ')->page($page,$limit)->select();

        $where = array();
        $where['uid'] = $this->user_info['id'];
        $where['course_id'] = array('in',array_column($course_list,'id'));
        $book_list = Db::name('book')->where($where)->column('id','course_id');

        foreach ($course_list as &$value){
            if(isset($book_list[$value['id']])){
                $value['book_status'] = '已预约';
                $value['book_status_code'] = 1;
            }elseif ($value['people_num'] >=  $value['max_people_num']){
                $value['book_status'] = '已满';
                $value['book_status_code'] = 2;
            }else{
                $value['book_status'] = '剩余'.($value['max_people_num']-$value['people_num']).'个名额';
                $value['book_status_code'] = 3;
            }
            $value['class_start_end_time'] = timetostr($value['start_time'],$value['end_time']);

            //课程系列
            $value['cat2_info'] = Db::name('category')->where('id','=',$value['cat_id2'])->find();
            $attr = Db::name('cat_attr')->where('cat_id','=',$value['cat_id2'])->find();
            $value['cat2_info']['attr'] =  $attr;

        }
        $result['course_list'] = $course_list;

        $result['page'] = array(
            'all_num' => $course_all_num,
            'limit' => $limit,
            'current_page' => $page,
            'all_page' => ceil($course_all_num/$limit),
        );
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => $result
        ));

    }

    public function detail(){
        $course_id = input('id');
        $where = array();
        $where['id'] = $course_id;
        $course_info = Db::name('course')->where($where)->find();
        if(empty($course_info)){
            return json(array(
                'status' => -1,
                'msg' => '课程不存在',
                'data' => array(
                )
            ));
        }
        $result['course_info'] = $course_info;
        $result['hxnl'] = '分享交往';
        $result['skdd'] = '烘焙区角';
        $result['kcjj'] = '大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字';

        $where = array();
        $where['uid'] = $this->user_info['id'];
        $where['course_id'] = $course_info['id'];
        $book_info = Db::name('book')->where($where)->column('id','course_id');
        if($book_info){
            $result['book_status'] = '已预约';
            $result['book_status_code'] = 1;
        }elseif ($course_info['people_num'] >=  $course_info['max_people_num']){
            $result['book_status'] = '已满';
            $result['book_status_code'] = 2;
        }else{
            $result['book_status'] = '预约';
            $result['book_status_code'] = 3;
        }

        //课程系列
        $result['cat2_info'] = Db::name('category')->where('id','=',$course_info['cat_id2'])->find();
        $attr = Db::name('cat_attr')->where('cat_id','=',$course_info['cat_id2'])->find();
        $result['cat2_info']['attr'] = $attr;

        $result['foot_title'] = '剩余'.($course_info['max_people_num']-$course_info['people_num']).'个名额';
        $result['class_start_end_time'] = timetostr($course_info['start_time'],$course_info['end_time']);
        $result['edu_user_info'] = $this->edu_user_info;
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => $result
        ));
    }
}
