<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Shift;
use App\Schedule;
use App\Meal;
use App\Order;
use Carbon\Carbon;
use App\Events\OrdersProcess;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PushNotification;

class SelectWinningMeals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'winning-meals:select';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the process of winning meals';

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
        $start = Carbon::now();
        \Log::notice($start);
        $shift = Shift::ended()->checkshift(Carbon::parse()->format('H:i:s'))->first();
        if( $shift ){
            $orders = ['winner'=>[], 'runner'=>[]];
            $schedules = $shift->schedules()->today()->whereHas('orders', function($query) use($shift){
                $query->where('shift_id', $shift->id)->exceptStatus(Order::VOTING, '=');
            })->get()->map(function($schedule) use(&$orders, $shift){

                $meals = $schedule->top3Meals()->select('meals.id', 'meals.name')->with(['orders' => function($query) use($shift, $schedule){
                    $query->where(['shift_id' => $shift->id, 'schedule_id' => $schedule->id])->today()->exceptStatus(Order::VOTING, '=');
                }])->join('orders', 'meals.id', 'orders.meal_id')->groupBy('meal_schedule.meal_id')->orderBy('orders.created_at')->get();
                // dd($meals->toArray());
                $meals->take(3)->map(function($meal) use(&$orders){
                    $orders['winner'] = array_merge($orders['winner'], $meal->orders->toArray());
                });

                $meals->slice(3)->map(function($meal) use(&$orders){
                    $orders['runner'] = array_merge($orders['runner'], $meal->orders->toArray());
                });

                return $orders;
            });


            $winnerIds = collect($orders['winner'])->pluck('id');
            $runnerIds = collect($orders['runner'])->pluck('id');

            if( $winnerIds->count() )
                $customer_ids = Order::whereIn('id',$winnerIds)->pluck('customer_id');
                Order::whereIn('id', $winnerIds)->update(['status' => Order::PENDING]);

                foreach($customer_ids as $id)
                {
                    $customer = \App\Customer::where('id', $id)->first();
                    if($customer)
                    {
                        logger('Notification: Winner Meals Decided '. $customer->name);
                        $data = getNotificationData('customer-winner-meal-decided');
                        $message = replacement(['{{ CUSTOMER }}'],[$customer->name],$data->message);
                        Notification::send($customer, new PushNotification($data->title, $message, $data->link, ($data->imageExists()) ? $data->imageUrl() : null));
//                        dd($message);
                    }
                }
                logger('All winner customer deal decided notification ended.');
            // customer ids
            if( $runnerIds->count() ){
                $customer_ids = Order::whereIn('id',$runnerIds)->pluck('customer_id');
                Order::whereIn('id', $runnerIds)->update(['status' => Order::VOTING_CANCEL]);
                event(new OrdersProcess($runnerIds));
                foreach($customer_ids as $id)
                {
                    $customer = \App\Customer::where('id', $id)->first();
                    if($customer)
                    {
                        logger('Notification: Runner up Meals Decided '. $customer->name);
                        $data = getNotificationData('customer-winner-meal-lose');
                        $message = replacement(['{{ CUSTOMER }}'],[$customer->name],$data->message);
                        Notification::send($customer, new PushNotification($data->title, $message, $data->link, ($data->imageExists()) ? $data->imageUrl() : null));
                    }
                }
                logger('All Runner Up Customer meal Decided.');
            }
        }  

        $end = Carbon::now();

        \Log::notice($end);

        \Log::info($start->diffInMinutes($end));

        
    }
}
