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
                    $where = array();
                    $where['parent_id'] = $son_item['id'];
                    $where['status'] = array('neq',0);
                    $son_item['son'] = Db::name('category')->where($where)->select();
                    foreach ($son_item['son'] as &$son_son_item){
                        $son_son_item['attr'] = Db::name('cat_attr')->where('cat_id','=',$son_son_item['id'])->select();
                    }
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
                $where = array();
                $where['parent_id'] = $value['id'];
                $where['status'] = array('neq',0);
                $son_item['son'] = Db::name('category')->where($where)->select();
                foreach ($son_item['son'] as &$son_son_item){
                    $son_son_item['attr'] = Db::name('cat_attr')->where('cat_id','=',$son_son_item['id'])->select();
                }
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
        $level = input('level','');
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
        $data['level'] = $level;

        $parent_id = input('parent_id',0);
        if($parent_id != 0){
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
            if($level == 2){
                if(empty($attr_img) || empty($attr_hxnl) || empty($attr_class_place) || empty($attr_class_content)){
                    return json(array(
                        'status' => -1,
                        'msg' => '课程系列参数不能为空',
                        'data' => array(
                        )
                    ));
                }
            }elseif ($level == 3){
                if(empty($attr_banner_img)){
                    return json(array(
                        'status' => -1,
                        'msg' => '课程系列参数不能为空',
                        'data' => array(
                        )
                    ));
                }
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
        $level = input('level','');
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
            if($level == 2){
                if(empty($attr_img) || empty($attr_hxnl) || empty($attr_class_place) || empty($attr_class_content)){
                    return json(array(
                        'status' => -1,
                        'msg' => '课程系列参数不能为空',
                        'data' => array(
                        )
                    ));
                }
            }elseif ($level == 3){
                if(empty($attr_banner_img)){
                    return json(array(
                        'status' => -1,
                        'msg' => '课程系列参数不能为空',
                        'data' => array(
                        )
                    ));
                }
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

    public function imgUpload(){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');

        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                // 输出 jpg
                echo $info->getExtension();
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                echo $info->getSaveName();
                // 输出 42a79759f284b767dfcb2a0197904287.jpg
                echo $info->getFilename();

                return json(array(
                    'status' => 1,
                    'msg' => $file->getError(),
                    'data' => array(
                        'path' =>''；
                    )
                ));
            }else{
                // 上传失败获取错误信息
                return json(array(
                    'status' => -1,
                    'msg' => $file->getError(),
                    'data' => array(
                    )
                ));
            }
        }
    }


}
