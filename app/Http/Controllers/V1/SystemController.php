<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\DocumentPost;
use App\Modules\Role\Model\Permission;
use App\Modules\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Maatwebsite\Excel\Excel;

class SystemController extends Controller
{
    //
    private $handle;
    public function __construct()
    {
        $this->handle = new User();
    }
//    public function __construct(Excel $excel)
//    {
//        $this->excel = $excel;
//    }
//    public function test(Request $post)
//    {
//        $file = $post->file('file');
//        if ($file){
//            $list = [];
//            $this->excel->selectSheetsByIndex(0)->load($file,function ($sheet) use (&$list){
//                $sheet->ignoreEmpty()->each(function ($data) use (&$list){
//                    $origin = $data->toArray();
////                    dd($origin);
////                    $origin = array_values($origin);
////                    dd($origin);
//                    $permission = new Permission();
//                    $permission->name = $origin['name'];
//                    $permission->display_name = $origin['display_name'];
//                    $permission->save();
////                    dd($origin);
//                });
//            });
////            dd($list);
//            return response()->json([
//                'code'=>'200',
//                'msg'=>'SUCCESS'
//            ]);
////            dd($file);
//        }else{
//            return response()->json([
//                'code'=>'400',
//                'msg'=>'空文件'
//            ]);
//        }
//    }
    public function getDocuments()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $title = Input::get('title','');
        $data = $this->handle->getDocuments($page,$limit,$title);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function createDocument(DocumentPost $post)
    {
        $id = $post->id?$post->id:0;
        if ($this->handle->createDocument($id,$post->title,$post->detail)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'参数错误！'
        ],400);
    }
    public function delDocument()
    {
        $id = Input::get('id');
        if ($this->handle->delDocument($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        };
        return jsonResponse([
            'msg'=>'操作失败！'
        ],400);
    }
    public function addPermission()
    {
        $id = Input::get('id',0);
        $data = [
            'name'=>Input::get('name'),
            'display_name' => Input::get('display_name')
        ];
        if ($this->handle->createPermission($id,$data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
    }
    public function getPermissions()
    {
        $permissions = $this->handle->getPermissions();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$permissions
        ]);

    }
    public function addRole()
    {
        $id = Input::get('id',0);
        $data = [
            'name'=>Input::get('name'),
            'display_name' => Input::get('display_name')
        ];
        $permissions = Input::get('permissions');
        if ($this->handle->createRole($id,$data,$permissions)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
    }
    public function getRoles()
    {
        $page = Input::get('page',1);
        $limit = Input::get('limit',10);
        $name = Input::get('name');
        $data = $this->handle->getRoles($page,$limit,$name);
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function delRole()
    {
        $id = Input::get('id');
        if($this->handle->delRole($id)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
    }
    public function addBrokerageRatio(Request $post)
    {
        $data = [
            'system'=>$post->system,
            'level1'=>$post->level1,
            'level2'=>$post->level2,
            'level3'=>$post->level3
        ];
        if ($this->handle->addBrokerageRatio($data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        };
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getBrokerageRatio()
    {
        $data = $this->handle->getBrokerageRatio();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addTxConfig(Request $post)
    {
        $data = [
            'app_id'=>$post->app_id,
            'app_secret'=>$post->app_secret,
            'api_key'=>$post->api_key,
            'mch_id'=>$post->mch_id,
            'ssl_cert'=>$post->ssl_cert,
            'ssl_key'=>$post->ssl_key
        ];
        if ($this->handle->addTxConfig($data)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
    public function getTxConfig()
    {
        $data = $this->handle->getTxConfig();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function upload(Request $request)
    {
        if (!$request->hasFile('file')){
            return response()->json([
                'msg'=>'空文件'
            ],422);
        }
        $file = $request->file('file');
        $name = $file->getClientOriginalName();
        $name = explode('.',$name);
        if (count($name)!=2){
            return response()->json([
                'msg'=>'非法文件名!'
            ],422);
        }
        $allow =  [
            'pem',
            'mp4',
        ];
        if (!in_array(strtolower($name[1]),$allow)){
            return response()->json([
                'msg'=>'不支持的文件格式'
            ],422);
        }
        $md5 = md5_file($file);
        $name = $name[1];
        $name = $md5.'.'.$name;
        if (!$file){
            return response()->json([
                'msg'=>'空文件'
            ],422);
        }
        if ($file->isValid()){
            $destinationPath = 'uploads';
            $file->move($destinationPath,$name);
            return response()->json([
                'msg'=>'ok',
                'data'=>[
                    'url'=>$destinationPath.'/'.$name,
                ]
            ]);
        }
    }
    public function getCount()
    {
        $storeId = checkPermission(Auth::id(),'productListAll')?0:getStoreId();
        $amount = $this->handle->getStoreAmount(getStoreId());
        $data = [
            'productCount'=>$this->handle->countProduct($storeId),
            'todayOrderCount'=>$this->handle->countOrders(0,'',date('Y-m-d')),
            'todaySalesCount'=>$this->handle->countSales(0,date('Y-m-d')),
            'todayUserCount'=>$this->handle->countWeChatUsers(date('Y-m-d')),
            'reviewProductCount'=>$this->handle->countProduct($storeId,0,1),
            'storeAmount'=>$amount->amount,
            'withdrawAmount'=>$this->handle->countStoreWithdraw($storeId),
            'amount'=>$amount->available,
            'loginCount'=>getRedisData('LoginCount',0)
        ];
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addNotifyConfig(Request $post)
    {
        $data = $post->all();
        if (!empty($data)){
            foreach ($data as $key=>$value){
                $this->handle->addNotifyConfig($key,$value);
            }
        }
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }
    public function getNotifyConfigs()
    {
        $data = $this->handle->getNotifyConfigs();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function addPosterConfigs(Request $post)
    {
        $data = $post->all();
        if (!empty($data)){
            foreach ($data as $key=>$value){
                $this->handle->addPosterConfig($key,$value);
            }
        }
        return jsonResponse([
            'msg'=>'ok'
        ]);
    }
    public function getPosterConfigs()
    {
        $data = $this->handle->getPosterConfigs();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$data
        ]);
    }
    public function getIconConfigs()
    {
        $configs = $this->handle->getIconConfigs();
        return jsonResponse([
            'msg'=>'ok',
            'data'=>$configs
        ]);
    }
    public function addIconConfig()
    {
        $position = Input::get('position');
        $url = Input::get('url');
        if ($this->handle->addIconConfig($position,$url)){
            return jsonResponse([
                'msg'=>'ok'
            ]);
        }
        return jsonResponse([
            'msg'=>'系统错误！'
        ],400);
    }
}
