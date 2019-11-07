<?php
/**
 * Created by PhpStorm.
 * User: LENOVO
 * Date: 2019/11/7
 * Time: 10:26
 */
namespace app\admin\controller;

use think\Db;
use think\Exception;

class Config extends Base{

    public function getValue(){
        $key = input('key','');
        $config_item = Db::name('config')->where(array('key' => $key))->find();
        if($config_item == null || empty($config_item)){
            return json(array(
                'status' => -1,
                'msg' => '参数错误！',
                'data' => array()
            ));
        }
        return json(array(
            'status' => 1,
            'msg' => '获取成功！',
            'data' => array(
                'data' => $config_item
            )
        ));
    }

    public function setVlue(){
        $key = input('key','');
        $value = input('value','');
        $config_item = Db::name('config')->where(array('key' => $key))->find();
        if($config_item == null || empty($config_item)){
            return json(array(
                'status' => -1,
                'msg' => '参数错误！',
                'data' => array()
            ));
        }
        try{
            Db::name('config')->where(array('key' => $key))->update(array('value' => $value));
            return json(array(
                'status' => 1,
                'msg' => '保存成功！',
                'data' => array()
            ));
        }catch (Exception $e){
            return json(array(
                'status' => -1,
                'msg' => '保存失败！',
                'data' => array()
            ));
        }
    }
}