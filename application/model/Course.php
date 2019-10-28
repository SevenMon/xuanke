<?php
namespace app\model;

use think\Model;
use think\Db;

class Course extends Model
{
    public function getDetail($id){
        $course_info = $this->where(array('id' => $id))->find()->toArray();
        if($course_info == null){
            return false;
        }

        //课程系列
        $course_info['cat2_info'] = Db::name('category')->where('id','=',$course_info['cat_id2'])->find();
        $attr2 = Db::name('cat_attr')->where('cat_id','=',$course_info['cat_id2'])->find();
        $attr2['img'] =  getUrl().$attr2['img'];
        $attr2['banner_img'] =  getUrl().$attr2['banner_img'];
        $course_info['cat2_info']['attr'] = $attr2;

        //课程
        $course_info['cat3_info'] = Db::name('category')->where('id','=',$course_info['cat_id3'])->find();
        $attr3 = Db::name('cat_attr')->where('cat_id','=',$course_info['cat_id3'])->find();
        $attr3['img'] =  getUrl().$attr3['img'];
        $attr3['banner_img'] =  getUrl().$attr3['banner_img'];
        $course_info['cat3_info']['attr'] = $attr3;

        $course_info['foot_title'] = '剩余'.($course_info['max_people_num']-$course_info['people_num']).'个名额';
        $course_info['class_start_end_time'] = timetostr($course_info['start_time'],$course_info['end_time']);
        return $course_info;
    }
}