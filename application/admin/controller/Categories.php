<?php
namespace app\admin\controller;

use think\Db;
use think\Exception;
use think\Request;

class Categories extends Base
{

    public $status = array(
        '0' => '删除',
        '1' => '开启',
        '2' => '关闭'
    );


    public function index(){
        $where = array();
        $where['parent_id'] = 0;
        $where['status'] = array('neq',0);
        $list = Db::name('category')->where($where)->select();
        foreach ($list as &$value){
            $where = array();
            $where['parent_id'] = $value['id'];
            $where['status'] = array('neq',0);
            $value['son'] = Db::name('category')->where($where)->select();
            if($value['son']){
                foreach ($value['son'] as &$son_item){
                    $son_item['attr'] = Db::name('cat_attr')->where('cat_id','=',$son_item['id'])->select();
                }
            }
        }
        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => array(
                'list' => $list
            )
        ));
    }

    public function show(){
        $cat_id = input('id','');
        $cat_info = Db::name('category')->where('id','=',$cat_id)->find();
        if($cat_info == null || $cat_info['status'] == 0){
            return json(array(
                'status' => -1,
                'msg' => '所查信息不存在',
                'data' => array(
                )
            ));
        }
        $cat_info['attr'] = Db::name('cat_attr')->where('cat_id','=',$cat_info['id'])->select();

        if($cat_info['parent_id'] == 0){
            $cat_info['son'] = Db::name('category')->where('parent_id','=',$cat_info['id'])->select();
            foreach ($cat_info['son'] as &$value){
                $value['attr'] = Db::name('cat_attr')->where('cat_id','=',$value['id'])->select();
            }
        }

        return json(array(
            'status' => 1,
            'msg' => '获取成功',
            'data' => array(
                'list' => $cat_info
            )
        ));
    }

    public function store(){
        $name = input('name','');
        $sort = input('sort',0);
        if(empty($name)){
            return json(array(
                'status' => -1,
                'msg' => '请输入名字',
                'data' => array(
                )
            ));
        }
        $data = array();
        $attr = array();
        $data['name'] = $name;
        $data['sort'] = $sort;
        $data['level'] = 1;

        $parent_id = input('parent_id',0);
        if($parent_id != 0){
            $data['level'] = 2;
            $parent_cat_info = Db::name('category')->where('id','=',$parent_id)->find();
            if($parent_cat_info == null){
                return json(array(
                    'status' => -1,
                    'msg' => '请输入正确的课程级别',
                    'data' => array(
                    )
                ));
            }
            $attr_img = input('attr_img','');
            $attr_banner_img = input('attr_banner_img','');
            $attr_hxnl = input('attr_hxnl','');
            $attr_class_place = input('attr_class_place','');
            $attr_class_content = input('attr_class_content','');
            if(empty($attr_img) || empty($attr_banner_img) || empty($attr_hxnl) || empty($attr_class_place) || empty($attr_class_content)){
                return json(array(
                    'status' => -1,
                    'msg' => '课程系列参数不能为空',
                    'data' => array(
                    )
                ));
            }

            $attr = array(
                'img' => $attr_img,
                'banner_img' => $attr_banner_img,
                'hxnl' => $attr_hxnl,
                'class_place' => $attr_class_place,
                'class_content' => $attr_class_content,
            );
        }
        $data['parent_id'] = $parent_id;
        Db::startTrans();
        try{
            $cat_id = Db::name('category')->insertGetId($data);
            if(!empty($attr)){
                $attr['cat_id'] = $cat_id;
                Db::name('cat_attr')->insertGetId($attr);
            }
        }catch (Exception $e){
            Db::rollback();
            return json(array(
                'status' => -1,
                'msg' => '添加失败',
                'data' => array(
                )
            ));
        }
        Db::commit();
        return json(array(
            'status' => 1,
            'msg' => '添加成功',
            'data' => array(
            )
        ));

    }

    public function update(){
        $cat_id = input('id','');
        $cat_info = Db::name('category')->where('id','=',$cat_id)->find();
        if($cat_info == null){
            return json(array(
                'status' => -1,
                'msg' => '修改数据不存在',
                'data' => array(
                )
            ));
        }

        $name = input('name','');
        $sort = input('sort',0);
        if(empty($name)){
            return json(array(
                'status' => -1,
                'msg' => '请输入名字',
                'data' => array(
                )
            ));
        }
        $data = array();
        $attr = array();
        $data['name'] = $name;
        $data['sort'] = $sort;

        if($cat_info['parent_id'] != 0){
            $parent_cat_info = Db::name('category')->where('id','=',$cat_info['parent_id'])->find();
            if($parent_cat_info == null){
                return json(array(
                    'status' => -1,
                    'msg' => '请输入正确的课程级别',
                    'data' => array(
                    )
                ));
            }
            $attr_img = input('attr_img','');
            $attr_banner_img = input('attr_banner_img','');
            $attr_hxnl = input('attr_hxnl','');
            $attr_class_place = input('attr_class_place','');
            $attr_class_content = input('attr_class_content','');
            if(empty($attr_img) || empty($attr_banner_img) || empty($attr_hxnl) || empty($attr_class_place) || empty($attr_class_content)){
                return json(array(
                    'status' => -1,
                    'msg' => '课程系列参数不能为空',
                    'data' => array(
                    )
                ));
            }

            $attr = array(
                'img' => $attr_img,
                'banner_img' => $attr_banner_img,
                'hxnl' => $attr_hxnl,
                'class_place' => $attr_class_place,
                'class_content' => $attr_class_content,
            );
        }
        Db::startTrans();
        try{
            Db::name('category')->where('id','=',$cat_id)->update($data);
            if(!empty($attr)){
                Db::name('cat_attr')->where('cat_id','=',$cat_id)->update($attr);
            }
        }catch (Exception $e){
            Db::rollback();
            return json(array(
                'status' => -1,
                'msg' => '修改失败',
                'data' => array(
                )
            ));
        }
        Db::commit();
        return json(array(
            'status' => 1,
            'msg' => '修改成功',
            'data' => array(
            )
        ));

    }

    public function destroy(){
        $cat_id = input('id','');
        $cat_info = Db::name('category')->where('id','=',$cat_id)->find();
        if($cat_info == null || $cat_info['status'] == 0){
            return json(array(
                'status' => -1,
                'msg' => '修改数据不存在',
                'data' => array(
                )
            ));
        }
        Db::startTrans();
        try{
            Db::name('category')->where('id','=',$cat_id)->update(array('status' => 0));
        }catch (Exception $e){
            Db::rollback();
            return json(array(
                'status' => -1,
                'msg' => '删除失败',
                'data' => array(
                )
            ));
        }
        Db::commit();
        return json(array(
            'status' => 1,
            'msg' => '删除成功',
            'data' => array(
            )
        ));

    }



}
