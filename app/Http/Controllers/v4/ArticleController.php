<?php

namespace App\Http\Controllers\v4;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ArticleController extends Controller
{
    public function index(Request $request){
        $page=$request->get('page');
        $limit=$request->get('limit');
        $data=DB::table('article')
            ->orderby('created_at','DESC')
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->select('id','title','pic','heat')
            ->get();
        $total=DB::table('article')->count();
        return response()->json([
           'data'=>$data,
            'total'=>$total
        ]);

    }

    public function detail(Request $request){
        $id=$request->get('id');
        $data=DB::table('article')
            ->where('id',$id)
            ->select('id','title','pic','content')
            ->get();
        $data=$data[0];
        return response()->json([
           'data'=>$data
        ]);
    }

    public function home_detail(Request $request){
        $id=$request->get('id');
        $data=DB::table('article')
            ->where('id',$id)
            ->value('content');
        $heat=DB::table('article')
            ->where('id',$id)
            ->value('heat');
        $heat++;
        DB::table('article')
            ->where('id',$id)
            ->update(['heat'=>$heat]);
        return response()->json([
           'data' =>$data
        ]);
    }

    public function add(Request $request){
        $dataGet=$request->all();
        $time=date('Y-m-d H:i:s');
        $dataGet['created_at']=$time;
        $res=DB::table('article')->insert($dataGet);
        if($res){
            return response()->json([
               'msg'=>'success'
            ]);
        }else{
            return response()->json([
               'msg'=>'fail'
            ]);
        }
    }

    public function edit(Request $request){
        $id=$request->get('id');
        $pic=$request->get('pic');
        $title=$request->get('title');
        $content=$request->get('content');
        $time=date('Y-m-d H:i:s');
        DB::table('article')->where('id',$id)->update([
           'title'=>$title,'pic'=>$pic,'content'=>$content,'updated_at'=>$time
        ]);
        return response()->json([
           'msg'=>'success'
        ]);
    }

    public function del(Request $request){
        $id=$request->get('id');
        $res=DB::table('article')->where('id',$id)->delete();
        if($res){
            return response()->json([
               'msg'=>'success'
            ]);
        }else{
            return response()->json([
               'msg'=>'fail'
            ]);
        }
    }


}
