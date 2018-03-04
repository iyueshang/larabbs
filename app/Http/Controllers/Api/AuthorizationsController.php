<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\Api\AuthorizationRequest;
use App\Http\Requests\Api\AuthorRequest;

class AuthorizationsController extends Controller
{
    //
    public function socialStore($type, AuthorizationRequest $request)
    {
        if(!in_array($type,['weixin'])){
            return $this->response->errorBadRequest();
        }

        $driver = \Socialite::driver($type);

        try {
            if($code = $request->code){
                $response = $driver->getAccessTokenResponse($code);
                $token = array_get($response,'access_token');

            }else {
                $token = $request->access_toekn;
                if($type == 'weixin')
                {
                    $driver->setOpenId($request->openid);
                }
            }
            $outhUser = $driver->userFromToken($token);

        } catch (\Exception $e) {
            return $this->response->errorUnauthorized('参数错误,为获取用户信息');
        }

        switch ($type){
            case 'weixin':
                $unionid = $outhUser->offsetExists('unionid') ? $outhUser->offsetGet('unionid') : null;
                if($unionid){
                    $user = User::where('weixin_unionid',$unionid)->first();
                }else{
                    $user = User::where('weixin_openid',$outhUser->getId())->first();
                }

                if($user)
                {
                    $user = User::create([
                        'name' => $outhUser->getNickname(),
                        'avatar' => $outhUser->getAvatar(),
                        'weixin_openid' => $outhUser->getId(),
                        'weixin_unionid' => $unionid
                    ]);
                }

                break;
        }
        return $this->response->array(['token' => $user->id]);
    }

    public function store(AuthorRequest $request)
    {
        $username = $request->username;

        filter_var($username,FILTER_VALIDATE_EMAIL) ?
            $credentials['email'] = $username :
            $credentials['phone'] = $username;
        $credentials['password'] = $request->password;

        if(!$token = \Auth::guard('api')->attempt($credentials)){
            return $this->response->errorUnauthorized('用户名或密码错误');
        }

        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => \Auth::guard('api')->factory()->getTTL() * 60,
        ])->setStatusCode(201);

    }
}
