<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Pocket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DemoCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $year = date("Y");
        $month = date("m");
//        info("logged every minute");
        $pockets = Pocket::whereRaw("(year = $year AND ((start_month + month_count-1)%month_count ) >= $month) OR (year < $year AND ((start_month + month_count-1)%month_count ) >= $month")->get();
        //Get a list of all slots in active pockets
        //Get last invoice paid month per slot and convert to normal month index
        //Get a list of all that have not payment record for this month
        Notification::sendPushNotification([1,2,3,4],"Hello","Just Testing...");
//        return 0;
    }
}
