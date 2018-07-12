<?php
/**
 * Created by PhpStorm.
 * User: zeng
 * Date: 2018/6/26
 * Time: 上午11:06
 */

namespace App\Modules\System;


use App\Modules\System\Model\Document;
use App\Modules\System\Model\NotifyQueue;
use App\Modules\System\Model\TxConfig;
use App\Modules\WeChatUser\Model\NotifyList;
use App\Modules\WeChatUser\Model\WeChatUser;
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
    public function addTxConfig($data)
    {
        $config = TxConfig::first();
        if (empty($config)){
            $config = new TxConfig();
        }
        foreach ($data as $key=>$value){
            $config->$key = $value;
        }
        if ($config->save()){
            return true;
        }
        return false;
    }
    public function getTxConfig()
    {
        return TxConfig::first();
    }
    public function countWeChatUsers($created)
    {
        return WeChatUser::whereDate('created_at',$created)->count();
    }
    public function getNotifyList()
    {
        $data = NotifyList::groupBy('open_id')->get();
        return $data;
    }
    public function delNotifyList($id)
    {
        $list = NotifyList::findOrFail($id);
        if ($list->delete()){
            return true;
        }
        return false;
    }
    public function addNotifyQueue($data)
    {
        $queue = new NotifyQueue();
        $queue->content = $data;
        if ($queue->save()){
            return true;
        }
        return false;
    }
    public function delNotifyQueue($id)
    {
        $queue = NotifyQueue::find($id);
        if ($queue->delete()){
            return true;
        }
        return false;
    }
}