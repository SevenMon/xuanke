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
        AlibabaCloud::accessKeyClient($this->accessKeyId, $this->accessSecret)
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'PhoneNumbers' => $phone,
                        'SignName' => "麦禾学园",
                        'TemplateCode' => "SMS_174990274",
                        'TemplateParam' => "{\"code\":$code}",
                    ],
                ])
                ->request();
            $data = $result->toArray();
            if($data['Message'] == 'OK'){
                return array('code' => 1,'msg' => '发送成功');
            }else{
                return array('code' => -1,'msg' => $data['Message']);
            }
        } catch (ClientException $e) {
            return array('code' => -1,'msg' => $e->getErrorMessage());
        } catch (ServerException $e) {
            return array('code' => -1,'msg' => $e->getErrorMessage());
        }
    }
}
