<?php
namespace app\admin\controller;

use think\Db;
use think\Exception;
use think\Request;

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
        $edu_db = Db::connect(config('edu_database'));
        $condition = array();
        $student_where = array();
        $student_name = input('student_name','');
        if(!empty($student_name)){
            $student_where['stu_name'] = array('like','%'.$student_name.'%');
        }
        $phone = input('phone','');
        if(!empty($phone)){
            $student_where['stu_phone'] = array('like','%'.$phone.'%');
        }
        if(!empty($student_where)){
            $edu_student_list = $edu_db->name('student_baseinfo')->where($student_where)->select();
            if(!empty($edu_student_list)){
                $student_id_arr = Db::name('student')->where('edu_student_id',array('in',array_column($edu_student_list,'id')))->column('id');
                if(!empty($student_id_arr)){
                    $condition['student'] = array('in',$student_id_arr);
                }
            }
        }

        $course_where = array();
        $start_time = input('start_time','');
        $end_time = input('end_time','');
        if(!empty($end_time) && !empty($end_time)){
            $course_where['start_time'] = array('between',array(strtotime($start_time),strtotime($end_time)));
        }
        $cat_id1 = input('cat_id1','');
        if(!empty($cat_id1)){
            $course_where['cat_id1'] = $cat_id1;
        }
        $cat_id2 = input('cat_id2','');
        if(!empty($cat_id2)){
            $course_where['cat_id2'] = $cat_id2;
        }
        $cat_id3 = input('cat_id3','');
        if(!empty($cat_id2)){
            $course_where['cat_id3'] = $cat_id3;
        }
        $condition['course_id'] = array('in',$this->campus_arr);
        if(!empty($course_where)){
            $course_id_arr = Db::name('course')->where($course_where)->column('id');
            if(!empty($course_id_arr)){
                $condition['course_id'] = array('in',array_merge($course_id_arr,$this->campus_arr));
            }
        }
        $page = input('page',1);
        $limit = config('admin_page_limit');
        $condition['status'] = array('gt',0);
        $course_all_num = Db::name('book')->where($condition)->count();
        $book_list = Db::name('book')->where($condition)->order('id desc')->page($page,$limit)->select();

        foreach ($book_list as $key => &$value){
            //课程
            $where = array();
            $where['id'] = $value['course_id'];
            $course_info = Db::name('course')->where($where)->find();
            //级别
            $course_info['cat_id1_info'] = Db::name('category')->where('id','=',$course_info['cat_id1'])->find();
            //课程系列
            $course_info['cat_id2_info'] = Db::name('category')->where('id','=',$course_info['cat_id2'])->find();
            //课程
            $course_info['cat_id3_info'] = Db::name('category')->where('id','=',$course_info['cat_id3'])->find();
            //主教
            $course_info['teacher_main_info'] = $edu_db->name('admin')->where('uid','=',$course_info['teacher_main_uid'])->find();
            //助教
            $course_info['teacher_assist_info'] = $edu_db->name('admin')->where('uid','=',$course_info['teacher_assist_uid'])->find();
            //校区
            $course_info['campus_info'] = $edu_db->name('campus')->where('id','=',$course_info['campus_id'])->find();

            $course_info['start_time_form'] = date('Y-m-d H:i:s',$course_info['start_time']);
            $course_info['end_time_form'] = date('Y-m-d H:i:s',$course_info['end_time']);
            $course_info['time'] = date('Y-m-d',$course_info['start_time']).' '.date('H:i',$course_info['start_time']).'~'.date('H:i',$course_info['end_time']);
            $value['course_info'] = $course_info;
            $value['status_str'] = $this->status[$value['status']];
            $student_info = Db::name('student')->where(array('id' => $value['student_id']))->find();
            $value['edu_student'] = $edu_db->name('student_baseinfo')->where(array('id'=>$student_info['edu_student_id']))->find();
        }
        $page = array(
            'all_num' => $course_all_num,
            'limit' => $limit,
            'current_page' => $page,
            'all_page' => ceil($course_all_num/$limit),
        );
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => array(
                'book_list' => $book_list,
                'page' => $page,
            )
        ));
    }

    public function changeBook(){
        $book_id = input('id');
        $status = input('status');//2或者3
        $where = array();
        $where['id'] = $book_id;
        $book_info = Db::name('book')->where($where)->find();
        if($book_info == null || empty($book_info) || $book_info['status'] != 1){
            return json(array(
                'status' => -1,
                'msg' => '数据错误',
                'data' => array(
                )
            ));
        }

        $where = array();
        $where['id'] = $book_info['course_id'];
        $course_info = Db::name('course')->where($where)->find();
        if(empty($course_info)){
            return json(array(
                'status' => -1,
                'msg' => '课程不存在',
                'data' => array(
                )
            ));
        }

        Db::startTrans();
        try{
            Db::name('book')->where('id','=',$book_id)->update(array('status' => $status));
            if($status == 3){
                Db::name('course')->where($where)->update(array('people_num' => --$course_info['people_num']));
            }
            return json(array(
                'status' => 1,
                'msg' => '变更成功',
                'data' => array(
                )
            ));
        }catch (Exception $e){
            Db::rollback();
            return json(array(
                'status' => -1,
                'msg' => '变更失败！',
                'data' => array(
                )
            ));
        }

    }

    public function exportList(){
        $edu_db = Db::connect(config('edu_database'));
        $condition = array();
        $student_where = array();
        $student_name = input('student_name','');
        if(!empty($student_name)){
            $student_where['stu_name'] = array('like','%'.$student_name.'%');
        }
        $phone = input('phone','');
        if(!empty($phone)){
            $student_where['stu_phone'] = array('like','%'.$phone.'%');
        }
        if(!empty($student_where)){
            $edu_student_list = $edu_db->name('student_baseinfo')->where($student_where)->select();
            if(!empty($edu_student_list)){
                $student_id_arr = Db::name('student')->where('edu_student_id',array('in',array_column($edu_student_list,'id')))->column('id');
                if(!empty($student_id_arr)){
                    $condition['student'] = array('in',$student_id_arr);
                }
            }
        }

        $course_where = array();
        $start_time = input('start_time','');
        $end_time = input('end_time','');
        if(!empty($end_time) && !empty($end_time)){
            $course_where['start_time'] = array('between',array(strtotime($start_time),strtotime($end_time)));
        }
        $cat_id1 = input('cat_id1','');
        if(!empty($cat_id1)){
            $course_where['cat_id1'] = $cat_id1;
        }
        $cat_id2 = input('cat_id2','');
        if(!empty($cat_id2)){
            $course_where['cat_id2'] = $cat_id2;
        }
        $cat_id3 = input('cat_id3','');
        if(!empty($cat_id2)){
            $course_where['cat_id3'] = $cat_id3;
        }

        $condition['course_id'] = array('in',$this->campus_arr);
        if(!empty($course_where)){
            $course_id_arr = Db::name('course')->where($course_where)->column('id');
            if(!empty($course_id_arr)){
                $condition['course_id'] = array('in',array_merge($course_id_arr,$this->campus_arr));
            }
        }

        $condition['status'] = array('gt',0);
        $book_list = Db::name('book')->where($condition)->order('id desc')->select();
        $result_list = array();
        foreach ($book_list as $key => &$value){
            //课程
            $where = array();
            $where['id'] = $value['course_id'];
            $course_info = Db::name('course')->where($where)->find();
            //级别
            $course_info['cat_id1_info'] = Db::name('category')->where('id','=',$course_info['cat_id1'])->find();
            //课程系列
            $course_info['cat_id2_info'] = Db::name('category')->where('id','=',$course_info['cat_id2'])->find();
            //课程
            $course_info['cat_id3_info'] = Db::name('category')->where('id','=',$course_info['cat_id3'])->find();
            //主教
            $course_info['teacher_main_info'] = $edu_db->name('admin')->where('uid','=',$course_info['teacher_main_uid'])->find();
            //助教
            $course_info['teacher_assist_info'] = $edu_db->name('admin')->where('uid','=',$course_info['teacher_assist_uid'])->find();
            //校区
            $course_info['campus_info'] = $edu_db->name('campus')->where('id','=',$course_info['campus_id'])->find();

            $course_info['start_time_form'] = date('Y-m-d H:i:s',$course_info['start_time']);
            $course_info['end_time_form'] = date('Y-m-d H:i:s',$course_info['end_time']);
            $course_info['time'] = date('Y-m-d',$course_info['start_time']).' '.date('H:i',$course_info['start_time']).'~'.date('H:i',$course_info['end_time']);
            $value['course_info'] = $course_info;
            $value['status_str'] = $this->status[$value['status']];

            $student_info = Db::name('student')->where(array('id' => $value['student_id']))->find();
            $value['edu_student'] = $edu_db->name('student_baseinfo')->where(array('id'=>$student_info['edu_student_id']))->find();
            $result_list[] = array(
                $value['edu_student']['stu_name'],
                $value['edu_student']['stu_phone'],
                $value['cat_id1_info']['name'],
                $value['cat_id2_info']['name'],
                $value['cat_id3_info']['name'],
                $value['course_info']['time'],
                $value['teacher_main_info']['username'],
                $value['status_str']
            );
        }
        $book_list;
        $csv = new Csv();
        $csv_title = array('学员姓名','学员手机号','课程级别','课程系列','课程','上课时间','教师','到课状态');
        $csv->put_csv($result_list,$csv_title);

    }

}
