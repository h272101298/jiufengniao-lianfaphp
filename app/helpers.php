<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/5/29
 * Time: 下午2:52
 */

/**
 * 返回json响应
 */
if (!function_exists('jsonResponse')){
    function jsonResponse($param,$code=200){
        return response()->json($param,$code);
    }
}
/**
 * 返回视图响应
 */
if (!function_exists('viewResponse')){
    function viewResponse($view,$param){
        return view($view,$param);
    }
}
/**
 * 返回随机字符串
 */
if (!function_exists('createNonceStr')){
    function CreateNonceStr($length = 10){
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
}
/**
 * 设置redis缓存数据
 *
 */
if (!function_exists('setRedisData')){
    function setRedisData($key,$value,$time=0){
        \Illuminate\Support\Facades\Redis::set($key,$value);
        if ($time!=0){
            \Illuminate\Support\Facades\Redis::expire($key,$time);
        }
    }
}
/**
 * 获取redis缓存数据
 */
if (!function_exists('getRedisData')){
    function getRedisData($key,$refresh=0){
        $data = \Illuminate\Support\Facades\Redis::get($key);
        return $data;
    }
}
/**
 *  返回校验消息
 */
if (!function_exists('getRequestMessage')){
    function getRequestMessage($key){
        $message = config('message.'.$key);
        return $message;
    }
}