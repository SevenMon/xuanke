<?php
namespace app\admin\controller;

use think\Db;
use think\Exception;
use think\Request;

class Common extends Base
{

    private $type = array('png','jpg','gif');
    private $size = 5120;
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->checkLogin();
    }

    public function uploadImg(){
        // 获取表单上传的文件，例如上传了一张图片
        $file = request()->file('image');
        if($file){
            //将传入的图片移动到框架应用根目录/public/uploads/ 目录下，ROOT_PATH是根目录下，DS是代表斜杠 /
            $info = $file->validate(['ext'=>$this->type])->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                return json(array(
                    'status' => 1,
                    'msg' => '获取成功',
                    'data' => array(
                        'path' => 'public'.DS . 'uploads'.DS.$info->getSaveName(),
                        'show_path' => getUrl().'public'.DS . 'uploads'.DS.$info->getSaveName(),
                    )
                ));
            }else{
                // 上传失败获取错误信息
                return json(array(
                    'status' => 1,
                    'msg' => $file->getError(),
                    'data' => array(
                    )
                ));
            }
        }
    }
}
