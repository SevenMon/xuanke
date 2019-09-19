<?php
namespace app\api\controller;

use think\Db;

class Books extends Base
{

    public $status = array(
        '1' => '预约成功',
        '2' => '已到课',
        '3' => '已取消'
    );
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->checkLogin();
    }

    public function bookList(){
        $where = array();
        $where[] = array('uid','=',$this->user_info['id']);
        $book_list = Db::name('book')->where($where)->order('status asc,id desc')->select();
        foreach ($book_list as &$value){
            $value['class_start_end_time'] = timetostr($value['start_time'],$value['end_time']);
            $value['status_str'] = $this->status[$value['status']];
        }
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => $book_list,
        ));
    }

    public function book(){
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
        $where = array();
        $where[] = array('uid','=',$this->user_info['id']);
        $where[] = array('course_id','=',$course_info['id']);
        $book_info = Db::name('book')->where($where)->column('id','course_id');
        if($book_info){
            return json(array(
                'status' => -1,
                'msg' => '已预约过，不能再次预约！',
                'data' => array(
                )
            ));
        }
        $data = array(
            'uid' => $this->user_info['id'],
            'student_phone' => $this->edu_user_info['account_phone'],
            'course_id' => $course_info['id']
        );
        $add_info = Db::name('book')->insertGetId($data);
        if(empty($add_info)){
            return json(array(
                'status' => -1,
                'msg' => '预约失败，稍后再试！',
                'data' => array(
                )
            ));
        }else{
            return json(array(
                'status' => 1,
                'msg' => '预约成功',
                'data' => array(
                )
            ));
        }

    }

}
