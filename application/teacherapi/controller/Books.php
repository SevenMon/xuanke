<?php
namespace app\api\controller;

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
        $where = array();
        $where['student_id'] = $this->student_info['id'];
        $where['status'] = array('gt',0);
        $book_list = Db::name('book')->where($where)->order('status asc,id desc')->select();
        foreach ($book_list as $key => &$value){
            //课程
            $where = array();
            $where['id'] = $value['course_id'];
            $course_info = Db::name('course')->where($where)->find();
            if($course_info != null && !empty($course_info)){
                $value['course_info'] = $course_info;
                //课程系列
                $value['cat2_info'] = Db::name('category')->where('id','=',$course_info['cat_id2'])->find();
                $attr2 = Db::name('cat_attr')->where('cat_id','=',$course_info['cat_id2'])->find();
                $attr2['img'] =  getUrl().$attr2['img'];
                $attr2['banner_img'] =  getUrl().$attr2['banner_img'];
                $value['cat2_info']['attr'] = $attr2;
                //课程
                $value['cat3_info'] = Db::name('category')->where('id','=',$course_info['cat_id3'])->find();
                $attr3 = Db::name('cat_attr')->where('cat_id','=',$course_info['cat_id3'])->find();
                $attr3['img'] =  getUrl().$attr3['img'];
                $attr3['banner_img'] =  getUrl().$attr3['banner_img'];
                $value['cat3_info']['attr'] = $attr3;

                $value['class_start_end_time'] = timetostr($course_info['start_time'],$course_info['end_time']);
                $value['status_str'] = $this->status[$value['status']];
            }else{
                unset($book_list[$key]);
                continue;
            }
        }
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => array(
                'book_list' => $book_list,
                'edu_student_info' => $this->edu_student_info,
            )
        ));
    }

    public function book(){
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
        if(!canbook()){
            return json(array(
                'status' => -1,
                'msg' => '已过预约时间',
                'data' => array(
                )
            ));
        }
        if($course_info['people_num'] >= $course_info['max_people_num']){
            return json(array(
                'status' => -1,
                'msg' => '课程人数已满',
                'data' => array(
                )
            ));
        }

        $where = array();
        $where['student_id'] = $this->student_info['id'];
        $where['course_id'] = $course_info['id'];
        $book_info = Db::name('book')->where($where)->find();
        if($book_info){
            if($book_info['status'] == 1 || $book_info['status'] == 2){
                return json(array(
                    'status' => -1,
                    'msg' => '已预约过，不能再次预约！',
                    'data' => array(
                    )
                ));
            }else{
                $update_info = Db::name('book')->where('id','=',$book_info['id'])->update(array('status' => 1));
                if(empty($update_info)){
                    return json(array(
                        'status' => -1,
                        'msg' => '预约失败，稍后再试！',
                        'data' => array(
                        )
                    ));
                }else{
                    $where = array();
                    $where['id'] = $course_id;
                    Db::name('course')->where($where)->update(array('people_num' => ++$course_info['people_num']));
                    return json(array(
                        'status' => 1,
                        'msg' => '预约成功',
                        'data' => array(
                        )
                    ));
                }
            }
        }else{
            $data = array(
                'student_id' => $this->student_info['id'],
                'student_phone' => $this->edu_student_info['stu_phone'],
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
                $where = array();
                $where['id'] = $course_id;
                Db::name('course')->where($where)->update(array('people_num' => ++$course_info['people_num']));
                return json(array(
                    'status' => 1,
                    'msg' => '预约成功',
                    'data' => array(
                    )
                ));
            }
        }


    }

    public function cancelBook(){
        $book_id = input('id');
        $where = array();
        $where['student_id'] = $this->student_info['id'];
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

        if(!canbook()){
            return json(array(
                'status' => -1,
                'msg' => '已过预约时间',
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
            $cancel_book_info = Db::name('book')->where('id','=',$book_id)->update(array('status' => 3));
            if(empty($cancel_book_info)){
                Db::rollback();
                return json(array(
                    'status' => -1,
                    'msg' => '取消失败！',
                    'data' => array(
                    )
                ));
            }else{
                $where = array();
                $where['id'] = $book_info['course_id'];
                $update_course_info = Db::name('course')->where($where)->update(array('people_num' => --$course_info['people_num']));
                if(empty($update_course_info)){
                    Db::rollback();
                    return json(array(
                        'status' => -1,
                        'msg' => '取消失败！',
                        'data' => array(
                        )
                    ));
                }else{
                    Db::commit();
                    return json(array(
                        'status' => 1,
                        'msg' => '取消预约成功',
                        'data' => array(
                        )
                    ));
                }
            }
        }catch (Exception $e){
            Db::rollback();
            return json(array(
                'status' => -1,
                'msg' => '取消失败！',
                'data' => array(
                )
            ));
        }

    }

}
