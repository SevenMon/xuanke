<?php
namespace app\admin\controller;

use think\Db;
use think\Request;

class Courses extends Base
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->checkLogin();
    }

    public function index(){
        $edu_db = Db::connect(config('edu_database'));
        $page = input('page',1);
        $limit = config('api_page_limit');
        $condition = array();
        $condition['status'] = array('neq',0);
        $condition['campus_id'] = array('in',$this->campus_arr);
        $cat_id1 = input('cat_id1','');
        if(!empty($cat_id1)){
            $condition['cat_id1'] = $cat_id1;
        }
        $cat_id2 = input('cat_id2','');
        if(!empty($cat_id2)){
            $condition['cat_id1'] = $cat_id2;
        }
        $name = input('name','');
        if(!empty($name)){
            $condition['name'] = array('like','%'.$name.'%');
        }
        $course_all_num = Db::name('course')->where($condition)->count();

        $list = Db::name('course')->where($condition)->order('id desc')->select();
        foreach ($list as &$value){
            //级别
            $value['cat_id1_info'] = Db::name('category')->where('id','=',$value['cat_id1'])->find();
            //课程系列
            $value['cat_id2_info'] = Db::name('category')->where('id','=',$value['cat_id2'])->find();
            //主教
            $value['teacher_main_info'] = $edu_db->name('admin')->where('uid','=',$value['teacher_main_uid'])->find();
            //助教
            $value['teacher_assist_info'] = $edu_db->name('admin')->where('uid','=',$value['teacher_assist_uid'])->find();
            //校区
            $value['campus_info'] = $edu_db->name('campus')->where('id','=',$value['campus_id'])->find();

            $value['start_time'] = date('Y-m-d H:i:s',$value['start_time']);
            $value['end_time'] = date('Y-m-d H:i:s',$value['end_time']);
            $value['time'] = date('Y-m-d',$value['start_time']).' '.date('H:i',$value['start_time']).'~'.date('H:i',$value['end_time']);
        }
        $result['list'] = $list;
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

    public function show(){
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
        //$result['hxnl'] = '分享交往';
        //$result['skdd'] = '烘焙区角';
        //$result['kcjj'] = '大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字大概五十个字';

        $where = array();
        $where['student_id'] = $this->student_info['id'];
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
        $result['edu_user_info'] = $this->edu_student_info;
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => $result
        ));
    }
}
