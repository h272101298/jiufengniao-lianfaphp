<?php

namespace App\Console\Commands;

use App\Modules\System\SystemHandle;
use Illuminate\Console\Command;

class Notify extends Command
{
    use SystemHandle;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $notify = getWxNotify();
        $queues = $this->getNotifyQueues();
        foreach ($queues as $queue){
            $notify->setAccessToken();
            $data = $notify->send($queue->content);
            if ($data['errmsg']=='ok'){
                $this->delNotifyQueue($queue->id);
            }else{
                var_dump($data);
            }
        }
    }
}
