<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IndexController extends Controller
{
    //

    public function sms()
    {
        $sms = app('easysms');

        try {


            $result = $sms->send(18553359039, [
                'content'  => '您的验证码为: 6379',
                'template' => 'SMS_101230084',
                'data' => [
                    'code' => 6379
                ],
            ]);

        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            $response = $exception->getResponse();
            $result = json_decode($response->getBody()->getContents(), true);
            dd($result);
        }
    }
}
