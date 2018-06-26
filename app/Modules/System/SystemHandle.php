<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/6/26
 * Time: 上午11:06
 */

namespace App\Modules\System;


use App\Modules\System\Model\Document;
use Illuminate\Support\Facades\DB;

trait SystemHandle
{
    public function createDocument($id=0,$title,$content)
    {
        if ($id){
            $document = Document::find($id);
        }else{
            $document = new Document();
        }
        $document->title = $title;
        $document->detail = $content;
        if ($document->save()){
            return true;
        }
        return false;
    }
    public function getDocuments($page=1,$limit=10,$title='')
    {
        $db = DB::table('documents');
        if ($title){
            $db->where('title','like','%'.$title.'%');
        }
        $count = $db->count();
        $data = $db->limit($limit)->offset(($page-1)*$limit)->get();
        return [
            'data'=>$data,
            'count'=>$count
        ];
    }
    public function delDocument($id)
    {
        $document = Document::findOrFail($id);
        if ($document->delete()){
            return true;
        }
        return false;
    }
}