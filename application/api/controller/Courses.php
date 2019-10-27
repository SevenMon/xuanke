<?php
namespace app\api\controller;

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
        $page = input('page',1);
        $limit = config('api_page_limit');

        $result = array();
        $result['edu_student_info'] = $this->edu_student_info;
        $result['student_info'] = $this->student_info;

        $where = array();
        $where['status'] = 1;
        $where['cat_id1'] = $this->limit_student_info['cat_id1'];
        $where['campus_id'] = $this->edu_student_info['campus_id'];
        $course_all_num = Db::name('course')->where($where)->count();
        $course_list = Db::name('course')->where($where)->order('sort desc,id des ')->page($page,$limit)->select();

        $where = array();
        $where['student_id'] = $this->student_info['id'];
        $where['course_id'] = array('in',array_column($course_list,'id'));
        $book_list = Db::name('book')->where($where)->column('*','course_id');

        foreach ($course_list as &$value){
            if(isset($book_list[$value['id']])){
                if($book_list[$value['id']]['status'] == 1){
                    $value['book_status'] = '已预约';
                    $value['book_status_code'] = 1;
                }elseif ($book_list[$value['id']]['status'] == 2){
                    $value['book_status'] = '已到课';
                    $value['book_status_code'] = 4;
                }else{
                    $value['book_status'] = '剩余'.($value['max_people_num']-$value['people_num']).'个名额';
                    $value['book_status_code'] = 3;
                }
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
            $attr2 = Db::name('cat_attr')->where('cat_id','=',$value['cat_id2'])->find();
            $attr2['img'] =  getUrl().$attr2['img'];
            $attr2['banner_img'] =  getUrl().$attr2['banner_img'];
            $value['cat2_info']['attr'] =  $attr2;


            //课程
            $value['cat3_info'] = Db::name('category')->where('id','=',$value['cat_id3'])->find();
            $attr3 = Db::name('cat_attr')->where('cat_id','=',$value['cat_id3'])->find();
            $attr3['img'] =  getUrl().$attr3['img'];
            $attr3['banner_img'] =  getUrl().$attr3['banner_img'];
            $value['cat3_info']['attr'] =  $attr3;


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
        $where['cat_id1'] = $this->limit_student_info['cat_id1'];
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
        $book_info = Db::name('book')->where($where)->find();
        if($book_info){
            if($book_info['status'] == 1){
                $result['book_status'] = '已预约';
                $result['book_status_code'] = 1;
            }elseif ($book_info['status'] == 2){
                $result['book_status'] = '已到课';
                $result['book_status_code'] = 4;
            }else{
                $result['book_status'] = '预约';
                $result['book_status_code'] = 3;
            }
        }elseif ($course_info['people_num'] >=  $course_info['max_people_num']){
            $result['book_status'] = '已满';
            $result['book_status_code'] = 2;
        }else{
            $result['book_status'] = '预约';
            $result['book_status_code'] = 3;
        }

        //课程系列
        $result['cat2_info'] = Db::name('category')->where('id','=',$course_info['cat_id2'])->find();
        $attr2 = Db::name('cat_attr')->where('cat_id','=',$course_info['cat_id2'])->find();
        $attr2['img'] =  getUrl().$attr2['img'];
        $attr2['banner_img'] =  getUrl().$attr2['banner_img'];
        $result['cat2_info']['attr'] = $attr2;

        //课程
        $result['cat3_info'] = Db::name('category')->where('id','=',$course_info['cat_id3'])->find();
        $attr3 = Db::name('cat_attr')->where('cat_id','=',$course_info['cat_id3'])->find();
        $attr3['img'] =  getUrl().$attr3['img'];
        $attr3['banner_img'] =  getUrl().$attr3['banner_img'];
        $result['cat3_info']['attr'] = $attr3;

        $result['foot_title'] = '剩余'.($course_info['max_people_num']-$course_info['people_num']).'个名额';
        $result['class_start_end_time'] = timetostr($course_info['start_time'],$course_info['end_time']);
        $result['edu_user_info'] = $this->edu_student_info;
        $result['book_info'] = $book_info;
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => $result
        ));
    }

    public function assess(){
        $id = input('id','');//课程id
        $course_model = new Course();
        $course_info = $course_model->getDetail($id);
        if(!$course_info){
            return json(array(
                'status' => -1,
                'msg' => '课程不存在',
                'data' => array()
            ));
        }

        $assess = Db::name('assess')->where(array('course_id' => $id))->find();
        if($assess == null
            || $assess['question1'] == -1
            || $assess['question2'] == -1
            || $assess['question3'] == -1
            || $assess['question4'] == -1
            || $assess['question5'] == -1
            || $assess['question6'] == -1
            || $assess['question7'] == -1
            || $assess['question8'] == -1
            || $assess['question9'] == -1
            || $assess['question10'] == -1
            || $assess['question11'] == -1
            || $assess['question12'] == -1
            || $assess['question13'] == -1
            || $assess['question14'] == -1
            || $assess['question15'] == -1
            || empty($assess['word1'])
            || empty($assess['word2'])
            || empty($assess['word3'])
            || empty($assess['main_word'])
            || empty($assess['word_pass'])
            || empty($assess['word_not_pass'])
        ){
            return json(array(
                'status' => -1,
                'msg' => '测试结果还没有生成！',
                'data' => array()
            ));
        }
        $temp_count = 0;
        for($i=1;$i<=15;$i++){
            if(in_array($this->student_info['id'],explode(',',$assess['question'.$i]))){
                $temp_count++;
            }
        }
        if($temp_count >=1 && $temp_count <=3){
            $before_assess = 'D';
        }elseif ($temp_count >=4 && $temp_count <=7){
            $before_assess = 'C';
        }elseif ($temp_count >=8 && $temp_count <=11){
            $before_assess = 'B';
        }elseif ($temp_count >=12){
            $before_assess = 'A';
        }else{
            $before_assess = '无评分';
        }
        $assess['before_assess'] = $before_assess;

        return json(array(
            'status' => 1,
            'msg' => '获取成功！',
            'data' => array(
                'assess' => $assess,
                'course_info' => $course_info
            )
        ));
    }
}
