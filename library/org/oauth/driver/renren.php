<?php
// +----------------------------------------------------------------------
// | TOPThink [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2010 http://topthink.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi.cn@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace think\oauth\driver;
use think\oauth\Driver;

class Renren extends Driver{
    /**
     * 获取requestCode的api接口
     * @var string
     */
    protected $getRequestCodeURL = 'https://graph.renren.com/oauth/authorize';

    /**
     * 获取access_token的api接口
     * @var string
     */
    protected $getAccessTokenURL = 'https://graph.renren.com/oauth/token';

    /**
     * API根路径
     * @var string
     */
    protected $apiBase = 'http://api.renren.com/restserver.do';

    /**
     * 组装接口调用参数 并调用接口
     * @param  string $api    微博API
     * @param  string $param  调用API的额外参数
     * @param  string $method HTTP请求方法 默认为GET
     * @return json
     */
    public function call($api, $param = '', $method = 'POST'){
        /* 人人网调用公共参数 */
        $params = array(
            'method'       => $api,
            'access_token' => $this->token['access_token'],
            'v'            => '1.0',
            'format'       => 'json',
        );
        
        $data = $this->http($this->url(''), $this->param($params, $param), $method);
        return json_decode($data, true);
    }

    /**
     * 合并默认参数和额外参数
     * @param array $params  默认参数
     * @param array/string $param 额外参数
     * @return array:
     */
    protected function param($params, $param){
        $params = parent::param($params, $param);
        
        /* 签名 */
        ksort($params);
        $param = [];
        foreach ($params as $key => $value){
            $param[] = "{$key}={$value}";
        }
        $sign = implode('', $param).$this->AppSecret;
        $params['sig'] = md5($sign);

        return $params;
    }

    /**
     * 解析access_token方法请求后的返回值
     * @param string $result 获取access_token的方法的返回值
     */
    protected function parseToken($result){
        $data = json_decode($result, true);
        if($data['access_token'] && $data['expires_in'] && $data['refresh_token'] && $data['user']['id']){
            $data['openid'] = $data['user']['id'];
            unset($data['user']);
            return $data;
        } else
            throw new \Exception("获取人人网ACCESS_TOKEN出错：{$data['error_description']}");
    }

    /**
     * 获取当前授权应用的openid
     * @return string
     */
    public function getOpenId(){
        if(!empty($this->token['openid']))
            return $this->token['openid'];
        return null;
    }

    /**
     * 获取当前登录的用户信息
     * @return array
     */
    public function getOauthInfo(){
        $data   = $this->call('users.getInfo');

        if(!isset($data['error_code'])){
            $userInfo['type']   =   'RENREN';
            $userInfo['name']   =   $data[0]['name'];
            $userInfo['nick']   =   $data[0]['name'];
            $userInfo['avatar'] =   $data[0]['headurl'];
            return $userInfo;
        } else {
            E("获取人人网用户信息失败：{$data['error_msg']}");
        }
    }
}
