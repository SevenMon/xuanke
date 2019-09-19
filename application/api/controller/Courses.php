<?php
namespace app\api\controller;

use think\Db;

class Courses extends Base
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->checkLogin();
    }

    public function index(){
        $page = input('page',1);
        $limit = config('api_page_limit');

        $rusult = array();
        $rusult['edu_user_info'] = $this->edu_user_info;
        $rusult['user_info'] = $this->user_info;

        $where = array();
        $where[] = array('status','=',1);
        $where[] = array('campus_id','in',$this->campus_arr);
        $course_all_num = Db::name('course')->where($where)->count();
        $course_list = Db::name('course')->where($where)->order('sort desc,id des ')->page($page,$limit)->select();

        $where = array();
        $where[] = array('uid','=',$this->user_info['id']);
        $where[] = array('course_id','in',array_column($course_list,'id'));
        $book_list = Db::name('book')->where($where)->column('id','course_id');

        foreach ($course_list as &$value){
            if(isset($book_list[$value['id']])){
                $value['book_status'] = '已预约';
                $value['book_status'] = 1;
            }elseif ($value['people_num'] >=  $value['max_people_num']){
                $value['book_status'] = '已满';
                $value['book_status'] = 2;
            }else{
                $value['book_status'] = '剩余'.($value['max_people_num']-$value['people_num']).'个名额';
                $value['book_status'] = 3;
            }
            $value['class_start_end_time'] = timetostr($value['start_time'],$value['end_time']);

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
        $where[] = array('id','=',$course_id);
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
        $where = array();
        $where[] = array('uid','=',$this->user_info['id']);
        $where[] = array('course_id','=',$course_info['id']);
        $book_info = Db::name('book')->where($where)->column('id','course_id');
        if($book_info){
            $result['book_status'] = '已预约';
            $result['book_status'] = 1;
        }elseif ($course_info['people_num'] >=  $course_info['max_people_num']){
            $result['book_status'] = '已满';
            $result['book_status'] = 2;
        }
        $result['foot_title'] = '剩余'.($course_info['max_people_num']-$course_info['people_num']).'个名额';
        $result['class_start_end_time'] = timetostr($course_info['start_time'],$course_info['end_time']);
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => $result
        ));

    }
}
