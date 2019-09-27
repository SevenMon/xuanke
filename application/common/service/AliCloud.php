<?php
namespace app\common\service;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class AliCloud
{
    private $accessKeyId = '';
    private $accessSecret = '';

    public function __construct()
    {
        $this->accessKeyId = config('ali_cloud.accessKeyId');
        $this->accessSecret = config('ali_cloud.accessSecret');
    }

    //发送登录验证码
    public function sendLoginCode($phone,$code){

    }
}
