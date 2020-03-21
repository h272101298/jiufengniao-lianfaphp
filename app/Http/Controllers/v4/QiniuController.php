<?php

namespace App\Http\Controllers\v4;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Qiniu\Auth;

class QiniuController extends Controller
{
    //
    private $accessKey = "IRb_YKqItisSFGclNyJyLwBN9Wh2bhjX6eVCpVDB";
    private $secretKey = "PCksRolXkKH3A8bj_1DXiZW5KHLiTuCJXUmQ2AQK";
    private $bucket = "tubanuo";
    public function getToken(){
        $auth=new Auth($this->accessKey,$this->secretKey);
        $uptoken=$auth->uploadToken($this->bucket);

        $ret=['uptoken'=>$uptoken];
        return response()->json($ret);
    }
}
