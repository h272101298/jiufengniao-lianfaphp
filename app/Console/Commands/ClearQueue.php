<?php

namespace App\Console\Commands;

use App\Modules\System\Model\NotifyQueue;
use App\Modules\WeChatUser\Model\NotifyList;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ClearQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clearQueues';

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
        //
        NotifyList::where('created_at','<',Carbon::parse(date('Y-m-d',strtotime('-6 days'))))->orWhere('notify_id','=','the formId is a mock one')->delete();
    }
}
