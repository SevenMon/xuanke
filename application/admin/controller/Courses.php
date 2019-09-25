<?php
namespace app\admin\controller;

use think\Db;
use think\Exception;
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

            $value['start_time_form'] = date('Y-m-d H:i:s',$value['start_time']);
            $value['end_time_form'] = date('Y-m-d H:i:s',$value['end_time']);
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
        $edu_db = Db::connect(config('edu_database'));
        $course_id = input('id');
        $where = array();
        $where['id'] = $course_id;
        $where['status'] = array('neq',0);
        $where['campus_id'] = array('in',$this->campus_arr);
        $course_info = Db::name('course')->where($where)->find();
        if(empty($course_info)){
            return json(array(
                'status' => -1,
                'msg' => '课程不存在',
                'data' => array(
                )
            ));
        }
        //级别
        $course_info['cat_id1_info'] = Db::name('category')->where('id','=',$course_info['cat_id1'])->find();
        //课程系列
        $course_info['cat_id2_info'] = Db::name('category')->where('id','=',$course_info['cat_id2'])->find();
        //主教
        $course_info['teacher_main_info'] = $edu_db->name('admin')->where('uid','=',$course_info['teacher_main_uid'])->find();
        //助教
        $course_info['teacher_assist_info'] = $edu_db->name('admin')->where('uid','=',$course_info['teacher_assist_uid'])->find();
        //校区
        $course_info['campus_info'] = $edu_db->name('campus')->where('id','=',$course_info['campus_id'])->find();

        $course_info['start_time_form'] = date('Y-m-d H:i:s',$course_info['start_time']);
        $course_info['end_time_form'] = date('Y-m-d H:i:s',$course_info['end_time']);
        $course_info['time'] = date('Y-m-d',$course_info['start_time']).' '.date('H:i',$course_info['start_time']).'~'.date('H:i',$value['end_time']);
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => array(
                'course_info'=>$course_info
            )
        ));

    }

    public function store(){
        $edu_db = Db::connect(config('edu_database'));
        $name = input('name','');
        if(empty($name)){
            return json(array(
                'status' => -1,
                'msg' => '课程名不能为空',
                'data' => array(
                )
            ));
        }

        $cat_id1 = input('cat_id1','');
        $cat_info1 = Db::name('category')->where('id','=',$cat_id1)->find();
        if($cat_info1 == null){
            return json(array(
                'status' => -1,
                'msg' => '课程级别不存在',
                'data' => array(
                )
            ));
        }

        $cat_id2 = input('cat_id2','');
        $cat_info2 = Db::name('category')->where('id','=',$cat_id2)->find();
        if($cat_info2 == null){
            return json(array(
                'status' => -1,
                'msg' => '课程系列不存在',
                'data' => array(
                )
            ));
        }
        $start_time = input('start_time','');
        $end_time = input('end_time','');
        if(empty($start_time) || empty($end_time) || $start_time > $end_time){
            return json(array(
                'status' => -1,
                'msg' => '上课时间不合法，请重新输入',
                'data' => array(
                )
            ));
        }

        $teacher_main_uid = input('teacher_main_uid','');
        $teacher_main_info = $edu_db->name('admin')->where('uid','=',$teacher_main_uid)->find();
        if($teacher_main_info == null){
            return json(array(
                'status' => -1,
                'msg' => '主教老师不存在',
                'data' => array(
                )
            ));
        }

        $teacher_assist_uid = input('teacher_assist_uid','');
        $teacher_assist_info = $edu_db->name('admin')->where('uid','=',$teacher_assist_uid)->find();
        if($teacher_assist_info == null){
            return json(array(
                'status' => -1,
                'msg' => '助教老师不存在',
                'data' => array(
                )
            ));
        }
        $sort = input('sort',0);
        $data = array(
            'cat_id1' => $cat_id1,
            'cat_id2' => $cat_id2,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'teacher_main_uid' => $teacher_main_uid,
            'teacher_assist_uid' => $teacher_assist_uid,
            'campuse_id' => $this->campus_arr[0],
            'sort' => $sort
        );

        Db::name('course')->insertGetId($data);
        return json(array(
            'status' => 1,
            'msg' => '添加成功',
            'data' => array()
        ));

    }

    public function update(){
        $course_id = input('id');
        $where = array();
        $where['status'] = array('neq',0);
        $where['campus_id'] = array('in',$this->campus_arr);
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
        $edu_db = Db::connect(config('edu_database'));
        $name = input('name','');
        if(empty($name)){
            return json(array(
                'status' => -1,
                'msg' => '课程名不能为空',
                'data' => array(
                )
            ));
        }

        $cat_id1 = input('cat_id1','');
        $cat_info1 = Db::name('category')->where('id','=',$cat_id1)->find();
        if($cat_info1 == null){
            return json(array(
                'status' => -1,
                'msg' => '课程级别不存在',
                'data' => array(
                )
            ));
        }

        $cat_id2 = input('cat_id2','');
        $cat_info2 = Db::name('category')->where('id','=',$cat_id2)->find();
        if($cat_info2 == null){
            return json(array(
                'status' => -1,
                'msg' => '课程系列不存在',
                'data' => array(
                )
            ));
        }
        $start_time = input('start_time','');
        $end_time = input('end_time','');
        if(empty($start_time) || empty($end_time) || $start_time > $end_time){
            return json(array(
                'status' => -1,
                'msg' => '上课时间不合法，请重新输入',
                'data' => array(
                )
            ));
        }

        $teacher_main_uid = input('teacher_main_uid','');
        $teacher_main_info = $edu_db->name('admin')->where('uid','=',$teacher_main_uid)->find();
        if($teacher_main_info == null){
            return json(array(
                'status' => -1,
                'msg' => '主教老师不存在',
                'data' => array(
                )
            ));
        }

        $teacher_assist_uid = input('teacher_assist_uid','');
        $teacher_assist_info = $edu_db->name('admin')->where('uid','=',$teacher_assist_uid)->find();
        if($teacher_assist_info == null){
            return json(array(
                'status' => -1,
                'msg' => '助教老师不存在',
                'data' => array(
                )
            ));
        }
        $sort = input('sort',0);
        $data = array(
            'cat_id1' => $cat_id1,
            'cat_id2' => $cat_id2,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'teacher_main_uid' => $teacher_main_uid,
            'teacher_assist_uid' => $teacher_assist_uid,
            'campuse_id' => $this->campus_arr[0],
            'sort' => $sort
        );
        try{
            Db::name('course')->where('id','=',$course_id)->update($data);
            return json(array(
                'status' => 1,
                'msg' => '修改成功',
                'data' => array()
            ));
        }catch (Exception $e){
            return json(array(
                'status' => -1,
                'msg' => '修改失败',
                'data' => array()
            ));
        }
    }

    public function destroy(){
        $course_id = input('id');
        $where = array();
        $where['status'] = array('neq',0);
        $where['campus_id'] = array('in',$this->campus_arr);
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
        try{
            Db::name('course')->where('id','=',$course_id)->update(array('status' => 0));
            return json(array(
                'status' => 1,
                'msg' => '删除成功',
                'data' => array()
            ));
        }catch (Exception $e){
            return json(array(
                'status' => -1,
                'msg' => '删除失败',
                'data' => array()
            ));
        }
    }

    public function getTeacherList(){
        $edu_db = Db::connect(config('edu_database'));
        $teacher_list = $edu_db->name('admin')->where('state','=',1)->select();
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => array(
                'teacher_list' => $teacher_list
            )
        ));
    }
}
