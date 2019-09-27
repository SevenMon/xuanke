<?php
namespace app\api\controller;

use think\Db;
use think\Exception;
use think\Request;

class Common extends Base
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->checkLogin();
    }

}
