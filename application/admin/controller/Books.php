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
        '3' => '已取消',
        '4' => '未到课',
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
            $student_id_arr = Db::name('student')->where(array('edu_student_id'=>array('in',array_column($edu_student_list,'id'))))->column('id');
            $condition['student_id'] = array('in',$student_id_arr);
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
        if(!empty($cat_id3)){
            $course_where['cat_id3'] = $cat_id3;
        }
        $course_where['campus_id'] = array('in',$this->campus_arr);
        if(!empty($course_where)){
            $course_id_arr = Db::name('course')->where($course_where)->column('id');
            $condition['course_id'] = array('in',$course_id_arr);
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
            Db::commit();
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
            $student_id_arr = Db::name('student')->where(array('edu_student_id'=>array('in',array_column($edu_student_list,'id'))))->column('id');
            $condition['student_id'] = array('in',$student_id_arr);
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
        if(!empty($cat_id3)){
            $course_where['cat_id3'] = $cat_id3;
        }
        $course_where['campus_id'] = array('in',$this->campus_arr);
        if(!empty($course_where)){
            $course_id_arr = Db::name('course')->where($course_where)->column('id');
            $condition['course_id'] = array('in',$course_id_arr);
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
            //校区、课程分类、课程系列、课程名称、开班人数、满班人数、当前人数、学员姓名、手机号、年龄、预约状态
            $result_list[] = array(
                $value['course_info']['campus_info']['campus_name'],
                $value['course_info']['cat_id1_info']['name'],
                $value['course_info']['cat_id2_info']['name'],
                $value['course_info']['cat_id3_info']['name'],
                $value['course_info']['start_class_people_num'],
                $value['course_info']['max_people_num'],
                $value['course_info']['people_num'],
                $value['edu_student']['stu_name'],
                $value['edu_student']['stu_phone'],
                //$value['course_info']['teacher_main_info']['username'],
                $value['edu_student']['stu_age'],
                $value['status_str']
                /*$value['edu_student']['stu_name'],
                $value['edu_student']['stu_phone'],
                $value['course_info']['cat_id1_info']['name'],
                $value['course_info']['cat_id2_info']['name'],
                $value['course_info']['cat_id3_info']['name'],
                $value['course_info']['time'],
                $value['course_info']['teacher_main_info']['username'],
                $value['status_str']*/
            );
        }
        $csv = new Csv();
        //$csv_title = array('学员姓名','学员手机号','课程级别','课程系列','课程','上课时间','教师','到课状态');
        $csv_title = array('校区','课程级别','课程系列','课程名称','开班人数','满班人数','当前人数','学员姓名','手机号','年龄','预约状态');
        $csv->put_csv($result_list,$csv_title);
    }

    //调课  将预约好的课调整到其他的课程
    public function changeClass(){
        $book_id_str = input('book_id_str','');
        if(empty($book_id_str)){
            return json(array(
                'status' => -1,
                'msg' => '参数不完整！',
                'data' => array(
                )
            ));
        }

        $course_id = input('course_id');
        $edu_db = Db::connect(config('edu_database'));
        Db::startTrans();
        //try{
            //记录调课详情
            //变更后的课程信息
            $after['course_info'] = Db::name('course')->where(array('id' => $course_id))->find();
            //级别
            $after['cat_id1_info'] = Db::name('category')->where('id','=',$after['course_info']['cat_id1'])->find();
            //课程系列
            $after['cat_id2_info'] = Db::name('category')->where('id','=',$after['course_info']['cat_id2'])->find();
            //课程
            $after['cat_id3_info'] = Db::name('category')->where('id','=',$after['course_info']['cat_id3'])->find();
            //主教
            $after['teacher_main_info'] = $edu_db->name('admin')->where('uid','=',$after['course_info']['teacher_main_uid'])->find();

            $book_list = Db::name('book')->where(array('id' => array('in',explode(',',$book_id_str))))->select();
            foreach ($book_list as $value){
                $student_info = Db::name('student')->where(array('id' => $value['student_id']))->find();
                $edu_student_info = $edu_db->name('student_baseinfo')->where(array('id' => $student_info['edu_student_id']))->find();
                $before = array();
                $before['course_info'] = Db::name('course')->where(array('id' => $value['course_id']))->find();
                //级别
                $before['cat_id1_info'] = Db::name('category')->where('id','=',$before['course_info']['cat_id1'])->find();
                //课程系列
                $before['cat_id2_info'] = Db::name('category')->where('id','=',$before['course_info']['cat_id2'])->find();
                //课程
                $before['cat_id3_info'] = Db::name('category')->where('id','=',$before['course_info']['cat_id3'])->find();
                //主教
                $before['teacher_main_info'] = $edu_db->name('admin')->where('uid','=',$before['course_info']['teacher_main_uid'])->find();

                $data = array(
                    'student_id' => $student_info['id'],
                    'edu_student_id' => $student_info['edu_student_id'],
                    'student_phone' => $value['student_phone'],
                    'student_name' => $edu_student_info['stu_name'],
                    'before_course_id' => $value['course_id'],
                    'before_cat1_name' => $before['cat_id1_info']['name'],
                    'before_cat2_name' => $before['cat_id2_info']['name'],
                    'before_cat3_name' => $before['cat_id3_info']['name'],
                    'before_class_time' => date('Y-m-d',$before['course_info']['start_time']).' '.date('H:i',$before['course_info']['start_time']).'~'.date('H:i',$before['course_info']['end_time']),
                    'before_teacher_name' => $before['teacher_main_info']['account_name'],
                    'after_course_id' => $course_id,
                    'after_cat1_name' => $after['cat_id1_info']['name'],
                    'after_cat2_name' => $after['cat_id2_info']['name'],
                    'after_cat3_name' => $after['cat_id3_info']['name'],
                    'after_class_time' => date('Y-m-d',$after['course_info']['start_time']).' '.date('H:i',$after['course_info']['start_time']).'~'.date('H:i',$after['course_info']['end_time']),
                    'after_teacher_name' => $after['teacher_main_info']['account_name'],
                    'make_time' => time(),
                    'make_admin_id' => $this->admin_info['id'],
                    'make_admin_uid' => $this->admin_info['edu_uid'],
                    'make_admin_name' => $this->edu_admin_info['account_name']
                );
                Db::name('change_class')->insert($data);

            }

            Db::name('book')->where(array('id' => array('in',explode(',',$book_id_str))))->update(array('course_id' => $course_id));
            Db::commit();
            return json(array(
                'status' => 1,
                'msg' => '调课成功',
                'data' => array()
            ));
        //}catch (Exception $e){
            Db::rollback();
            return json(array(
                'status' => -1,
                'msg' => '调课失败',
                'data' => array()
            ));
        //}
    }

    //调课详情
    public function changeClassDetail(){
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
            $student_id_arr = Db::name('student')->where(array('edu_student_id'=>array('in',array_column($edu_student_list,'id'))))->column('id');
            $condition['student_id'] = array('in',$student_id_arr);
        }
        $start_time = input('start_time','');
        $end_time = input('end_time','');
        if(!empty($end_time) && !empty($end_time)){
            $course['make_time'] = array('between',array(strtotime($start_time),strtotime($end_time)));
        }
        $page = input('page',1);
        $limit = config('admin_page_limit');
        $change_class_num = Db::name('change_class')->where($condition)->count();
        $change_class_list = Db::name('change_class')->where($condition)->order('id desc')->page($page,$limit)->select();
        $page = array(
            'all_num' => $change_class_num,
            'limit' => $limit,
            'current_page' => $page,
            'all_page' => ceil($change_class_num/$limit),
        );
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => array(
                'book_list' => $change_class_list,
                'page' => $page,
            )
        ));
    }


    //调课详情
    public function changeClassDetailExport(){
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
            $student_id_arr = Db::name('student')->where(array('edu_student_id'=>array('in',array_column($edu_student_list,'id'))))->column('id');
            $condition['student_id'] = array('in',$student_id_arr);
        }
        $start_time = input('start_time','');
        $end_time = input('end_time','');
        if(!empty($end_time) && !empty($end_time)){
            $course['make_time'] = array('between',array(strtotime($start_time),strtotime($end_time)));
        }
        $change_class_list = Db::name('change_class')->where($condition)->order('id desc')->select();
        $result_list = array();
        foreach ($change_class_list as $value){
            $result_list[] = array(
                $value['student_name'],
                $value['student_phone'],
                $value['before_cat1_name'],
                $value['before_cat2_name'],
                $value['before_cat3_name'],
                $value['before_class_time'],
                $value['before_teacher_name'],
                $value['after_cat1_name'],
                $value['after_cat2_name'],
                $value['after_cat3_name'],
                $value['after_class_time'],
                $value['after_teacher_name'],
                date('Y-m-d H:s:i',$value['make_time']),
                $value['make_admin_name'],
            );
        }

        $csv = new Csv();
        $csv_title = array('学员姓名','学员手机号','原课程级别','原课程系列','原课程','上课时间','教师',
            '调整后课程级别','调整后课程系列','调整后课程','上课时间','教师','操作时间','操作人');
        $csv->put_csv($result_list,$csv_title);
    }
}
